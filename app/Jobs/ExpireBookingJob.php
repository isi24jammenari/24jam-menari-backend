<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookingId;

    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle(): void
    {
        // Cari booking berdasarkan ID
        $booking = Booking::with('timeSlot')->find($this->bookingId);

        if (!$booking) return;

        // Jika setelah 15 menit statusnya masih 'pending', HANCURKAN!
        if ($booking->status === 'pending') {
            $booking->update(['status' => 'expired']);
            
            // Bebaskan slot agar bisa dibeli orang lain
            if ($booking->timeSlot) {
                $booking->timeSlot->update(['is_booked' => false]);
            }

            Log::info("Booking {$this->bookingId} expired. Slot freed.");
        }
    }
}