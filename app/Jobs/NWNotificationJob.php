<?php

namespace App\Jobs;

use App\Notification;
use App\User;

/** New wholesaler signup notification job */
class NWNotificationJob extends Job
{
    protected $wholesaler;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $wholesaler)
    {
        $this->wholesaler = $wholesaler;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::where('role', 'retailer')->whereNotNull('fcm_token')
            ->whereRaw('id IN (SELECT DISTINCT user_id FROM user_contacts WHERE mobile = ? OR mobile = ?)', [$this->wholesaler->mobile, $this->wholesaler->username])
            ->get();

        foreach ($users as $user) {
            Notification::createForToken(
                $user->fcm_token,
                "New wholesaler!",
                "'{$this->wholesaler->name}' from your contacts just joined Zaveri Bazaar"
            )->send();
        }
    }
}
