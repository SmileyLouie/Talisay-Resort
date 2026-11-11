<?php

// ============================================================
// Payment.php — Eloquent Model for the 'payments' Table
// ============================================================
// Tracks all payment transactions linked to bookings.
// Supports multiple gateways: Stripe (online) and manual (cash/GCash).
// Statuses: pending, success, failed, refunded.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for testing and seeding
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model
use Illuminate\Database\Eloquent\Model;

// Declare mass-assignable columns (protects against mass-assignment attacks)
#[Fillable([
    'booking_id',          // FK — the booking this payment is for
    'amount',              // Amount paid in Philippine Pesos
    'gateway',             // Payment method: 'gcash', 'paypal', 'card', 'cash', etc.
    'payment_channel',     // 'gcash', 'paypal', 'card', 'cash'
    'transaction_id',      // Reference number (nullable)
    'paypal_order_id',     // PayPal Order ID (nullable)
    'card_last_four',      // Last 4 digits of card (nullable)
    'is_cash_on_arrival',  // Boolean flag for cash payments
    'status',              // Payment lifecycle: pending|success|failed|refunded
    'paid_at',             // When the resort confirmed the money (set automatically on success)
    'proof_path',          // Storage path to uploaded payment proof image (for manual payments)
    'metadata',            // JSON blob for extra data
])]

/**
 * Class Payment
 *
 * Represents a single payment transaction for a resort booking.
 *
 * @property int         $id             Auto-incrementing primary key
 * @property int         $booking_id     FK to the associated booking
 * @property float       $amount         Amount paid in PHP
 * @property string      $gateway        'stripe' or 'manual'
 * @property string|null $transaction_id External transaction identifier
 * @property string      $status         Payment status: pending|success|failed|refunded
 * @property string|null $proof_path     Path to proof-of-payment image for manual payments
 * @property array|null  $metadata       Additional payment metadata (JSON)
 */
class Payment extends Model
{
    // Enable factory support for generating test/seed data
    use HasFactory;

    /**
     * Define column type casts for automatic transformation.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast amount to 2-decimal-place float to prevent floating-point errors
            'amount'   => 'decimal:2',

            // Cast metadata JSON string to PHP array automatically
            // Allows: $payment->metadata['currency'] or $payment->metadata['paid_at']
            'metadata'            => 'array',
            'is_cash_on_arrival'  => 'boolean',
            'paid_at'             => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $payment) {
            if ($payment->status === 'success' && ! $payment->paid_at && ($payment->isDirty('status') || ! $payment->exists)) {
                $payment->paid_at = now();
            } elseif ($payment->status === 'pending' && $payment->isDirty('status')) {
                $payment->paid_at = null;
            }
        });
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The booking that this payment is associated with (many-to-one).
     * Foreign key: payments.booking_id → bookings.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking()
    {
        // Each payment record belongs to exactly one booking.
        return $this->belongsTo(Booking::class);
    }

    public function guestReference(): ?string
    {
        $reference = trim((string) $this->transaction_id);
        if ($reference === '' || preg_match('/^(TXN|CASH|MNL)-/', $reference)) {
            return null;
        }

        return $reference;
    }

    public function proofUrl(): ?string
    {
        if (!$this->proof_path) {
            return null;
        }

        return '/storage/' . ltrim($this->proof_path, '/');
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter payments with 'success' status.
     * Used for revenue calculations and completed transaction reports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        // Add WHERE status = 'success' to filter only confirmed payments
        return $query->where('status', 'success');
    }

    /**
     * Scope: Filter payments with 'refunded' status.
     * Used for tracking cancelled bookings that received refunds.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRefunded($query)
    {
        // Add WHERE status = 'refunded' to filter refunded payment records
        return $query->where('status', 'refunded');
    }

    /** What the guest originally paid, before any cancellation or refund changed `amount`. */
    public function paidAmount(): float
    {
        return round((float) ($this->metadata['paid_amount'] ?? $this->amount), 2);
    }

    /** @return array{amount: float, reference: ?string, note: ?string, by: ?string, by_id: ?int, at: string}|null */
    public function refundSent(): ?array
    {
        return $this->metadata['refund_sent'] ?? null;
    }

    /**
     * Where this payment stands on refunds:
     *  - 'sent'      the resort recorded returning money to the guest,
     *  - 'due'       the booking was cancelled and the policy owes the guest money,
     *  - 'available' the guest may have paid (declined with a receipt, or a cancelled stay
     *                the policy keeps), so staff can still return money if they choose,
     *  - null        nothing to refund.
     */
    public function refundState(): ?string
    {
        if ($this->refundSent()) {
            return 'sent';
        }

        if ($this->status === 'failed') {
            return ($this->proof_path || $this->guestReference()) ? 'available' : null;
        }

        $cancelled = $this->booking && $this->booking->status === Booking::STATUS_CANCELLED;
        if ($cancelled && in_array($this->status, ['success', 'refunded'], true)) {
            return (float) ($this->metadata['refund_amount'] ?? 0) > 0 ? 'due' : 'available';
        }

        return null;
    }

    public function suggestedRefund(): float
    {
        return match ($this->refundState()) {
            'due' => round((float) ($this->metadata['refund_amount'] ?? 0), 2),
            'available' => $this->status === 'failed' ? $this->paidAmount() : 0.0,
            default => 0.0,
        };
    }

    /** @return array<int, array{amount: float, method: ?string, reference: ?string, by: ?string, at: string}> */
    public function balancePayments(): array
    {
        return $this->metadata['balance_payments'] ?? [];
    }

    /**
     * Money the resort received on this payment, each part on the day it came in:
     * the first payment when it was confirmed, then any balance collected later.
     *
     * @return array<int, array{0: \Carbon\Carbon, 1: float}>
     */
    public function receipts(): array
    {
        if ($this->status !== 'success') {
            return [];
        }

        $later = collect($this->balancePayments());
        $firstAt = ($this->paid_at ?? $this->created_at)->copy();
        $first = round((float) $this->amount - (float) $later->sum('amount'), 2);

        if ($first < 0) {
            return [[$firstAt, round((float) $this->amount, 2)]];
        }

        $rows = $first > 0 ? [[$firstAt, $first]] : [];
        foreach ($later as $part) {
            $rows[] = [\Carbon\Carbon::parse($part['at'])->timezone(config('app.timezone')), round((float) $part['amount'], 2)];
        }

        return $rows;
    }

    /**
     * Every receipt dated between $from and $to (either may be null for an open range).
     *
     * @return \Illuminate\Support\Collection<int, array{0: \Carbon\Carbon, 1: float}>
     */
    public static function receiptsBetween($from = null, $to = null)
    {
        return static::where('status', 'success')->get()
            ->flatMap(fn (self $payment) => $payment->receipts())
            ->filter(fn (array $row) => (! $from || $row[0]->gte($from)) && (! $to || $row[0]->lte($to)))
            ->values();
    }

    public static function revenueBetween($from = null, $to = null): float
    {
        return round(static::receiptsBetween($from, $to)->sum(fn (array $row) => $row[1]), 2);
    }

    /**
     * Money the resort has to return: full refunds plus partial refunds
     * recorded on a payment the resort still partly keeps.
     */
    public static function refundedTotal(): float
    {
        $full = (float) static::where('status', 'refunded')->sum('amount');
        $partial = (float) static::where('status', 'success')->get()
            ->sum(fn (self $payment) => (float) ($payment->metadata['refund_amount'] ?? 0));

        return round($full + $partial, 2);
    }
}
