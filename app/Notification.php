<?php

namespace App;

use Illuminate\Support\Facades\Log;

class Notification
{
    protected $to, $title, $body, $type;

    public static $TOPIC_WHOLESALER_APP = 'sonaar.wholesaler.all-users';

    /** Create notification object for the given topic and set type to topic */
    public static function createForTopic($topic, $title, $body)
    {
        $notification = new Notification;
        $notification->to = $topic;
        $notification->type = 'topic';
        $notification->title = $title;
        $notification->body = $body;

        return $notification;
    }

    /** Create notification object for the given token and set type to token */
    public static function createForToken($token, $title, $body)
    {
        $notification = new Notification;
        $notification->to = $token;
        $notification->type = 'token';
        $notification->title = $title;
        $notification->body = $body;

        return $notification;
    }

    public function send()
    {
        if (app()->environment('local')) return ['debug-message' => 'mock sent!'];

        $data = ['extra_data' => 'none'];

        $notificationArr = ['notification' => [
            'title' => $this->title, 'body' => $this->body
        ]];

        $fields = array_merge(['to' => $this->type == 'topic' ?  '/topics/' . $this->to : $this->to, 'data' => $data], $notificationArr);

        return self::sendNotification($fields);
    }

    private static function sendNotification($fields)
    {
        $headers = [
            'Authorization: key=' . env('FCM_KEY'),
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        curl_close($ch);

        Log::info($result);

        return $result;
    }
}
