<?php

namespace App\Notifications;

use App\Events\Product\ProductAfterUpdate;
use App\Models\AdminUser;
use App\Models\Notification as NotificationModel;
use App\Models\Setting;
use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;
    protected string $sentTo = 'customer';

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductAfterUpdate $event, $sentTo = 'customer')
    {
        $this->event = $event;
        $this->sentTo = $sentTo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $emailSettings = Setting::query()->where('variable', 'email')->first()?->value;
        $demoMode = (bool) data_get($emailSettings, 'email_demo_mode', false);

        return $demoMode ? ['database'] : ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->event->product;

        return (new MailMessage)
            ->subject('Product Updated - ' . $product->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A product has been updated.')
            ->line('Product: ' . $product->title)
            ->line('Description: ' . $product->short_description)
            ->line('Price: $' . number_format($product->price ?? 0, 2))
            ->line('Status: ' . $product->status)
            ->action('View Product', url('admin/products/' . $product->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $product = $this->event->product;

        return [
            'product_id' => $product->id,
            'title' => 'Product Updated',
            'message' => 'The product "' . $product->title . '" has been updated.',
            'type' => 'product_updated',
            'metadata' => [
                'product_title' => $product->title,
                'product_price' => $product->price,
                'product_status' => $product->status,
                'seller_id' => $product->seller_id,
            ]
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $product = $this->event->product;
        $sentTo = in_array($this->sentTo, ['admin', 'seller', 'customer'], true) ? $this->sentTo : 'customer';
        $isAdminPanelUser = $notifiable instanceof AdminUser;

        // Store in custom notifications table
        NotificationModel::create([
            'user_id' => $isAdminPanelUser ? null : $notifiable->id,
            'admin_user_id' => $isAdminPanelUser ? $notifiable->id : null,
            'store_id' => $product->seller_id, // assuming seller_id is the store
            'type' => NotificationTypeEnum::PRODUCT,
            'sent_to' => $sentTo,
            'title' => 'Product Updated',
            'message' => 'The product "' . $product->title . '" has been updated.',
            'is_read' => false,
            'metadata' => [
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_price' => $product->price,
                'product_status' => $product->status,
                'seller_id' => $product->seller_id,
            ]
        ]);

        return $this->toArray($notifiable);
    }
}
