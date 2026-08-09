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
    'booking_id',      // FK — the booking this payment is for
    'amount',          // Amount paid in Philippine Pesos
    'gateway',         // Payment method: 'stripe' or 'manual'
    'transaction_id',  // Stripe charge ID or manual reference number (nullable)
    'status',          // Payment lifecycle: pending|success|failed|refunded
    'proof_path',      // Storage path to uploaded payment proof image (for manual payments)
    'metadata',        // JSON blob for extra data (Stripe response, refund info, etc.)
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
            'metadata' => 'array',
        ];
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
        // Each payment record belongs to exactly one booking
        return $this->belongsTo(Booking::class);
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
}
