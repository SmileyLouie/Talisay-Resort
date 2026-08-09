<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class WebPackageController extends WebControllers
{
    public function index() { return $this->packagesIndex(); }
    public function store(Request $request) { return $this->packageStore($request); }
    public function update(Request $request, Package $package) { return $this->packageUpdate($request, $package); }
    public function toggleVisibility(Package $package) { return $this->packageToggleVisibility($package); }
    public function destroy(Package $package) { return $this->packageDestroy($package); }
}
