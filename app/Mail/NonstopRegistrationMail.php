<?php

namespace App\Mail;

use App\Models\NonstopDancer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NonstopRegistrationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $dancer;

    public function __construct(NonstopDancer $dancer)
    {
        $this->dancer = $dancer;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Konfirmasi] Pendaftaran Penari 24 Jam Non-Stop Berhasil',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nonstop_registration',
        );
    }
}