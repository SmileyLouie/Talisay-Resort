<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebProfileController extends WebControllers
{
    public function show() { return $this->profileShow(); }
    public function update(Request $request) { return $this->profileUpdate($request); }
    public function updatePassword(Request $request) { return parent::updatePassword($request); }
    public function destroyAvatar(Request $request) { return $this->profileDestroyAvatar($request); }
}
