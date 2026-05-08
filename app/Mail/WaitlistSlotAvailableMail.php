<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistSlotAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $firstName;
    public string $serviceName;
    public string $preferredDate;
    public string $preferredTime;
    public string $claimUrl;
    public string $expiresAt;

    public function __construct(
        string $firstName,
        string $serviceName,
        string $preferredDate,
        string $preferredTime,
        string $claimUrl,
        string $expiresAt
    ) {
        $this->firstName     = $firstName;
        $this->serviceName   = $serviceName;
        $this->preferredDate = $preferredDate;
        $this->preferredTime = $preferredTime;
        $this->claimUrl      = $claimUrl;
        $this->expiresAt     = $expiresAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🔔 Your Waitlisted Slot is Available! — SkinMedic');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.waitlist_slot_available');
    }
}