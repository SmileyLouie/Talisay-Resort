<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['booking.user', 'booking.package']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }
        if ($request->has('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function process(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'gateway' => 'required|in:stripe,cash,bank_transfer',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['error' => 'Booking is not in pending status'], 422);
        }

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
            'gateway' => $request->gateway,
            'status' => 'pending',
        ]);

        if ($request->gateway === 'stripe') {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                $stripePaymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => (int)($booking->total_amount * 100),
                    'currency' => 'php',
                    'metadata' => [
                        'booking_id' => $booking->id,
                        'reference_no' => $booking->reference_no,
                    ],
                ]);

                $payment->update([
                    'transaction_id' => $stripePaymentIntent->id,
                    'metadata' => ['stripe_client_secret' => $stripePaymentIntent->client_secret],
                ]);

                return response()->json([
                    'payment' => $payment,
                    'client_secret' => $stripePaymentIntent->client_secret,
                    'message' => 'Payment intent created.',
                ]);
            } catch (\Exception $e) {
                $payment->update(['status' => 'failed']);
                return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 500);
            }
        }

        return response()->json([
            'payment' => $payment,
            'message' => 'Payment recorded. Please upload proof of payment.',
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['booking.user', 'booking.package']);
        return response()->json($payment);
    }

    public function uploadProof(Request $request, Payment $payment)
    {
        $request->validate([
            'proof' => 'required|image|max:5120',
        ]);

        $path = $request->file('proof')->store('payment_proofs', 'public');

        $payment->update([
            'proof_path' => $path,
            'status' => 'pending',
        ]);

        AuditLog::log('payment_proof_uploaded', $payment);

        return response()->json(['message' => 'Proof uploaded successfully. Awaiting verification.']);
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $payment = Payment::where('transaction_id', $paymentIntent->id)->first();

            if ($payment) {
                $payment->update(['status' => 'success']);
                $payment->booking->update(['status' => 'paid']);
                AuditLog::log('payment_completed', $payment);
                broadcast(new \App\Events\PaymentReceived($payment));
            }
        } elseif ($event->type === 'payment_intent.payment_failed') {
            $paymentIntent = $event->data->object;
            $payment = Payment::where('transaction_id', $paymentIntent->id)->first();

            if ($payment) {
                $payment->update(['status' => 'failed']);
                AuditLog::log('payment_failed', $payment);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function generateReceipt(Booking $booking)
    {
        $booking->load(['user', 'package', 'payment']);
        $pdf = Pdf::loadView('pdf.receipt', compact('booking'));

        return $pdf->download("receipt-{$booking->reference_no}.pdf");
    }
}
