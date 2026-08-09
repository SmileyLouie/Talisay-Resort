<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class WebPaymentController extends WebControllers
{
    public function index(Request $request) { return $this->paymentsIndex($request); }
    public function show(Payment $payment) { return $this->paymentShow($payment); }
    public function approvePayment(Payment $payment) { return parent::approvePayment($payment); }
    public function rejectPayment(Payment $payment) { return parent::rejectPayment($payment); }
}
