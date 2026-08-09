<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageSchedule;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $query = Package::with('schedules');

        if ($request->user() && $request->user()->isTourist()) {
            $query->visible();
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(12));
    }

    public function show(Package $package)
    {
        $package->load('schedules');
        return response()->json($package);
    }
}
