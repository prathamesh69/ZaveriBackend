<?php

namespace App\Jobs;

use App\Notification;
use App\RetailerRating;
use App\User;

/** Retailer rating notification job */
class RRNotificationJob extends Job
{
    protected $rating;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RetailerRating $rating)
    {
        $this->rating = $rating;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::where('role', 'wholesaler')->whereNotNull('fcm_token')
            ->whereRaw('id IN (SELECT DISTINCT user_id FROM user_contacts WHERE mobile = ?)', [$this->rating->mobile])
            ->get();

        foreach ($users as $user) {
            Notification::createForToken(
                $user->fcm_token,
                "New rating!",
                "One of your contacts just received {$this->rating->rating} star rating"
            )->send();
        }
    }
}
