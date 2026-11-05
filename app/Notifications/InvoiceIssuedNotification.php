<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceIssuedNotification extends Notification
{
    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ! empty($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice '.$this->invoice->invoice_no.' issued')
            ->line('A new fee invoice has been issued.')
            ->line('Invoice: '.$this->invoice->invoice_no)
            ->line('Amount due: '.Money::format($this->invoice->balance))
            ->line('Due date: '.$this->invoice->due_date->toDateString());
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New invoice issued',
            'body' => 'Invoice '.$this->invoice->invoice_no.' for '.Money::format($this->invoice->total)
                .' is due on '.$this->invoice->due_date->toDateString().'.',
            'invoice_id' => $this->invoice->id,
            'url' => url('/'),
        ];
    }
}
