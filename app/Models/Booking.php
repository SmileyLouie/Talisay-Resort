<?php

// ============================================================
// Booking.php — Eloquent Model for the 'bookings' Table
// ============================================================
// Represents a guest's reservation for a room or cottage.
// Contains booking reference, dates, guest count, status,
// and all relationships to user, accommodation unit, payment, and reviews.
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'reference_no',                     // Unique booking reference (e.g., TBR-A1B2C3D4)
    'booking_type',                     // 'regular' or 'special_resort'
    'booking_source',                   // 'online' or 'manual'
    'user_id',                          // Foreign key to the users table (the guest, nullable for walk-in)
    'guest_name_manual',                // Walk-in guest name (when no user account)
    'guest_contact_manual',             // Walk-in guest contact (phone/email)
    'accommodation_unit_id',            // Foreign key to the accommodation_units table
    'original_accommodation_unit_id',   // Foreign key to accommodation_units (previous unit before modification)
    'booking_date',                     // The date the guest will visit / check in
    'check_in_date',                    // Check-in date for multi-day / special bookings
    'check_out_date',                   // Check-out date for multi-day / special bookings
    'nights_count',                     // Number of nights for multi-day bookings
    'time_slot',                        // Optional time slot string (e.g., '08:00 AM - 05:00 PM')
    'guests_count',                     // Number of guests included in this booking
    'status',                           // Current status: pending|paid|checked_in|checked_out|cancelled|completed
    'admin_approval_status',            // 'not_required', 'pending', 'approved', 'rejected'
    'admin_approved_by',                // FK to user (admin who approved)
    'special_requests',                 // Optional text for special guest requests
    'modified_at',                      // Timestamp of last modification
    'modification_notes',               // Notes regarding booking modification
    'cancelled_at',                     // Timestamp when this booking was cancelled (null if not cancelled)
    'cancellation_reason',              // Reason given for cancellation (required when cancelling)
    'total_amount',                     // Total price in PHP
])]

class Booking extends Model
{
    use HasFactory;

    /**
     * Define how specific columns should be cast when read from the database.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date'   => 'date',
            'check_in_date'  => 'date',
            'check_out_date' => 'date',
            'cancelled_at'   => 'datetime',
            'modified_at'    => 'datetime',
            'total_amount'   => 'decimal:2',
            'nights_count'   => 'integer',
        ];
    }

    /**
     * Get the display name for the guest (manual walk-in or registered user).
     */
    public function getGuestNameAttribute(): string
    {
        return $this->guest_name_manual ?: ($this->user?->name ?? 'Guest User');
    }

    /**
     * Get the contact information for the guest.
     */
    public function getGuestContactAttribute(): string
    {
        return $this->guest_contact_manual ?: ($this->user?->phone ?? $this->user?->email ?? 'N/A');
    }

    /**
     * Check if booking was manually created by staff/admin.
     */
    public function isManual(): bool
    {
        return $this->booking_source === 'manual';
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The tourist/guest who made this booking (many-to-one).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The accommodation room/cottage that was booked (many-to-one).
     */
    public function accommodationUnit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'accommodation_unit_id');
    }

    /**
     * The previous accommodation unit before modification (many-to-one).
     */
    public function originalAccommodationUnit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'original_accommodation_unit_id');
    }

    public function adminApprovedBy()
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    /**
     * The payment record associated with this booking (one-to-one).
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * The guest's post-visit review for this booking (one-to-one).
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'paid', 'checked_in']);
    }

    // ──────────────────────────────────────────────────────────
    // STATIC HELPERS & CONFLICT CHECKING
    // ──────────────────────────────────────────────────────────

    public static function generateReferenceNo(): string
    {
        return 'TBR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }

    /**
     * Check if a specific accommodation unit has an overlapping active reservation.
     * Overlap occurs when:
     * (existing_check_in < requested_check_out) AND (existing_check_out > requested_check_in)
     *
     * @param int|string $unitId
     * @param string|\Carbon\Carbon $checkInDate
     * @param string|\Carbon\Carbon $checkOutDate
     * @param int|null $excludeBookingId
     * @return bool
     */
    public static function hasConflict($unitId, $checkInDate, $checkOutDate, $excludeBookingId = null): bool
    {
        return self::getConflictingBooking($unitId, $checkInDate, $checkOutDate, $excludeBookingId) !== null;
    }

    /**
     * Retrieve the first conflicting booking for a unit in the given date range.
     *
     * @param int|string $unitId
     * @param string|\Carbon\Carbon $checkInDate
     * @param string|\Carbon\Carbon $checkOutDate
     * @param int|null $excludeBookingId
     * @return Booking|null
     */
    public static function getConflictingBooking($unitId, $checkInDate, $checkOutDate, $excludeBookingId = null): ?self
    {
        $cin = \Carbon\Carbon::parse($checkInDate)->format('Y-m-d');
        $cout = \Carbon\Carbon::parse($checkOutDate)->format('Y-m-d');
        if ($cout <= $cin) {
            $cout = \Carbon\Carbon::parse($cin)->addDay()->format('Y-m-d');
        }

        $query = self::whereIn('status', ['pending', 'paid', 'checked_in'])
            ->where(function ($q) use ($unitId) {
                $q->where('accommodation_unit_id', $unitId)
                  ->orWhere('booking_type', 'special_resort');
            })
            ->where(function ($q) use ($cin, $cout) {
                $q->where(function ($inner) use ($cin, $cout) {
                    $inner->whereNotNull('check_in_date')
                          ->whereDate('check_in_date', '<', $cout)
                          ->whereDate('check_out_date', '>', $cin);
                })->orWhere(function ($inner) use ($cin, $cout) {
                    $inner->whereNull('check_in_date')
                          ->whereDate('booking_date', '<', $cout)
                          ->whereDate('booking_date', '>=', $cin);
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->with('user', 'accommodationUnit')->first();
    }

    /**
     * Check if a Special Full-Resort booking conflicts with ANY active unit bookings.
     *
     * @param string|\Carbon\Carbon $checkInDate
     * @param string|\Carbon\Carbon $checkOutDate
     * @param int|null $excludeBookingId
     * @return bool
     */
    public static function hasSpecialResortConflict($checkInDate, $checkOutDate, $excludeBookingId = null): bool
    {
        $cin = \Carbon\Carbon::parse($checkInDate)->format('Y-m-d');
        $cout = \Carbon\Carbon::parse($checkOutDate)->format('Y-m-d');
        if ($cout <= $cin) {
            $cout = \Carbon\Carbon::parse($cin)->addDay()->format('Y-m-d');
        }

        $query = self::whereIn('status', ['pending', 'paid', 'checked_in'])
            ->where(function ($q) use ($cin, $cout) {
                $q->where(function ($inner) use ($cin, $cout) {
                    $inner->whereNotNull('check_in_date')
                          ->whereDate('check_in_date', '<', $cout)
                          ->whereDate('check_out_date', '>', $cin);
                })->orWhere(function ($inner) use ($cin, $cout) {
                    $inner->whereNull('check_in_date')
                          ->whereDate('booking_date', '<', $cout)
                          ->whereDate('booking_date', '>=', $cin);
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Get all booked date ranges for a unit to assist in client-side calendar disabling.
     *
     * @param int|string $unitId
     * @return array
     */
    public static function getBookedDateRangesForUnit($unitId): array
    {
        return self::whereIn('status', ['pending', 'paid', 'checked_in'])
            ->where(function ($q) use ($unitId) {
                $q->where('accommodation_unit_id', $unitId)
                  ->orWhere('booking_type', 'special_resort');
            })
            ->where(function ($q) {
                $q->where('check_out_date', '>=', now()->format('Y-m-d'))
                  ->orWhere('booking_date', '>=', now()->format('Y-m-d'));
            })
            ->orderBy('check_in_date')
            ->get()
            ->map(function ($b) {
                $start = $b->check_in_date ? $b->check_in_date->format('Y-m-d') : $b->booking_date->format('Y-m-d');
                $end = $b->check_out_date ? $b->check_out_date->format('Y-m-d') : \Carbon\Carbon::parse($start)->addDay()->format('Y-m-d');
                return [
                    'check_in'  => $start,
                    'check_out' => $end,
                    'ref'       => $b->reference_no,
                    'type'      => $b->booking_type,
                ];
            })
            ->values()
            ->toArray();
    }
}
