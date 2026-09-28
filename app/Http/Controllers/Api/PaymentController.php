<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentReceived;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\NotificationModel;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function show(Payment $payment)
    {
        Gate::authorize('view', $payment);

        return response()->json($payment->load(['booking.accommodationUnit']));
    }

    /**
     * Guest uploads proof of a manual payment (GCash / bank transfer).
     */
    public function uploadProof(Request $request, Payment $payment)
    {
        Gate::authorize('uploadProof', $payment);

        $request->validate([
            'proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($payment->status === 'success') {
            return response()->json(['error' => 'This payment has already been verified.'], 422);
        }

        if ($payment->proof_path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->update([
            'proof_path' => $request->file('proof')->store('payments', 'public'),
            'status'     => 'pending',
        ]);

        AuditLog::log('payment_proof_uploaded', $payment);

        NotificationModel::notifyAdminsAndStaff(
            'payment',
            'Payment Proof Uploaded',
            "Proof of payment uploaded for booking {$payment->booking->reference_no} (₱" . number_format($payment->amount, 2) . ').',
            ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
        );

        return response()->json(['message' => 'Proof uploaded successfully. Awaiting verification.']);
    }

    /**
     * Stripe webhook. Signature verification is mandatory; when no secret is
     * configured the endpoint refuses every request rather than trusting input.
     */
    public function webhook(Request $request)
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        if (empty($endpointSecret)) {
            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $endpointSecret
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook rejected: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $intent  = $event->data->object ?? null;
        $payment = $intent ? Payment::where('transaction_id', $intent->id)->first() : null;

        if ($payment && $event->type === 'payment_intent.succeeded') {
            $payment->update(['status' => 'success']);
            if ($payment->booking && $payment->booking->status === Booking::STATUS_PENDING) {
                $payment->booking->update(['status' => Booking::STATUS_PAID]);
            }
            AuditLog::log('payment_completed', $payment);
            broadcast(new PaymentReceived($payment));
        } elseif ($payment && $event->type === 'payment_intent.payment_failed') {
            $payment->update(['status' => 'failed']);
            AuditLog::log('payment_failed', $payment);
        }

        return response()->json(['status' => 'ok']);
    }

    public function receipt(Booking $booking)
    {
        Gate::authorize('view', $booking);

        $booking->load(['user', 'accommodationUnit', 'payment']);

        return Pdf::loadView('pdf.receipt', compact('booking'))->download("receipt-{$booking->reference_no}.pdf");
    }
}
