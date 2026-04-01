<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $bookingId;

    public function __construct(string $bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle(): void
    {
        // ✅ FIX 7: Pakai lockForUpdate agar tidak ada race condition
        //    jika job tiba-tiba dieksekusi bersamaan (double dispatch, retry, dsb.)
        DB::transaction(function () {
            $booking = Booking::with('timeSlot')
                ->where('id', $this->bookingId)
                ->lockForUpdate()
                ->first();

            // Guard: booking tidak ditemukan
            if (!$booking) {
                Log::warning("ExpireBookingJob: Booking {$this->bookingId} tidak ditemukan.");
                return;
            }

            // ✅ FIX 7: Hanya proses jika masih 'pending'
            //    Jika sudah 'success' (webhook sudah masuk) atau sudah 'expired' (double run),
            //    skip tanpa error — ini bukan bug, ini idempotency.
            if ($booking->status !== 'pending') {
                Log::info("ExpireBookingJob: Booking {$this->bookingId} sudah berstatus '{$booking->status}'. Dilewati.");
                return;
            }

            // Expire booking
            $booking->update(['status' => 'expired']);

            // Bebaskan slot agar bisa dibeli orang lain
            // ✅ FIX 7: Null check eksplisit sebelum update slot
            if ($booking->timeSlot) {
                $booking->timeSlot->update(['is_booked' => false]);
                Log::info("ExpireBookingJob: Booking {$this->bookingId} expired. Slot {$booking->timeSlot->id} dibebaskan.");
            } else {
                Log::warning("ExpireBookingJob: Booking {$this->bookingId} expired, tapi timeSlot tidak ditemukan.");
            }
        });
    }
}
