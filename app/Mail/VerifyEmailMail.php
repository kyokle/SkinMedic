<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $firstName;

    public function __construct(string $otp, string $firstName)
    {
        $this->otp       = $otp;
        $this->firstName = $firstName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify Your Email - SkinMedic');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}