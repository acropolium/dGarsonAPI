<?php
namespace App\Channels;

use App\DeviceToken;
use Illuminate\Notifications\Notification;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\PayloadDataBuilder;

class FcmChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {

        $tokens = $notifiable->device_tokens->where('type', DeviceToken::TYPE_FCM)->pluck('token')->toArray();

        if (! $tokens) {
            return;
        }

        /**@var $data PayloadDataBuilder*/
        $data = $notification->toFcm($notifiable);
        $downstreamResponse = FCM::sendTo($tokens, null, $data['notification'], $data['data']);
        $deleteTokens = $downstreamResponse->tokensToDelete();
        if(!empty($deleteTokens)){
            DeviceToken::where('type', DeviceToken::TYPE_FCM)->whereIn('token', $deleteTokens)->delete();
        }

    }
}