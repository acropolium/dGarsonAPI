<?php
namespace App\Notifications;

use App\Channels\FcmChannel;
use App\DeviceToken;
use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use LaravelFCM\Message\PayloadDataBuilder;
use NotificationChannels\Apn\ApnChannel;
use NotificationChannels\Apn\ApnMessage;
use LaravelFCM\Message\PayloadNotificationBuilder;
use Illuminate\Support\Facades\Config;
use App\User;

class NewOrder extends Notification
{
    use Queueable;

    private $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannel::class, ApnChannel::class];
    }

    /**
     * @param  mixed  $notifiable
     * @return array
     */
    public function toFcm($notifiable)
    {
        $locale = $notifiable->device_tokens->first()->locale;

        $dataBuilder = new PayloadDataBuilder();

        $dataBuilder->addData([
            'order_id' => $this->order->id,
            'order_state' => $this->order->state,
            'message' => trans(
                'messages.new_order',
                ['id' => $this->order->id],
                'messages',
                $locale
            )
        ]);

        $notificationBuilder = new PayloadNotificationBuilder();
        $notificationBuilder->setTitle(config('name'));
        $notificationBuilder->setBody($dataBuilder->getData()['message']);
        if ($notifiable->role != User::ROLE_CLIENT) {
            $notificationBuilder->setClickAction(Config::get('app.url'));
            $notificationBuilder->setIcon('assets/icon/small-logo.png');
        } else {
            $notificationBuilder->setIcon('ic_notif');
        }
        $notificationBuilder->setSound('assets/the-calling.mp3');
        $notificationBuilder->setColor('#6545D4');
        $fcm_notification = $notificationBuilder->build();

        return [
            'data' => $dataBuilder->build(),
            'notification' => $fcm_notification
        ];
    }

    public function toApn($notifiable)
    {
        $locale = $notifiable->device_tokens->first()->locale;
        return ApnMessage::create()
            ->badge(1)
            ->title(config('name'))
            ->body(
                trans(
                    'messages.new_order',
                    ['id' => $this->order->id],
                    'messages',
                    $locale
                )
            )
            ->custom('order_id', $this->order->id)
            ->custom('order_state', $this->order->state);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
