<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class WebUserController extends WebControllers
{
    public function index(Request $request) { return $this->usersIndex($request); }
    public function create() { return $this->userCreate(); }
    public function show(User $user) { return $this->userShow($user); }
    public function store(Request $request) { return $this->userStore($request); }
    public function edit(User $user) { return $this->userEdit($user); }
    public function update(Request $request, User $user) { return $this->userUpdate($request, $user); }
    public function destroy(User $user) { return $this->userDestroy($user); }
    public function toggleStatus(User $user) { return $this->userToggleStatus($user); }
    public function adminResetPassword(Request $request, User $user) { return $this->userResetPassword($request, $user); }
}
