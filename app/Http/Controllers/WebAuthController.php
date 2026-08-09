<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebAuthController extends WebControllers
{
    public function showLogin() { return parent::showLogin(); }
    public function login(Request $request) { return parent::login($request); }
    public function showRegister() { return parent::showRegister(); }
    public function register(Request $request) { return parent::register($request); }
    public function logout(Request $request) { return parent::logout($request); }
    public function showForgotPassword() { return parent::showForgotPassword(); }
    public function sendResetLink(Request $request) { return parent::sendResetLink($request); }
    public function showResetPassword(Request $request) { return parent::showResetPassword($request); }
    public function resetPassword(Request $request) { return parent::resetPassword($request); }
}
