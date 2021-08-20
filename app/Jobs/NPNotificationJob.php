<?php

namespace App\Jobs;

use App\Notification;
use App\Post;
use App\User;

/** New post notification job */
class NPNotificationJob extends Job
{
    protected $post;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::where('role', 'retailer')->whereNotNull('fcm_token')
            // ->whereRaw('id IN (SELECT DISTINCT follower_id FROM follows WHERE followed_id = ?)', [$this->post->wholesaler_firm_id])
            ->get();

        foreach ($users as $user) {
            Notification::createForToken(
                $user->fcm_token,
                "New post!",
                "'{$this->post->firm->name}' posted on Zaveri Bazaar"
            )->send();
        }
    }
}
