<?php

namespace App\Services;

use App\Mail\SendActivationMail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmailService
{
    /**
     * Send activation email with queue optimization
     *
     * @param object $user
     * @param string $activationToken
     * @return bool
     */
    public function sendActivationEmail($user, $activationToken)
    {
        try {
            $activationLink = $this->generateActivationLink($user->id, $activationToken);
            
            // Queue the email with high priority for user registration
            Mail::to($user->email)
                ->queue((new SendActivationMail($user, $activationLink))
                    ->onQueue('emails')
                    ->delay(now()->addSeconds(5))); // Small delay to avoid overwhelming email servers
            
            // Cache the email send attempt to prevent duplicates
            Cache::put("activation_email_sent_{$user->id}", true, now()->addMinutes(5));
            
            Log::info('Activation email queued successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to queue activation email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send password reset email with queue optimization
     *
     * @param object $user
     * @param string $resetToken
     * @return bool
     */
    public function sendPasswordResetEmail($user, $resetToken)
    {
        try {
            $resetLink = $this->generatePasswordResetLink($user->id, $resetToken);
            
            // Queue the email with high priority for password reset
            Mail::to($user->email)
                ->queue((new ResetPasswordMail($user, $resetLink))
                    ->onQueue('emails')
                    ->delay(now()->addSeconds(2))); // Immediate for security
            
            // Cache the email send attempt
            Cache::put("reset_email_sent_{$user->id}", true, now()->addMinutes(5));
            
            Log::info('Password reset email queued successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to queue password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if email was recently sent to prevent spam
     *
     * @param int $userId
     * @param string $type
     * @return bool
     */
    public function wasEmailRecentlySent($userId, $type = 'activation')
    {
        return Cache::has("{$type}_email_sent_{$userId}");
    }

    /**
     * Generate activation link
     *
     * @param int $userId
     * @param string $token
     * @return string
     */
    private function generateActivationLink($userId, $token)
    {
        $encodedId = base64_encode($userId);
        return url('/activate/' . $encodedId . '/' . $token);
    }

    /**
     * Generate password reset link
     *
     * @param int $userId
     * @param string $token
     * @return string
     */
    private function generatePasswordResetLink($userId, $token)
    {
        $encodedId = base64_encode($userId);
        return url('/reset-password/' . $encodedId . '/' . $token);
    }

    /**
     * Bulk send emails (for newsletters, notifications, etc.)
     *
     * @param array $recipients
     * @param string $mailableClass
     * @param array $data
     * @return bool
     */
    public function sendBulkEmails($recipients, $mailableClass, $data = [])
    {
        try {
            $chunks = array_chunk($recipients, 50); // Process in chunks of 50
            
            foreach ($chunks as $index => $chunk) {
                foreach ($chunk as $recipient) {
                    Mail::to($recipient['email'])
                        ->queue((new $mailableClass($recipient, $data))
                            ->onQueue('bulk-emails')
                            ->delay(now()->addSeconds($index * 10))); // Stagger sending
                }
            }
            
            Log::info('Bulk emails queued successfully', [
                'total_recipients' => count($recipients),
                'mailable_class' => $mailableClass
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to queue bulk emails', [
                'error' => $e->getMessage(),
                'mailable_class' => $mailableClass
            ]);
            
            return false;
        }
    }
}