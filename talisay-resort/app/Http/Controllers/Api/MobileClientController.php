<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\GalleryPhoto;
use App\Models\PaymentMethod;
use App\Models\ResortActivity;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Models\TourAsset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileClientController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', User::uniqueActiveColumn('email')],
            'phone' => ['nullable', 'string', 'max:20', new \App\Rules\PhoneNumber],
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

    public function paymentInfo()
    {
        return response()->json([
            'gcash_name' => SystemSetting::get('gcash_name', 'Talisay Beach Resort'),
            'gcash_number' => SystemSetting::get('gcash_number', ''),
            'methods' => PaymentMethod::catalog()->map(function ($method) {
                $account = PaymentMethod::details($method);

                return [
                    'code' => $method->code,
                    'name' => $method->name,
                    'kind' => $method->kind,
                    'account_name' => $account['name'],
                    'account_number' => $account['number'],
                    'instructions' => $account['instructions'],
                ];
            })->values(),
            'activities' => ResortActivity::bookable()->map(fn ($activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'schedule' => $activity->schedule,
                'duration' => $activity->duration,
                'meeting_point' => $activity->meeting_point,
                'included' => $activity->included,
                'what_to_bring' => $activity->what_to_bring,
                'max_guests' => $activity->max_guests,
                'price' => $activity->price,
                'is_bookable' => (bool) $activity->is_bookable,
            ])->values(),
            'exclusive_nightly_rate' => app(\App\Services\BookingService::class)->exclusiveNightlyRate(),
            'down_payment_percent' => \App\Support\BookingRules::downPaymentEnabled() ? \App\Support\BookingRules::downPaymentPercent() : 0,
            'hourly_stays_enabled' => \App\Support\BookingRules::hourlyStaysEnabled(),
            'min_nights' => \App\Support\BookingRules::minNights(),
            'exclusive_title' => ($exclusiveCopy = \App\Models\Booking::exclusiveCopy())['title'],
            'exclusive_description' => $exclusiveCopy['description'],
            'exclusive_information' => $exclusiveCopy['information'],
            'visitor_cap' => \App\Models\CapacitySchedule::visitorCap(),
            'occasions' => \App\Models\Booking::occasions(),
        ]);
    }

    /** The same public site content the website shows, for the phone app. */
    public function site()
    {
        $name = setting('resort_name', 'Talisay Beach Resort');
        $address = setting('resort_address', 'Barangay Maslug, Baybay City, Leyte');
        $clock = function (?string $value, string $fallback): string {
            try {
                return Carbon::createFromFormat('H:i', (string) $value)->format('g:i A');
            } catch (\Throwable) {
                return $fallback;
            }
        };
        $tourAssets = TourAsset::active()->get();
        $tourVideo = $tourAssets->first(fn ($asset) => $asset->type === 'video');
        $videoPath = $tourVideo ? $tourVideo->mediaUrl() : (file_exists(public_path('videos/virtual-tour.mp4')) ? '/videos/virtual-tour.mp4' : null);
        $reviews = Review::with('user')->approved()->commentActive()
            ->whereNotNull('comment')->where('comment', '!=', '')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return response()->json([
            'resort' => [
                'name' => $name,
                'email' => setting('contact_email', 'info@talisayresort.com'),
                'phone' => setting('contact_phone', ''),
                'address' => $address,
                'opens' => $clock(setting('opening_time', '06:00'), '6:00 AM'),
                'closes' => $clock(setting('closing_time', '22:00'), '10:00 PM'),
                'late_refund' => (int) setting('late_cancellation_refund_percent', 50),
                'same_day' => (int) setting('same_day_cancellation_refund', 0),
            ],
            'about' => [
                'story' => $this->siteText('about_story', $name.' is a family beach resort in '.$address.'. Guests stay overnight in a room or cottage, spend a few hours by the water, and take a 360° tour of the grounds before they arrive.'),
                'vision' => $this->siteText('about_vision', 'To be the most welcoming beach destination in Baybay City, where every guest finds rest, clean water, and a warm Leyteño welcome.'),
                'mission' => $this->siteText('about_mission', 'We are committed to safe, clean, and affordable stays by the sea, honest pricing by the night or by the hour, and a booking experience that is as simple on a phone as it is at the front desk.'),
            ],
            'tour_video_url' => $this->absoluteUrl($videoPath),
            'tour_scenes' => $tourAssets->count(),
            'gallery' => GalleryPhoto::query()->orderBy('sort_order')->orderBy('id')->get()->map(fn (GalleryPhoto $photo) => [
                'title' => $photo->title,
                'caption' => $photo->caption,
                'image' => $this->absoluteUrl($photo->imageUrl()),
            ])->values(),
            'activities' => ResortActivity::catalog()->map(fn (ResortActivity $activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'schedule' => $activity->schedule,
                'duration' => $activity->duration,
                'max_guests' => $activity->max_guests,
                'price' => $activity->price,
                'is_bookable' => (bool) $activity->is_bookable,
                'image' => $this->absoluteUrl($activity->imageUrl()),
            ])->values(),
            'reviews' => $reviews->map(function (Review $review) {
                $author = trim((string) ($review->user?->name ?? ''));
                $parts = $author !== '' ? preg_split('/\s+/', $author) : [];
                $shown = $author === '' ? 'Resort guest' : $parts[0].(count($parts) > 1 ? ' '.mb_substr(end($parts), 0, 1).'.' : '');

                return [
                    'name' => $shown,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'when' => $review->created_at?->format('F Y'),
                ];
            })->values(),
            'review_count' => Review::approved()->count(),
            'average_rating' => round((float) (Review::approved()->avg('rating') ?? 0), 1),
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
                'hourly_rate' => $unit->hourlyRate(),
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
            'check_out' => ['required_unless:stay_type,hour', 'nullable', 'date', 'after:check_in'],
            'stay_type' => ['nullable', 'in:night,hour'],
            'hours_count' => ['required_if:stay_type,hour', 'nullable', 'integer', 'min:1', 'max:12'],
            'hour_start' => ['required_if:stay_type,hour', 'nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
        ]);

        $hourly = ($data['stay_type'] ?? 'night') === 'hour';
        $hours = $hourly ? (int) $data['hours_count'] : null;
        $hourStart = $hourly ? (string) $data['hour_start'] : null;
        $passed = false;

        if ($hourly) {
            $startAt = Carbon::parse(Carbon::parse($data['check_in'])->format('Y-m-d').' '.$hourStart);
            $passed = $startAt->lt(now());
            $endAt = $startAt->copy()->addHours($hours);
            $exclusive = $endAt->copy()->startOfDay();
            if ($endAt->gt($exclusive)) {
                $exclusive->addDay();
            }
            $checkIn = $startAt->format('Y-m-d');
            $checkOut = $exclusive->format('Y-m-d');
            $nights = 0;
            $total = round($unit->hourlyRate() * $hours, 2);
            $conflict = $passed ? null : Booking::getConflictingBooking($unit->id, $checkIn, $checkOut, null, $hourStart, $hours);
        } else {
            [$checkIn, $checkOut, $nights] = Booking::normaliseStay($data['check_in'], $data['check_out']);
            $total = (float) $unit->price_per_night * $nights;
            $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut);
        }

        $open = (bool) $unit->is_available && $conflict === null && ! $passed;

        $booked = collect(Booking::getBookedDateRangesForUnit($unit->id))
            ->map(fn (array $range) => [
                'check_in' => $range['check_in'],
                'check_out' => $range['check_out'],
            ])
            ->values();

        return response()->json([
            'available' => $open,
            'nights' => $nights,
            'hours' => $hours,
            'price_per_night' => (float) $unit->price_per_night,
            'price_per_hour' => $unit->hourlyRate(),
            'total_amount' => $total,
            'message' => $open
                ? ($hourly ? 'That time is open.' : 'These dates are open.')
                : ($passed
                    ? 'Choose a start time that has not already passed.'
                    : ($unit->is_available
                        ? ($hourly ? $unit->unit_number.' is already booked for that time.' : $unit->unit_number.' is already booked on these dates.')
                        : 'This stay is not open for booking.')),
            'booked' => $booked,
        ]);
    }

    private function siteText(string $key, string $default): string
    {
        $value = trim((string) setting($key, ''));

        return $value !== '' ? $value : $default;
    }

    private function absoluteUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url(ltrim($path, '/'));
    }
}
