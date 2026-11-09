<?php

namespace App\Http\Controllers;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\GalleryPhoto;
use App\Models\ResortActivity;
use App\Models\Review;
use App\Models\TourAsset;
use App\Support\PortalSites;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Public guest-facing pages: home, about, rooms, cottages, unit details, reviews, contact.
 * Nobody has to be logged in to read these. Booking links lead to the client log in.
 */
class PublicPagesController extends Controller
{
    public function home()
    {
        // Administrators and staff still open their own dashboards.
        // A signed-in guest stays on this page; it is the client home.
        if (auth()->check()) {
            $user = auth()->user();
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            if (method_exists($user, 'isStaff') && $user->isStaff()) {
                return redirect()->route('staff.dashboard');
            }
        }

        // Port 8001 keeps the original welcome screen for administrators and staff.
        // Port 8000 keeps the guest website.
        if (PortalSites::current() === 'staff') {
            return view('admin-welcome');
        }

        $units = $this->units();
        $tourAssets = TourAsset::active()->get();
        $tourVideo = $tourAssets->firstWhere('type', 'video');

        // Hero slideshow: the landing photo and every unit photo, then short clips of the
        // tour video and the unit videos so the slideshow still moves when photos are few.
        // 360° panoramas are left out because they look stretched as a flat background.
        $heroSlides = collect(['/images/hero-landing.jpg'])
            ->merge($units->flatMap(fn ($unit) => collect($unit->images ?? [])->map(fn ($p) => $this->storageUrl($p))))
            ->filter()->unique()->values()
            ->map(fn ($src) => ['type' => 'image', 'src' => $src]);
        $heroClips = collect($tourVideo ? [$tourVideo->mediaUrl()] : [])
            ->merge($units->pluck('tour_video_path')->filter()->map(fn ($p) => $this->storageUrl($p)))
            ->filter()->unique()->take(4)->values()
            ->map(fn ($src) => ['type' => 'video', 'src' => $src]);

        return view('landing', $this->shared('home') + [
            'roomUnits'      => $units->where('unit_type', 'room')->values(),
            'cottageUnits'   => $units->where('unit_type', 'cottage')->values(),
            'heroSlides'     => $heroSlides->concat($heroClips)->values(),
            'tourVideoUrl'   => $this->tourVideoUrl($tourVideo),
            'tourVideoTitle' => $tourVideo->title ?? 'Talisay Beach Resort',
            'landingReviews' => $this->publicReviews()->take(12)->get(),
            'averageRating'  => $this->averageRating(),
            'reviewCount'    => Review::approved()->count(),
            'activities'     => ResortActivity::catalog(),
        ]);
    }

    public function about()
    {
        $units = $this->units();
        $tourAssets = TourAsset::active()->get();
        $tourVideo = $tourAssets->firstWhere('type', 'video');

        return view('public.about', $this->shared('about') + [
            'bannerImage'   => '/images/hero-landing.jpg',
            'galleryPhotos' => GalleryPhoto::query()->orderBy('sort_order')->orderBy('id')->get(),
            'tourScenes'   => $tourAssets,
            'tourVideoUrl' => $this->tourVideoUrl($tourVideo),
            'roomCount'    => $units->where('unit_type', 'room')->count(),
            'cottageCount' => $units->where('unit_type', 'cottage')->count(),
            'activities'   => ResortActivity::catalog(),
            'about'        => [
                'story'   => $this->text('about_story', setting('resort_name', 'Talisay Beach Resort').' is a family beach resort in '.setting('resort_address', 'Barangay Maslug, Baybay City, Leyte').'. Guests stay overnight in a room or cottage, spend a few hours by the water, and take a 360° tour of the grounds before they arrive.'),
                'vision'  => $this->text('about_vision', 'To be the most welcoming beach destination in Baybay City, where every guest finds rest, clean water, and a warm Leyteño welcome.'),
                'mission' => $this->text('about_mission', 'We are committed to safe, clean, and affordable stays by the sea, honest pricing by the night or by the hour, and a booking experience that is as simple on a phone as it is at the front desk.'),
            ],
        ]);
    }

    public function rooms()
    {
        return $this->unitList('room');
    }

    public function cottages()
    {
        return $this->unitList('cottage');
    }

    public function activities()
    {
        return view('public.activities', $this->shared('activities') + [
            'activities' => ResortActivity::catalog(),
            'bannerImage' => '/images/hero-landing.jpg',
        ]);
    }

    public function unit(AccommodationUnit $unit)
    {
        abort_unless($unit->is_available, 404);

        $similar = AccommodationUnit::available()
            ->where('id', '!=', $unit->id)
            ->where('unit_type', $unit->unit_type)
            ->orderBy('sort_order')->orderBy('unit_number')
            ->take(3)->get();

        $reviews = $this->publicReviews()
            ->whereHas('booking', fn ($q) => $q->where('accommodation_unit_id', $unit->id))
            ->take(6)->get();

        return view('public.unit', $this->shared($unit->unit_type === 'room' ? 'rooms' : 'cottages') + [
            'unit'    => $unit,
            'similar' => $similar,
            'reviews' => $reviews,
            'bookUrl' => route('public.booking', ['unit' => $unit->id]),
        ]);
    }

    public function reviews(Request $request)
    {
        $rating = (int) $request->query('rating', 0);
        $query = $this->publicReviews();
        if ($rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }

        $counts = Review::approved()->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        return view('public.reviews', $this->shared('reviews') + [
            'reviews'       => $query->paginate(12)->withQueryString(),
            'rating'        => $rating,
            'averageRating' => $this->averageRating(),
            'reviewCount'   => Review::approved()->count(),
            'ratingCounts'  => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($r) => [$r => (int) ($counts[$r] ?? 0)]),
        ]);
    }

    public function booking(Request $request)
    {
        $units = $this->units()->map(function ($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->unit_number,
                'type' => $unit->type_label,
                'variant' => $unit->variant_label,
                'deluxe' => $unit->isDeluxeVariant(),
                'kind' => $unit->unit_type,
                'occupancy' => (int) $unit->max_occupancy,
                'beds' => $unit->bed_configuration ?: '',
                'night' => (float) $unit->price_per_night,
                'hour' => $unit->hourlyRate(),
                'image' => $unit->first_image ? $this->storageUrl($unit->first_image) : null,
                'ranges' => Booking::getBookedDateRangesForUnit($unit->id),
            ];
        })->values();

        $user = $request->user();
        $exclusive = Booking::exclusiveCopy();

        return view('public.booking', $this->shared('booking') + [
            'navSolid' => true,
            'bookableActivities' => \App\Models\ResortActivity::bookable(),
            'exclusiveNightlyRate' => app(\App\Services\BookingService::class)->exclusiveNightlyRate(),
            'exclusiveTitle' => $exclusive['title'],
            'exclusiveInformation' => $exclusive['information'],
            'visitorCap' => CapacitySchedule::visitorCap(),
            'bookingUnits' => $units,
            'guestReady' => $user && method_exists($user, 'isTourist') && $user->isTourist(),
            'doneRef' => $request->query('done'),
            'resumeUrl' => route('public.booking.resume'),
        ]);
    }

    public function exclusive(Request $request)
    {
        $units = $this->units();
        $checkIn = null;
        $checkOut = null;
        $nights = 0;
        $blocked = false;
        $dateMessage = 'Choose your dates on the booking page first.';

        try {
            if ($request->filled('check_in') && $request->filled('check_out')) {
                $in = Carbon::parse($request->query('check_in'))->startOfDay();
                $out = Carbon::parse($request->query('check_out'))->startOfDay();
                if ($in->lt(Carbon::today())) {
                    $dateMessage = 'Choose a check-in date that is today or later.';
                } elseif ($out->lte($in)) {
                    $dateMessage = 'Check-out has to be after check-in.';
                } else {
                    $checkIn = $in->toDateString();
                    $checkOut = $out->toDateString();
                    $nights = (int) $in->diffInDays($out);
                    $blocked = Booking::hasSpecialResortConflict($checkIn, $checkOut);
                    $dateMessage = null;
                }
            }
        } catch (\Throwable) {
            $dateMessage = 'Those dates could not be read. Go back and choose them again.';
        }

        $nightSum = app(\App\Services\BookingService::class)->exclusiveNightlyRate();
        $user = $request->user();
        $visitorCap = CapacitySchedule::visitorCap();

        return view('public.exclusive', $this->shared('booking') + [
            'navSolid' => true,
            'units' => $units,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'nights' => $nights,
            'guests' => max(1, min($visitorCap, (int) $request->query('guests', old('guests_count', 2)))),
            'nightSum' => $nightSum,
            'total' => round($nightSum * $nights, 2),
            'capacity' => $visitorCap,
            'visitorCap' => $visitorCap,
            'exclusive' => Booking::exclusiveCopy(),
            'blocked' => $blocked,
            'dateMessage' => $dateMessage,
            'guestReady' => $user && method_exists($user, 'isTourist') && $user->isTourist(),
            'doneRef' => $request->query('done'),
            'occasion' => (string) $request->query('occasion', old('occasion', '')),
            'bookableActivities' => \App\Models\ResortActivity::bookable(),
            'eventNote' => (string) $request->query('note', ''),
        ]);
    }

    public function contact()
    {
        return view('public.contact', $this->shared('contact') + [
            'allUnits' => $this->units(),
        ]);
    }

    // ───────────────────────────────────────────────────────────────

    private function unitList(string $type)
    {
        $units = AccommodationUnit::available()->where('unit_type', $type)
            ->orderBy('sort_order')->orderBy('unit_number')->get();

        $isRoom = $type === 'room';

        return view('public.units', $this->shared($isRoom ? 'rooms' : 'cottages') + [
            'units'       => $units,
            'unitType'    => $type,
            'pageTitle'   => $isRoom ? 'Our Rooms' : 'Our Cottages',
            'pageLabel'   => $isRoom ? 'Time to reconnect' : 'By the water',
            'pageIntro'   => $isRoom
                ? 'Clean, quiet rooms a short walk from the sand. Every room lists its rate, occupancy, size, and amenities, with a 360° look inside before you book.'
                : 'Open-air cottages for families and groups right on the beachfront. Book a nightly stay or a few hours of shade and sea.',
            'bannerImage' => '/images/hero-landing.jpg',
        ]);
    }

    /** Values every public page needs: nav state, resort contact details, and the unit list for forms. */
    private function shared(string $navActive): array
    {
        $clock = function (?string $value, string $fallback): string {
            try {
                return Carbon::createFromFormat('H:i', (string) $value)->format('g:i A');
            } catch (\Throwable) {
                return $fallback;
            }
        };

        return [
            'navActive'  => $navActive,
            'resortInfo' => [
                'name'        => setting('resort_name', 'Talisay Beach Resort'),
                'email'       => setting('contact_email', 'info@talisayresort.com'),
                'phone'       => setting('contact_phone', ''),
                'address'     => setting('resort_address', 'Barangay Maslug, Baybay City, Leyte'),
                'opens'       => $clock(setting('opening_time', '06:00'), '6:00 AM'),
                'closes'      => $clock(setting('closing_time', '22:00'), '10:00 PM'),
                'late_refund' => (int) setting('late_cancellation_refund_percent', 50),
                'same_day'    => (int) setting('same_day_cancellation_refund', 0),
                'gcash_name'  => setting('gcash_name', 'Talisay Beach Resort'),
            ],
        ];
    }

    private function units()
    {
        return AccommodationUnit::available()->orderBy('sort_order')->orderBy('unit_number')->get();
    }

    private function publicReviews()
    {
        return Review::with(['user', 'booking.accommodationUnit'])->approved()->commentActive()
            ->whereNotNull('comment')->where('comment', '!=', '')
            ->orderByDesc('created_at');
    }

    private function averageRating(): float
    {
        return round((float) (Review::approved()->avg('rating') ?? 0), 1);
    }

    private function tourVideoUrl(?TourAsset $tourVideo): ?string
    {
        if ($tourVideo) {
            return $tourVideo->mediaUrl();
        }

        return file_exists(public_path('videos/virtual-tour.mp4')) ? '/videos/virtual-tour.mp4' : null;
    }

    /** A text setting, falling back to the default when the admin left it blank. */
    private function text(string $key, string $default): string
    {
        $value = trim((string) setting($key, ''));

        return $value !== '' ? $value : $default;
    }

    private function storageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }
}
