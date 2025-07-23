<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinancialNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $message;
    public $details;
    public $actionUrl;
    public $actionText;
    public $alertClass;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $title,
        string $message,
        array $details = [],
        string $actionUrl = null,
        string $actionText = null,
        string $alertClass = 'alert-info'
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->details = $details;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->alertClass = $alertClass;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.financial-notification',
            with: [
                'title' => $this->title,
                'message' => $this->message,
                'details' => $this->details,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'alertClass' => $this->alertClass,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}