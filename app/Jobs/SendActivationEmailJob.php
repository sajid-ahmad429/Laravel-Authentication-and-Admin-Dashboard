<?php

namespace App\Jobs;

use App\Services\EmailService;
use App\Libraries\AuthLibrary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendActivationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * The maximum number of seconds the job should run.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param $user
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->onQueue('emails'); // Use dedicated email queue
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EmailService $emailService, AuthLibrary $authLibrary)
    {
        try {
            // Check if email was recently sent to prevent spam
            if ($emailService->wasEmailRecentlySent($this->user->id, 'activation')) {
                Log::info('Activation email skipped - recently sent', [
                    'user_id' => $this->user->id
                ]);
                return;
            }

            // Generate activation token
            $encodedToken = $authLibrary->generateToken($this->user, 'activate_token');
            
            // Send activation email using optimized service
            $emailService->sendActivationEmail($this->user, $encodedToken);
            
        } catch (\Exception $e) {
            Log::error('SendActivationEmailJob failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendActivationEmailJob failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage()
        ]);
    }
}
