<?php

// ============================================================
// Booking.php — Eloquent Model for the 'bookings' Table
// ============================================================
// Represents a guest's reservation for a room or cottage.
// Contains booking reference, dates, guest count, status,
// and all relationships to user, accommodation unit, payment, and reviews.
// ============================================================

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    'stay_type',                        // 'night' or 'hour'
    'hours_count',                      // Length of an hourly stay
    'hour_start',                       // Start time of an hourly stay, HH:MM
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
    'external_id',                      // UUID shared with the Supabase client booking
])]

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING     = 'pending';
    public const STATUS_PAID        = 'paid';
    public const STATUS_CHECKED_IN  = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED   = 'cancelled';
    public const STATUS_COMPLETED   = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_CHECKED_IN,
        self::STATUS_CHECKED_OUT,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    /** Statuses that occupy inventory and block other reservations. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_CHECKED_IN];

    /**
     * Allowed status transitions. A cancelled booking may be reinstated,
     * but only after the availability check is repeated.
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING     => [self::STATUS_PAID, self::STATUS_CHECKED_IN, self::STATUS_CANCELLED],
        self::STATUS_PAID        => [self::STATUS_CHECKED_IN, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_CHECKED_IN  => [self::STATUS_CHECKED_OUT, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_CHECKED_OUT => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED   => [],
        self::STATUS_CANCELLED   => [self::STATUS_PENDING, self::STATUS_PAID],
    ];

    public const DEFAULT_TIME_SLOT = '2:00 PM Check-in · 12:00 PM Check-out';

    public const EXCLUSIVE_OCCASIONS = [
        'Wedding or reception',
        'Birthday',
        'Family reunion',
        'Company or group event',
        'Debut or celebration',
        'Other private event',
    ];

    public const DEFAULT_EXCLUSIVE_TITLE = 'Exclusive full resort';

    public const DEFAULT_EXCLUSIVE_DESCRIPTION = 'This booking takes the whole resort for your group. Every room and cottage is held for your nights, so other guests cannot reserve them on the same dates. It is for a private occasion such as a wedding, birthday, reunion, or company gathering. Management reviews the request before the dates are locked in.';

    public const DEFAULT_EXCLUSIVE_INFORMATION = 'A private overnight stay for one group. The nightly rate covers every room and cottage. Management confirms the request before the dates are locked in.';

    public static function exclusiveText(string $key, string $default): string
    {
        $value = trim((string) SystemSetting::get($key, ''));

        return $value !== '' ? $value : $default;
    }

    public static function exclusiveCopy(): array
    {
        return [
            'title' => self::exclusiveText('exclusive_title', self::DEFAULT_EXCLUSIVE_TITLE),
            'description' => self::exclusiveText('exclusive_description', self::DEFAULT_EXCLUSIVE_DESCRIPTION),
            'information' => self::exclusiveText('exclusive_information', self::DEFAULT_EXCLUSIVE_INFORMATION),
        ];
    }

    public static function occasions(): array
    {
        $raw = (string) SystemSetting::get('exclusive_occasions', '');
        $lines = collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $lines !== [] ? $lines : self::EXCLUSIVE_OCCASIONS;
    }

    public function activities()
    {
        return $this->hasMany(BookingActivity::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isSpecialResort(): bool
    {
        return $this->booking_type === 'special_resort';
    }

    public function isHourly(): bool
    {
        return $this->stay_type === 'hour';
    }

    /**
     * Status shown on the bookings list.
     * Pending until an admin approves. Waiting when the approved stay is still ahead.
     * Check in on the arrival day until the guest leaves. Check out once the stay is over.
     */
    public function listStage(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'cancelled';
        }

        if ($this->status === self::STATUS_PENDING || ($this->isSpecialResort() && $this->admin_approval_status === 'pending')) {
            return 'pending';
        }

        if (in_array($this->status, [self::STATUS_CHECKED_OUT, self::STATUS_COMPLETED], true)) {
            return 'check_out';
        }

        if ($this->checkInDate()->gt(now()->startOfDay())) {
            return 'waiting';
        }

        return $this->stayHasEnded() ? 'check_out' : 'check_in';
    }

    /** True once the booked night or hourly slot is over, whatever the saved status is. */
    public function stayHasEnded(): bool
    {
        $today = now()->startOfDay();

        if ($this->isHourly()) {
            if ($this->hour_start) {
                [, $end] = $this->stayWindow();

                return now()->gte($end);
            }

            return $this->checkInDate()->lt($today);
        }

        return $today->gte($this->checkOutDate());
    }

    public function listStageLabel(): string
    {
        return match ($this->listStage()) {
            'waiting' => 'Waiting',
            'check_in' => 'Check in',
            'check_out' => 'Check out',
            'cancelled' => 'Cancelled',
            default => 'Pending',
        };
    }

    public function scopeListStage($query, string $stage)
    {
        $today = now()->toDateString();
        $now = now()->format('Y-m-d H:i:s');
        $approved = [self::STATUS_PAID, self::STATUS_CHECKED_IN];
        $checkInDate = "date(coalesce(check_in_date, booking_date))";
        $checkOutDate = "date(coalesce(check_out_date, date(coalesce(check_in_date, booking_date), '+' || CASE WHEN coalesce(nights_count, 0) < 1 THEN 1 ELSE nights_count END || ' days')))";
        $hourEnd = "datetime(date(coalesce(check_in_date, booking_date)) || ' ' || hour_start, '+' || CASE WHEN coalesce(hours_count, 0) < 1 THEN 1 ELSE hours_count END || ' hours')";

        $awaitingApproval = function ($q) {
            $q->where('status', self::STATUS_PENDING)
                ->orWhere(function ($special) {
                    $special->where('booking_type', 'special_resort')
                        ->where('admin_approval_status', 'pending');
                });
        };

        $stayEnded = function ($q) use ($today, $now, $checkInDate, $checkOutDate, $hourEnd) {
            $q->where(function ($ended) use ($today, $now, $checkInDate, $checkOutDate, $hourEnd) {
                $ended->where(function ($hourly) use ($today, $now, $checkInDate, $hourEnd) {
                    $hourly->where('stay_type', 'hour')->where(function ($done) use ($today, $now, $checkInDate, $hourEnd) {
                        $done->where(function ($withStart) use ($now, $hourEnd) {
                            $withStart->whereNotNull('hour_start')
                                ->where('hour_start', '!=', '')
                                ->whereRaw("$hourEnd <= ?", [$now]);
                        })->orWhere(function ($noStart) use ($today, $checkInDate) {
                            $noStart->where(function ($missing) {
                                $missing->whereNull('hour_start')->orWhere('hour_start', '');
                            })->whereRaw("$checkInDate < ?", [$today]);
                        });
                    });
                })->orWhere(function ($nightly) use ($today, $checkOutDate) {
                    $nightly->where(function ($notHour) {
                        $notHour->whereNull('stay_type')->orWhere('stay_type', '!=', 'hour');
                    })->whereRaw("$checkOutDate <= ?", [$today]);
                });
            });
        };

        return match ($stage) {
            'cancelled' => $query->where('status', self::STATUS_CANCELLED),
            'pending' => $query->where('status', '!=', self::STATUS_CANCELLED)->where($awaitingApproval),
            'waiting' => $query->whereIn('status', $approved)
                ->whereNot($awaitingApproval)
                ->whereRaw("$checkInDate > ?", [$today]),
            'check_out' => $query->where(function ($q) use ($approved, $awaitingApproval, $stayEnded) {
                $q->whereIn('status', [self::STATUS_CHECKED_OUT, self::STATUS_COMPLETED])
                    ->orWhere(function ($open) use ($approved, $awaitingApproval, $stayEnded) {
                        $open->whereIn('status', $approved)
                            ->whereNot($awaitingApproval)
                            ->where($stayEnded);
                    });
            }),
            'check_in' => $query->whereIn('status', $approved)
                ->whereNot($awaitingApproval)
                ->whereRaw("$checkInDate <= ?", [$today])
                ->whereNot($stayEnded),
            default => $query,
        };
    }

    /**
     * The exact time this reservation occupies.
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    public function stayWindow(): array
    {
        if ($this->isHourly() && $this->hour_start) {
            $start = Carbon::parse($this->checkInDate()->format('Y-m-d') . ' ' . $this->hour_start);
            $hours = max(1, (int) $this->hours_count);

            return [$start, $start->copy()->addHours($hours)];
        }

        return [$this->checkInDate()->copy()->startOfDay(), $this->checkOutDate()->copy()->startOfDay()];
    }

    /** Effective check-in date (falls back to legacy booking_date). */
    public function checkInDate(): Carbon
    {
        return ($this->check_in_date ?? $this->booking_date)->copy()->startOfDay();
    }

    /** Effective check-out date (legacy rows are treated as a single night). */
    public function checkOutDate(): Carbon
    {
        if ($this->check_out_date) {
            return $this->check_out_date->copy()->startOfDay();
        }

        return $this->checkInDate()->addDays(max(1, (int) $this->nights_count));
    }

    /**
     * Every calendar date the booking occupies: [check-in, check-out).
     *
     * @return array<int, string> Y-m-d strings
     */
    public function occupiedDates(): array
    {
        $dates = [];
        for ($d = $this->checkInDate(); $d->lt($this->checkOutDate()); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        return $dates ?: [$this->checkInDate()->format('Y-m-d')];
    }

    /**
     * Normalise a requested stay: returns [check_in Y-m-d, check_out Y-m-d, nights].
     * A missing or non-positive range is treated as a single night.
     */
    public static function normaliseStay($checkIn, $checkOut = null): array
    {
        $in  = Carbon::parse($checkIn)->startOfDay();
        $out = $checkOut ? Carbon::parse($checkOut)->startOfDay() : $in->copy()->addDay();

        if ($out->lte($in)) {
            $out = $in->copy()->addDay();
        }

        return [$in->format('Y-m-d'), $out->format('Y-m-d'), (int) $in->diffInDays($out)];
    }

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
            'hours_count'    => 'integer',
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
        return $this->belongsTo(AccommodationUnit::class, 'accommodation_unit_id')->withTrashed();
    }

    /**
     * The previous accommodation unit before modification (many-to-one).
     */
    public function originalAccommodationUnit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'original_accommodation_unit_id')->withTrashed();
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
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Bookings whose stay covers the given calendar date (check-out day excluded).
     */
    public function scopeOccupyingDate($query, $date)
    {
        $day = Carbon::parse($date)->format('Y-m-d');

        return $query->where(function ($q) use ($day) {
            $q->where(function ($inner) use ($day) {
                $inner->whereNotNull('check_in_date')
                      ->whereDate('check_in_date', '<=', $day)
                      ->whereDate('check_out_date', '>', $day);
            })->orWhere(function ($inner) use ($day) {
                $inner->whereNull('check_in_date')
                      ->whereDate('booking_date', '=', $day);
            });
        });
    }

    // ──────────────────────────────────────────────────────────
    // STATIC HELPERS & CONFLICT CHECKING
    // ──────────────────────────────────────────────────────────

    public static function generateReferenceNo(string $prefix = 'TBR'): string
    {
        do {
            $ref = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
        } while (self::withTrashed()->where('reference_no', $ref)->exists());

        return $ref;
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
    public static function getConflictingBooking($unitId, $checkInDate, $checkOutDate, $excludeBookingId = null, ?string $hourStart = null, ?int $hours = null): ?self
    {
        $cin = \Carbon\Carbon::parse($checkInDate)->format('Y-m-d');
        $cout = \Carbon\Carbon::parse($checkOutDate)->format('Y-m-d');
        if ($cout <= $cin) {
            $cout = \Carbon\Carbon::parse($cin)->addDay()->format('Y-m-d');
        }

        $query = self::whereIn('status', self::ACTIVE_STATUSES)
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

        if ($hourStart) {
            $requestedStart = \Carbon\Carbon::parse($cin . ' ' . $hourStart);
            $requestedEnd = $requestedStart->copy()->addHours(max(1, (int) $hours));
        } else {
            $requestedStart = \Carbon\Carbon::parse($cin)->startOfDay();
            $requestedEnd = \Carbon\Carbon::parse($cout)->startOfDay();
        }

        return $query->with('user', 'accommodationUnit')->get()->first(function (self $existing) use ($requestedStart, $requestedEnd) {
            [$start, $end] = $existing->stayWindow();

            return $start->lt($requestedEnd) && $end->gt($requestedStart);
        });
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

        $query = self::whereIn('status', self::ACTIVE_STATUSES)
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
        return self::whereIn('status', self::ACTIVE_STATUSES)
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
