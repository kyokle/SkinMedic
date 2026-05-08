<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistJoinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $firstName;
    public string $serviceName;
    public string $preferredDate;
    public string $preferredTime;
    public int    $position;

    public function __construct(
        string $firstName,
        string $serviceName,
        string $preferredDate,
        string $preferredTime,
        int    $position
    ) {
        $this->firstName     = $firstName;
        $this->serviceName   = $serviceName;
        $this->preferredDate = $preferredDate;
        $this->preferredTime = $preferredTime;
        $this->position      = $position;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You\'re on the Waitlist — SkinMedic');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.waitlist_joined');
    }
}