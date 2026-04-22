<?php

namespace App\Jobs;

use App\Models\TenantBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireTenantBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $bookingId;

    public function __construct(string $bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $booking = TenantBooking::with('stand')
                ->where('id', $this->bookingId)
                ->lockForUpdate()
                ->first();

            if (!$booking) return;

            if ($booking->status !== 'pending') {
                Log::info("ExpireTenantBookingJob: Booking {$this->bookingId} status '{$booking->status}'. Dilewati.");
                return;
            }

            $booking->update(['status' => 'expired']);

            if ($booking->stand) {
                $booking->stand->update(['is_booked' => false]);
                Log::info("ExpireTenantBookingJob: Stand {$booking->stand->stand_number} dibebaskan.");
            }
        });
    }
}