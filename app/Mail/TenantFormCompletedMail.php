<?php

namespace App\Mail;

use App\Models\TenantBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantFormCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public TenantBooking $booking;

    public function __construct(TenantBooking $booking)
    {
        $this->booking = $booking;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pendaftaran Tenant Terverifikasi - 24 Jam Menari',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant_form_completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}