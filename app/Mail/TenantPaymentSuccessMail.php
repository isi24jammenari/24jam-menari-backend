<?php

namespace App\Mail;

use App\Models\TenantBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantPaymentSuccessMail extends Mailable implements ShouldQueue
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
            subject: 'Pembayaran Tenant Berhasil - Kode Akses Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant_payment_success',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}