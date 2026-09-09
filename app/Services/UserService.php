<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserService
{
    public function storeOrUpdateUser(array $validatedData, $userId = null): User|bool
    {
        return DB::transaction(function () use ($validatedData, $userId) {
            $isUpdating = !empty($userId) && $userId != 0;

            $data = [
                'name'         => $validatedData['userFullname'],
                'email'        => $validatedData['userEmail'],
                'contact_no'   => $validatedData['userContact'],
                'company_name' => $validatedData['companyName'] ?? null,
                'country'      => $validatedData['country'] ?? null,
                'plan'         => $validatedData['user-plan'] ?? null,
            ];

            if ($isUpdating) {
                $user = User::find($userId);

                if (!$user) {
                    return false;
                }

                $user->update($data);
            } else {
                $data['password'] = bcrypt('Smart@#123'); // Default password for new users
                $user = User::create($data);
            }

            if (!empty($validatedData['user-role'])) {
                $user->syncRoles([strtolower($validatedData['user-role'])]);
            }

            $this->clearCaches($userId);

            return $user;
        });
    }

    public function clearCaches(?int $userId = null): void
    {
        Cache::forget('count_active');
        Cache::forget('count_inactive');
        Cache::forget('count_total');
        Cache::forget('dt_total_base');
        Cache::forget('users_all_count');
        Cache::forget('users_inactive_count');
        Cache::forget('users_active_count');
        Cache::forget('users_list_data');
        Cache::forget('users_aggregate_counts');

        if ($userId) {
            Cache::forget("user_details_{$userId}");
        }
    }
}