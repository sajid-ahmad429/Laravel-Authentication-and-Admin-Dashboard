<?php

namespace App\Http\Controllers;

use App\Libraries\AuthLibrary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    /** Whitelisted sortable columns (never pass client input to orderBy). */
    protected const SORTABLE = [
        'id'         => 'id',
        'name'       => 'name',
        'email'      => 'email',
        'role'       => 'plan', // role is relational; falls back to a stable column
        'plan'       => 'plan',
        'country'    => 'country',
        'status'     => 'status',
        'created_at' => 'created_at',
    ];

    public function __construct(protected AuthLibrary $authLibrary)
    {
    }

    /* ==================================================================
     |  Page
     * ================================================================== */

    public function index(Request $request)
    {
        $stats = User::cachedStats();

        return view('masters.users.list', [
            'activeMenu' => 'users',
            'stats'      => $stats,
            'roles'      => (array) config('auth.roles'),
            'plans'      => (array) config('auth.protected_plans'),
        ]);
    }

    /* ==================================================================
     |  Server-side data (Tabulator remote pagination contract)
     * ================================================================== */

    /**
     * Contract (all params validated):
     *   page      int    1-based page number
     *   size      int    page size (1..100)
     *   sort_by   string whitelisted column
     *   sort_dir  asc|desc
     *   search    string global search term (max 100 chars)
     *   status    ''|0|1 active filter
     *   trash     0|1    recycle bin filter
     *   role      string spatie role filter
     *
     * Response:
     *   { last_page, total, current_page, stats, data[] }
     */
    public function getTableData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page'     => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'size'     => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by'  => ['sometimes', 'string', Rule::in(array_keys(self::SORTABLE))],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'status'   => ['sometimes', 'nullable', 'in:0,1'],
            'trash'    => ['sometimes', 'in:0,1'],
            'role'     => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $page    = (int) ($validated['page'] ?? 1);
        $size    = (int) ($validated['size'] ?? 10);
        $sortBy  = self::SORTABLE[$validated['sort_by'] ?? 'id'];
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $search  = trim((string) ($validated['search'] ?? ''));
        $status  = $validated['status'] ?? null;
        $trash   = (int) ($validated['trash'] ?? 0);
        $role    = strtolower(trim((string) ($validated['role'] ?? '')));

        $query = User::query()
            ->with('roles:id,name')
            ->select(['id', 'name', 'email', 'avatar', 'contact_no', 'company_name', 'country', 'plan', 'status', 'trash', 'activated', 'created_at'])
            ->where('trash', $trash);

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if ($role !== '') {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        // Global search (prefix on identity fields + bounded wildcard).
        if ($search !== '') {
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

            $query->where(function ($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                  ->orWhere('email', 'LIKE', $like)
                  ->orWhere('contact_no', 'LIKE', $like)
                  ->orWhere('company_name', 'LIKE', $like)
                  ->orWhere('country', 'LIKE', $like);
            });
        }

        $total = (clone $query)->count();

        $users = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc')
            ->forPage($page, $size)
            ->get();

        return response()->json([
            'last_page'    => (int) max(1, ceil($total / $size)),
            'total'        => $total,
            'current_page' => $page,
            'stats'        => User::cachedStats(),
            'data'         => $users->map(fn (User $u) => $this->transform($u))->all(),
        ]);
    }

    /**
     * Clean row payload — the frontend renders all markup, so the server
     * never emits HTML (removes a whole class of XSS).
     */
    protected function transform(User $u): array
    {
        return [
            'id'           => $u->id,
            'name'         => (string) $u->name,
            'email'        => (string) $u->email,
            'avatar'       => $u->avatar ? asset($u->avatar) : null,
            'initials'     => $this->initials($u->name),
            'role'         => $u->roleName() ?: (string) optional($u->roles->first())->name,
            'plan'         => (string) ($u->plan ?? ''),
            'country'      => (string) ($u->country ?? ''),
            'company_name' => (string) ($u->company_name ?? ''),
            'contact_no'   => (string) ($u->contact_no ?? ''),
            'status'       => (int) $u->status,
            'activated'    => (int) $u->activated,
            'created_at'   => optional($u->created_at)->format('d M Y'),
            'can_edit'     => $this->canManageTarget($u),
            'can_trash'    => $this->canManageTarget($u) && $u->id !== (int) session('id'),
        ];
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return strtoupper(substr($parts[0] ?? 'U', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    /* ==================================================================
     |  Create / Update
     * ================================================================== */

    public function store(Request $request): JsonResponse
    {
        $actor = $request->attributes->get('sessionUser') ?? User::find(session('id'));
        $actorRank = $actor?->roleRank() ?? 0;
        $isSuperadmin = $actor?->isProtected() ?? false;

        // Roles the actor may assign: never above own rank, never protected.
        $assignable = collect((array) config('auth.assignable_roles'))
            ->filter(fn ($r) => $isSuperadmin
                || (int) config("auth.role_rank.$r", PHP_INT_MAX) <= $actorRank)
            ->values()
            ->all();

        $validated = $request->validate([
            'user_id'       => ['sometimes', 'nullable', 'integer', 'min:0'],
            'userFullname'  => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\pN\s.\-\'&]+$/u'],
            'userEmail'     => ['required', 'email:rfc', 'max:255'],
            'userContact'   => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'companyName'   => ['nullable', 'string', 'max:150'],
            'country'       => ['nullable', 'string', 'max:100'],
            'user-role'     => ['nullable', 'string', Rule::in($assignable)],
            'user-plan'     => ['nullable', 'string', Rule::in((array) config('auth.protected_plans'))],
        ]);

        $userId    = (int) ($validated['user_id'] ?? 0);
        $isUpdating = $userId > 0;

        // Uniqueness check (ignores the target row when updating).
        if (User::where('email', $validated['userEmail'])->where('id', '!=', $userId)->exists()) {
            return response()->json([
                'status'  => 0,
                'message' => 'Validation failed.',
                'errors'  => ['userEmail' => ['This email address is already in use.']],
            ], 422);
        }

        if ($isUpdating) {
            $target = User::find($userId);

            if (! $target) {
                return response()->json(['status' => 0, 'message' => 'User not found.'], 404);
            }

            if (! $this->canManageTarget($target)) {
                return response()->json(['status' => 0, 'message' => 'You cannot modify this account.'], 403);
            }

            DB::beginTransaction();
            try {
                $target->fill([
                    'name'         => $validated['userFullname'],
                    'email'        => $validated['userEmail'],
                    'contact_no'   => $validated['userContact'] ?? null,
                    'company_name' => $validated['companyName'] ?? null,
                    'country'      => $validated['country'] ?? null,
                    'plan'         => $validated['user-plan'] ?? null,
                ])->save();

                if (! empty($validated['user-role'])) {
                    $target->syncRoles([$validated['user-role']]);
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                Log::error('User update failed: '.$e->getMessage());

                return response()->json(['status' => 0, 'message' => 'Could not update the user. Please try again.'], 500);
            }

            return response()->json(['status' => 1, 'message' => 'User updated successfully.']);
        }

        // ------------------------------------------------------------------
        // Create: no hardcoded passwords. The account starts unactivated and
        // receives an activation email (self-queues) to set up securely.
        // ------------------------------------------------------------------
        DB::beginTransaction();
        try {
            $user = new User();
            $user->name        = $validated['userFullname'];
            $user->email       = $validated['userEmail'];
            $user->password    = Str::password(24, symbols: true); // placeholder, unusable until activation flow
            $user->contact_no  = $validated['userContact'] ?? null;
            $user->company_name = $validated['companyName'] ?? null;
            $user->country     = $validated['country'] ?? null;
            $user->plan        = $validated['user-plan'] ?? null;
            $user->status      = 1;
            $user->trash       = 0;
            $user->activated   = 0;
            $user->save();

            $user->assignRole($validated['user-role'] ?? config('auth.default_role'));

            $token = $this->authLibrary->issueToken($user, 'activate_token');
            $this->authLibrary->sendActivationEmail($user, $token);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('User creation failed: '.$e->getMessage());

            return response()->json(['status' => 0, 'message' => 'Could not create the user. Please try again.'], 500);
        }

        return response()->json([
            'status'  => 1,
            'message' => 'User created. An activation email has been sent to their inbox.',
        ]);
    }

    /* ==================================================================
     |  Details (edit modal)
     * ================================================================== */

    public function getUserDetails(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);

        $user = User::with('roles:id,name')->find((int) $validated['id']);

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'User not found.'], 404);
        }

        if (! $this->canManageTarget($user)) {
            return response()->json(['status' => 0, 'message' => 'You cannot view this account.'], 403);
        }

        // Only return the fields the edit form needs — nothing more.
        return response()->json([
            'status' => 1,
            'data'   => [
                'id'           => $user->id,
                'name'         => (string) $user->name,
                'email'        => (string) $user->email,
                'contact_no'   => (string) ($user->contact_no ?? ''),
                'company_name' => (string) ($user->company_name ?? ''),
                'country'      => (string) ($user->country ?? ''),
                'plan'         => (string) ($user->plan ?? ''),
                'role'         => $user->roleName(),
            ],
        ]);
    }

    /* ==================================================================
     |  Trash / restore
     * ================================================================== */

    public function toggleTrash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'          => ['required', 'integer', 'min:1'],
            'action_type' => ['required', 'integer', 'in:0,1'],
        ]);

        $user = User::find((int) $validated['id']);

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'User not found.'], 404);
        }

        $targetAction = (int) $validated['action_type'];

        if ($user->id === (int) session('id')) {
            return response()->json(['status' => 0, 'message' => 'You cannot trash your own account.'], 403);
        }

        if (! $this->canManageTarget($user)) {
            return response()->json(['status' => 0, 'message' => 'You cannot modify this account.'], 403);
        }

        try {
            $user->forceFill(['trash' => $targetAction])->save();

            track_activity(
                ['trash' => $targetAction === 1 ? 0 : 1],
                $user,
                ['trash' => $targetAction],
                $user->id,
                'users',
                $targetAction === 1 ? 3 : 2
            );
        } catch (Throwable $e) {
            Log::error('Trash toggle failed: '.$e->getMessage());

            return response()->json(['status' => 0, 'message' => 'Operation failed. Please try again.'], 500);
        }

        return response()->json([
            'status'  => 1,
            'message' => $targetAction === 1
                ? 'User moved to trash.'
                : 'User restored successfully.',
        ]);
    }

    /* ==================================================================
     |  Admin triggered activation mail
     * ================================================================== */

    public function resendActivation(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);

        $user = User::find((int) $validated['id']);

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'User not found.'], 404);
        }

        if ((int) $user->activated === 1) {
            return response()->json(['status' => 0, 'message' => 'This account is already activated.']);
        }

        $token = $this->authLibrary->issueToken($user, 'activate_token');
        $sent  = $this->authLibrary->sendActivationEmail($user, $token);

        return response()->json([
            'status'  => $sent ? 1 : 0,
            'message' => $sent
                ? 'Activation link sent successfully.'
                : 'The email service is not responding. Please try again shortly.',
        ], $sent ? 200 : 503);
    }

    /* ==================================================================
     |  Authorization helpers
     * ================================================================== */

    /**
     * May the signed-in manager act on this target account?
     * Rules: Super Admin may manage anyone; everyone else only accounts of
     * strictly LOWER rank (an admin cannot tamper with another admin or the
     * Super Admin, an editor cannot touch admins, etc.).
     */
    protected function canManageTarget(User $target): bool
    {
        $actor = request()->attributes->get('sessionUser') ?? User::find(session('id'));

        if (! $actor) {
            return false;
        }

        if ($actor->isProtected()) {
            return true;
        }

        return $target->roleRank() < $actor->roleRank();
    }
}
