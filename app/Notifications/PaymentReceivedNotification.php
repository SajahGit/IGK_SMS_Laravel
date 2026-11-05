<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ! empty($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment received - receipt '.$this->payment->receipt_no)
            ->line('We received your payment of '.Money::format($this->payment->amount).'.')
            ->line('Receipt number: '.$this->payment->receipt_no)
            ->line('Invoice: '.$this->payment->invoice->invoice_no)
            ->line('Remaining balance: '.Money::format($this->payment->invoice->balance));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Payment received',
            'body' => 'Receipt '.$this->payment->receipt_no.' for '.Money::format($this->payment->amount).' was recorded.',
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'url' => url('/'),
        ];
    }
}
