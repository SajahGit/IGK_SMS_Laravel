<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification
{
    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ! empty($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Overdue fee invoice '.$this->invoice->invoice_no)
            ->line('A fee invoice is past its due date.')
            ->line('Invoice: '.$this->invoice->invoice_no)
            ->line('Outstanding balance: '.Money::format($this->invoice->balance))
            ->line('Due date: '.$this->invoice->due_date->toDateString());
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Fee invoice overdue',
            'body' => 'Invoice '.$this->invoice->invoice_no.' has an outstanding balance of '
                .Money::format($this->invoice->balance).' (due '.$this->invoice->due_date->toDateString().').',
            'invoice_id' => $this->invoice->id,
            'url' => url('/'),
        ];
    }
}
