<?php

namespace App\Jobs;

use App\Notification;
use App\User;
use App\WholesalerRating;

/** Wholesaler rating notification job */
class WRNotificationJob extends Job
{
    protected $rating, $ignoreUserId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WholesalerRating $rating, $ignoreUserId)
    {
        $this->rating = $rating;
        $this->ignoreUserId = $ignoreUserId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::where('role', 'retailer')->where('id', '!=', $this->ignoreUserId)->whereNotNull('fcm_token')
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
