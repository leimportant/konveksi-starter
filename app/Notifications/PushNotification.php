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
        // Log notification attempt
        \Illuminate\Support\Facades\Log::info('Preparing web push notification', [
            'title' => $this->title,
            'body' => substr($this->body, 0, 100) . (strlen($this->body) > 100 ? '...' : ''),
            'url' => $this->url,
            'user_id' => $notifiable->id ?? 'unknown'
        ]);
        
        // Create the base message with required properties
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon('/images/icons/icon-192x192.png') // Default icon path
            ->badge('/images/icons/badge-72x72.png') // Badge for notification tray
            ->tag('notification-' . md5($this->title . $this->body)) // Tag for grouping similar notifications
            ->renotify(true) // Important for iOS to show multiple notifications
            ->requireInteraction(true) // Keep notification visible until user interacts with it
            ->vibrate([100, 50, 100]) // Vibration pattern for Android
            ->data([
                'url' => $this->url, 
                'body' => $this->body,
                'timestamp' => time(),
                'id' => uniqid('push_')
            ])
            ->options(['TTL' => 86400]); // 24 hours TTL

        // Add actions if URL is provided
        if (!empty($this->url)) {
            $message->action('View', $this->url);
            $message->action('Dismiss', 'dismiss');
            $message->data(['action_url' => $this->url]);
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