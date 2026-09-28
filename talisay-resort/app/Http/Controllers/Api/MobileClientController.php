<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileClientController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'tourist',
            'is_active' => true,
            'account_status' => 'active',
        ]);

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !$user->isTourist() || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match a guest account.'],
            ]);
        }

        if ($user->is_active === false || in_array($user->account_status, ['inactive', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'email' => ['This guest account cannot sign in.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function units()
    {
        $units = AccommodationUnit::orderBy('unit_type')->orderBy('sort_order')->orderBy('unit_number')->get()
            ->map(fn (AccommodationUnit $unit) => [
                'id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'unit_type' => $unit->unit_type,
                'variant' => $unit->variant,
                'max_occupancy' => $unit->max_occupancy,
                'price_per_night' => $unit->price_per_night,
                'description' => $unit->description,
                'amenities' => $unit->amenities ?? [],
                'floor_area_sqm' => $unit->floor_area_sqm,
                'bed_configuration' => $unit->bed_configuration,
                'is_available' => (bool) $unit->is_available,
                'images' => collect($unit->images ?? [])->map(fn ($path) => url('storage/'.$path))->values(),
                'tour_video_url' => $unit->tour_video_path ? url('storage/'.$unit->tour_video_path) : null,
            ]);

        return response()->json(['units' => $units]);
    }

    public function stay(Request $request, AccommodationUnit $unit)
    {
        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        [$checkIn, $checkOut, $nights] = Booking::normaliseStay($data['check_in'], $data['check_out']);
        $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut);
        $open = (bool) $unit->is_available && $conflict === null;

        $booked = collect(Booking::getBookedDateRangesForUnit($unit->id))
            ->map(fn (array $range) => [
                'check_in' => $range['check_in'],
                'check_out' => $range['check_out'],
            ])
            ->values();

        return response()->json([
            'available' => $open,
            'nights' => $nights,
            'price_per_night' => (float) $unit->price_per_night,
            'total_amount' => (float) $unit->price_per_night * $nights,
            'message' => $open
                ? 'These dates are open.'
                : ($unit->is_available
                    ? $unit->unit_number.' is already booked on these dates.'
                    : 'This stay is not open for booking.'),
            'booked' => $booked,
        ]);
    }
}
