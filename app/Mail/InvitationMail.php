<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Barryvdh\DomPDF\Facade\Pdf;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Undangan Resmi Pergelaran 24 Jam Menari ISI Surakarta 2026',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation_broadcast',
        );
    }

    public function attachments(): array
    {
        // Render PDF langsung di Memory (RAM), tanpa menyampah di folder Storage server
        $pdf = Pdf::loadView('pdf.invitation', ['booking' => $this->booking]);
        $pdfContent = $pdf->output();

        $fileName = 'Undangan_24JamMenari_' . str_replace(' ', '_', $this->booking->performance->group_name) . '.pdf';

        return [
            Attachment::fromData(fn () => $pdfContent, $fileName)
                    ->withMime('application/pdf'),
        ];
    }
}