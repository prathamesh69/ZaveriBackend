<?php

namespace App\Jobs;

use App\Notification;
use App\User;

/** New retailer signup notification job */
class NRNotificationJob extends Job
{
    protected $retailer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $retailer)
    {
        $this->retailer = $retailer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::where('role', 'wholesaler')->whereNotNull('fcm_token')
            ->whereRaw('id IN (SELECT DISTINCT user_id FROM user_contacts WHERE mobile = ? OR mobile = ?)', [$this->retailer->mobile, $this->retailer->username])
            ->get();

        foreach ($users as $user) {
            Notification::createForToken(
                $user->fcm_token,
                "New retailer!",
                "'{$this->retailer->name}' from your contacts just joined Zaveri Bazaar"
            )->send();
        }
    }
}
