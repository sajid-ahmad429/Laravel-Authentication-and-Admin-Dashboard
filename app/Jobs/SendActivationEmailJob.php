<?php

namespace App\Jobs;

use App\Libraries\AuthLibrary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendActivationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job: rotate the activation token and e-mail the link.
     */
    public function handle(AuthLibrary $authLibrary): void
    {
        // Refresh the model in case it changed since dispatch.
        $user = User::find($this->user->id);

        if (! $user || (int) $user->activated === 1) {
            return;
        }

        $token = $authLibrary->generateToken($user, 'activate_token');
        $authLibrary->sendActivationEmail($user, $token);
    }
}
