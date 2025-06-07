<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PushNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $body, $url = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url ? trim($url) : null;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\WebPush\WebPushMessage
     */
    public function toWebPush($notifiable)
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->data(['url' => $this->url, 'body' => $this->body])
            ->options(['TTL' => 1000]);

        if (!empty($this->url)) {
            $message->action('View', url($this->url));
        }

        return $message;
    }


    /**
     * Log the push notification status.
     *
     * @param  bool  $success
     * @param  string|null  $message
     * @return void
     */
    public function log($success, $message = null)
    {
        // Implement logging logic here, e.g., using Laravel's Log facade
        if ($success) {
            \Illuminate\Support\Facades\Log::info('Push notification sent successfully: ' . $this->title, ['url' => $this->url]);
        } else {
            \Illuminate\Support\Facades\Log::error('Failed to send push notification: ' . $this->title, ['message' => $message, 'url' => $this->url]);
        }
    }
}