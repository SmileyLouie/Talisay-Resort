<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use App\Models\Emergency;
use App\Models\MemoryTimeline;
use App\Models\MemoryTimelineItem;
use App\Models\NotificationModel;
use App\Models\Package;
use App\Models\PackageSchedule;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Models\TourAsset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Users ───────────────────────────────────────────────
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@talisayresort.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+63-917-000-0001',
            'is_active' => true,
        ]);

        $staff1 = User::create([
            'name' => 'Maria Santos',
            'email' => 'staff1@talisayresort.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'staff',
            'phone' => '+63-917-000-0002',
            'is_active' => true,
        ]);

        $staff2 = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'staff2@talisayresort.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'staff',
            'phone' => '+63-917-000-0003',
            'is_active' => true,
        ]);

        $tourists = [];
        $touristData = [
            ['name' => 'Ana Reyes', 'email' => 'ana.reyes@email.com', 'phone' => '+63-918-111-0001'],
            ['name' => 'Carlos Garcia', 'email' => 'carlos.garcia@email.com', 'phone' => '+63-918-111-0002'],
            ['name' => 'Sofia Lim', 'email' => 'sofia.lim@email.com', 'phone' => '+63-918-111-0003'],
            ['name' => 'Diego Ramos', 'email' => 'diego.ramos@email.com', 'phone' => '+63-918-111-0004'],
            ['name' => 'Isabella Torres', 'email' => 'isabella.torres@email.com', 'phone' => '+63-918-111-0005'],
        ];

        foreach ($touristData as $data) {
            $tourists[] = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'tourist',
                'phone' => $data['phone'],
                'is_active' => true,
            ]);
        }

        // ─── Packages ────────────────────────────────────────────
        $packages = [];

        $packages[] = Package::create([
            'name' => 'Day Tour Package',
            'description' => 'Enjoy a full day of sun, sand, and sea at Talisay Beach. Includes access to all beach facilities, cottage use, and a complimentary welcome drink. Perfect for families and friends looking for a quick beach getaway.',
            'price' => 500.00,
            'max_capacity' => 50,
            'images' => ['packages/day-tour-1.jpg', 'packages/day-tour-2.jpg'],
            'is_visible' => true,
            'seasonal_pricing' => [
                ['season' => 'Peak (March-May)', 'markup_percent' => 20],
                ['season' => 'Lean (Nov-Feb)', 'discount_percent' => 10],
            ],
        ]);

        $packages[] = Package::create([
            'name' => 'Overnight Cottage Stay',
            'description' => 'Stay overnight in our cozy beachfront cottages with a stunning view of the Camotes Sea. Includes dinner and breakfast for two, bonfire access, and morning snorkeling gear rental.',
            'price' => 1500.00,
            'max_capacity' => 20,
            'images' => ['packages/overnight-1.jpg', 'packages/overnight-2.jpg'],
            'is_visible' => true,
            'seasonal_pricing' => [
                ['season' => 'Peak (March-May)', 'markup_percent' => 25],
                ['season' => 'Lean (Nov-Feb)', 'discount_percent' => 15],
            ],
        ]);

        $packages[] = Package::create([
            'name' => 'Beachfront Cabin Suite',
            'description' => 'Indulge in our premium beachfront cabin with air conditioning, private veranda, and direct beach access. Includes full-board meals for two, kayak rental, and a sunset cruise.',
            'price' => 3500.00,
            'max_capacity' => 10,
            'images' => ['packages/cabin-1.jpg'],
            'is_visible' => true,
            'seasonal_pricing' => [
                ['season' => 'Peak (March-May)', 'markup_percent' => 30],
            ],
        ]);

        $packages[] = Package::create([
            'name' => 'Group Adventure Package',
            'description' => 'Designed for groups of 5-15 people. Includes beach games, island hopping, snorkeling adventure, grilled lunch by the shore, and a group photo session. Minimum 5 guests required.',
            'price' => 800.00,
            'max_capacity' => 30,
            'images' => ['packages/group-1.jpg', 'packages/group-2.jpg'],
            'is_visible' => true,
            'seasonal_pricing' => [
                ['season' => 'Peak (March-May)', 'markup_percent' => 15],
            ],
        ]);

        $packages[] = Package::create([
            'name' => 'Snorkeling Adventure Add-on',
            'description' => 'Add this to any package for a guided snorkeling experience at the Talisay coral reef. Includes mask, snorkel, fins, and a certified guide. Available daily from 9AM to 4PM.',
            'price' => 350.00,
            'max_capacity' => 15,
            'images' => ['packages/snorkel-1.jpg'],
            'is_visible' => true,
            'seasonal_pricing' => null,
        ]);

        $packages[] = Package::create([
            'name' => 'Kayak Rental',
            'description' => 'Explore the calm waters of Talisay Bay on a single or double kayak. Life jackets and brief orientation included. Available in 1-hour or half-day rentals.',
            'price' => 250.00,
            'max_capacity' => 20,
            'images' => ['packages/kayak-1.jpg'],
            'is_visible' => true,
            'seasonal_pricing' => null,
        ]);

        // ─── Package Schedules ────────────────────────────────────
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($packages as $pkg) {
            // Day Tour: Open daily
            if ($pkg->name === 'Day Tour Package') {
                foreach ($days as $day) {
                    PackageSchedule::create([
                        'package_id' => $pkg->id,
                        'day_of_week' => $day,
                        'start_time' => '08:00:00',
                        'end_time' => '17:00:00',
                        'available_slots' => 50,
                    ]);
                }
            }
            // Overnight: Open Thu-Sun
            elseif ($pkg->name === 'Overnight Cottage Stay') {
                foreach (['thursday', 'friday', 'saturday', 'sunday'] as $day) {
                    PackageSchedule::create([
                        'package_id' => $pkg->id,
                        'day_of_week' => $day,
                        'start_time' => '14:00:00',
                        'end_time' => '11:00:00',
                        'available_slots' => 10,
                    ]);
                }
            }
            // Cabin Suite: Open daily
            elseif ($pkg->name === 'Beachfront Cabin Suite') {
                foreach ($days as $day) {
                    PackageSchedule::create([
                        'package_id' => $pkg->id,
                        'day_of_week' => $day,
                        'start_time' => '14:00:00',
                        'end_time' => '12:00:00',
                        'available_slots' => 5,
                    ]);
                }
            }
            // Group Adventure: Weekends only
            elseif ($pkg->name === 'Group Adventure Package') {
                foreach (['saturday', 'sunday'] as $day) {
                    PackageSchedule::create([
                        'package_id' => $pkg->id,
                        'day_of_week' => $day,
                        'start_time' => '09:00:00',
                        'end_time' => '16:00:00',
                        'available_slots' => 30,
                    ]);
                }
            }
            // Add-ons: Open daily
            else {
                foreach ($days as $day) {
                    PackageSchedule::create([
                        'package_id' => $pkg->id,
                        'day_of_week' => $day,
                        'start_time' => '09:00:00',
                        'end_time' => '16:00:00',
                        'available_slots' => $pkg->max_capacity,
                    ]);
                }
            }
        }

        // ─── Capacity Schedules (next 14 days) ───────────────────
        for ($i = 0; $i < 14; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            CapacitySchedule::create([
                'date' => $date,
                'max_capacity' => 100,
                'current_count' => rand(10, 65),
            ]);
        }
        // Past 7 days for chart data
        for ($i = 7; $i >= 1; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            CapacitySchedule::create([
                'date' => $date,
                'max_capacity' => 100,
                'current_count' => rand(25, 80),
            ]);
        }

        // ─── Bookings ────────────────────────────────────────────
        $statuses = ['pending', 'paid', 'checked_in', 'completed', 'cancelled'];
        $bookings = [];

        // Create bookings spread across past and future dates
        $bookingData = [
            // Past completed bookings
            ['tourist_idx' => 0, 'pkg_idx' => 0, 'days_offset' => -14, 'guests' => 2, 'status' => 'completed', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 1, 'pkg_idx' => 1, 'days_offset' => -12, 'guests' => 2, 'status' => 'completed', 'time' => '02:00 PM - 11:00 AM'],
            ['tourist_idx' => 2, 'pkg_idx' => 0, 'days_offset' => -10, 'guests' => 4, 'status' => 'completed', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 3, 'pkg_idx' => 2, 'days_offset' => -8, 'guests' => 2, 'status' => 'completed', 'time' => '02:00 PM - 12:00 PM'],
            ['tourist_idx' => 4, 'pkg_idx' => 3, 'days_offset' => -7, 'guests' => 8, 'status' => 'completed', 'time' => '09:00 AM - 04:00 PM'],
            ['tourist_idx' => 0, 'pkg_idx' => 4, 'days_offset' => -7, 'guests' => 2, 'status' => 'completed', 'time' => '10:00 AM - 02:00 PM'],
            // Recent/past with different statuses
            ['tourist_idx' => 1, 'pkg_idx' => 0, 'days_offset' => -3, 'guests' => 3, 'status' => 'completed', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 2, 'pkg_idx' => 5, 'days_offset' => -2, 'guests' => 1, 'status' => 'completed', 'time' => '09:00 AM - 01:00 PM'],
            ['tourist_idx' => 3, 'pkg_idx' => 0, 'days_offset' => -5, 'guests' => 2, 'status' => 'cancelled', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 4, 'pkg_idx' => 1, 'days_offset' => -4, 'guests' => 2, 'status' => 'cancelled', 'time' => '02:00 PM - 11:00 AM'],
            // Today's bookings
            ['tourist_idx' => 0, 'pkg_idx' => 0, 'days_offset' => 0, 'guests' => 3, 'status' => 'checked_in', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 1, 'pkg_idx' => 2, 'days_offset' => 0, 'guests' => 2, 'status' => 'paid', 'time' => '02:00 PM - 12:00 PM'],
            // Upcoming bookings
            ['tourist_idx' => 2, 'pkg_idx' => 0, 'days_offset' => 1, 'guests' => 5, 'status' => 'paid', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 3, 'pkg_idx' => 1, 'days_offset' => 2, 'guests' => 2, 'status' => 'paid', 'time' => '02:00 PM - 11:00 AM'],
            ['tourist_idx' => 4, 'pkg_idx' => 0, 'days_offset' => 3, 'guests' => 2, 'status' => 'pending', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 0, 'pkg_idx' => 3, 'days_offset' => 5, 'guests' => 10, 'status' => 'pending', 'time' => '09:00 AM - 04:00 PM'],
            ['tourist_idx' => 1, 'pkg_idx' => 4, 'days_offset' => 5, 'guests' => 2, 'status' => 'paid', 'time' => '09:00 AM - 01:00 PM'],
            ['tourist_idx' => 2, 'pkg_idx' => 2, 'days_offset' => 7, 'guests' => 2, 'status' => 'pending', 'time' => '02:00 PM - 12:00 PM'],
            ['tourist_idx' => 3, 'pkg_idx' => 5, 'days_offset' => 7, 'guests' => 2, 'status' => 'pending', 'time' => '10:00 AM - 02:00 PM'],
            ['tourist_idx' => 4, 'pkg_idx' => 0, 'days_offset' => 10, 'guests' => 4, 'status' => 'pending', 'time' => '08:00 AM - 05:00 PM'],
            ['tourist_idx' => 0, 'pkg_idx' => 1, 'days_offset' => 12, 'guests' => 2, 'status' => 'paid', 'time' => '02:00 PM - 11:00 AM'],
            ['tourist_idx' => 1, 'pkg_idx' => 0, 'days_offset' => 14, 'guests' => 6, 'status' => 'pending', 'time' => '08:00 AM - 05:00 PM'],
        ];

        foreach ($bookingData as $bd) {
            $bookingDate = now()->addDays($bd['days_offset'])->format('Y-m-d');
            $package = $packages[$bd['pkg_idx']];
            $totalAmount = $package->price * $bd['guests'];

            $cancelledAt = null;
            $cancellationReason = null;
            if ($bd['status'] === 'cancelled') {
                $cancelledAt = now()->subDays(abs($bd['days_offset']) - 1);
                $cancellationReason = 'Change of plans';
            }

            $bookings[] = Booking::create([
                'reference_no' => Booking::generateReferenceNo(),
                'user_id' => $tourists[$bd['tourist_idx']]->id,
                'package_id' => $package->id,
                'booking_date' => $bookingDate,
                'time_slot' => $bd['time'],
                'guests_count' => $bd['guests'],
                'status' => $bd['status'],
                'total_amount' => $totalAmount,
                'special_requests' => $bd['guests'] > 4 ? 'Please prepare extra beach chairs and a designated cottage area for our group.' : null,
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => $cancellationReason,
                'created_at' => now()->subDays(abs($bd['days_offset']) + rand(1, 5)),
            ]);
        }

        // ─── Payments ────────────────────────────────────────────
        foreach ($bookings as $booking) {
            if (in_array($booking->status, ['paid', 'checked_in', 'completed'])) {
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->total_amount,
                    'gateway' => rand(0, 1) ? 'stripe' : 'manual',
                    'transaction_id' => 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10)),
                    'status' => 'success',
                    'metadata' => ['currency' => 'PHP', 'paid_at' => $booking->created_at->addHours(2)->toIso8601String()],
                ]);
            } elseif ($booking->status === 'pending') {
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->total_amount,
                    'gateway' => 'stripe',
                    'transaction_id' => null,
                    'status' => 'pending',
                    'metadata' => null,
                ]);
            } elseif ($booking->status === 'cancelled') {
                // Some cancelled bookings had refunds
                if (rand(0, 1)) {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $booking->total_amount,
                        'gateway' => 'stripe',
                        'transaction_id' => 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10)),
                        'status' => 'refunded',
                        'metadata' => ['refunded_at' => now()->toIso8601String(), 'reason' => 'Cancellation within 24h window'],
                    ]);
                }
            }
        }

        // ─── Emergencies ─────────────────────────────────────────
        $emergencyData = [
            ['user_idx' => 0, 'category' => 'medical', 'description' => 'Guest experienced heat exhaustion near the beachfront. Feeling dizzy and dehydrated.', 'status' => 'resolved', 'responder_idx' => 1],
            ['user_idx' => 1, 'category' => 'lost_item', 'description' => 'Lost a black wallet near the cottage area. Contains IDs and cash.', 'status' => 'responding', 'responder_idx' => 1],
            ['user_idx' => 2, 'category' => 'security', 'description' => 'Suspicious individual near the parking area. Requesting security check.', 'status' => 'acknowledged', 'responder_idx' => 2],
            ['user_idx' => 3, 'category' => 'medical', 'description' => 'Child scraped knee on rocks near the shore. Needs first aid.', 'status' => 'pending', 'responder_idx' => null],
            ['user_idx' => 4, 'category' => 'other', 'description' => 'Cottage water supply not working. Requesting maintenance assistance.', 'status' => 'acknowledged', 'responder_idx' => 1],
        ];

        foreach ($emergencyData as $ed) {
            $resolvedAt = $ed['status'] === 'resolved' ? now()->subHours(2) : null;

            Emergency::create([
                'user_id' => $tourists[$ed['user_idx']]->id,
                'category' => $ed['category'],
                'description' => $ed['description'],
                'photo_path' => null,
                'latitude' => 10.6837 + (rand(-100, 100) / 10000),
                'longitude' => 124.7970 + (rand(-100, 100) / 10000),
                'status' => $ed['status'],
                'tracking_number' => Emergency::generateTrackingNumber(),
                'resolved_at' => $resolvedAt,
                'responder_id' => $ed['responder_idx'] ? ($ed['responder_idx'] == 1 ? $staff1->id : $staff2->id) : null,
                'response_notes' => $ed['status'] === 'resolved' ? 'First aid administered. Guest recovered after resting in the shade with hydration.' : null,
            ]);
        }

        // ─── Chatbot Intents ─────────────────────────────────────
        $intents = [
            ['keyword' => 'rates', 'response' => 'Our rates start at PHP 250 for kayak rental up to PHP 3,500 for our Beachfront Cabin Suite. Day Tour is PHP 500 per person. Visit our Packages page for full pricing details!', 'category' => 'rates'],
            ['keyword' => 'price', 'response' => 'Here are our current rates: Day Tour - PHP 500/pax, Overnight Cottage - PHP 1,500/couple, Beachfront Cabin - PHP 3,500/couple, Group Package - PHP 800/pax. Add-ons like Snorkeling (PHP 350) and Kayak (PHP 250) are also available.', 'category' => 'rates'],
            ['keyword' => 'cost', 'response' => 'Our packages range from PHP 250 (Kayak Rental) to PHP 3,500 (Beachfront Cabin Suite). Day Tour Package is our most popular at PHP 500 per person. Peak season surcharges may apply from March to May.', 'category' => 'rates'],
            ['keyword' => 'hours', 'response' => 'Talisay Beach Resort is open daily from 8:00 AM to 5:00 PM for day tours. Overnight stays check in at 2:00 PM and check out at 11:00 AM (cottage) or 12:00 PM (cabin).', 'category' => 'hours'],
            ['keyword' => 'open', 'response' => 'We are open daily! Day tours run from 8:00 AM to 5:00 PM. Overnight cottages are available Thursday through Sunday with check-in at 2:00 PM.', 'category' => 'hours'],
            ['keyword' => 'time', 'response' => 'Day Tour hours: 8:00 AM - 5:00 PM daily. Overnight Cottage: Check-in 2:00 PM, Check-out 11:00 AM (Thu-Sun). Beachfront Cabin: Check-in 2:00 PM, Check-out 12:00 PM daily.', 'category' => 'hours'],
            ['keyword' => 'cancel', 'response' => 'Cancellations made at least 48 hours before the booking date receive a full refund. Cancellations within 24-48 hours receive a 50% refund. Same-day cancellations are non-refundable. Please contact our staff for assistance.', 'category' => 'policies'],
            ['keyword' => 'refund', 'response' => 'Refunds are processed within 5-7 business days. Full refund for cancellations 48+ hours before booking. 50% refund for 24-48 hour cancellations. No refunds for same-day cancellations.', 'category' => 'policies'],
            ['keyword' => 'policy', 'response' => 'Our policies: Cancellations 48+ hours ahead get full refund. Children under 5 stay free. Pets are not allowed. Outside food is permitted in designated picnic areas only. Smoking is restricted to outdoor areas.', 'category' => 'policies'],
            ['keyword' => 'directions', 'response' => 'Talisay Beach Resort is located in Barangay Maslug, Baybay City, Leyte. From Tacloban, take the coastal road south (approx. 2.5 hours). From Baybay City proper, head northwest for about 20 minutes. Look for our signage along the coastal road.', 'category' => 'directions'],
            ['keyword' => 'location', 'response' => 'We are in Barangay Maslug, Baybay City, Leyte, Philippines. The resort is along the coastal road with clear signage. From Baybay City center, it is a 20-minute drive northwest.', 'category' => 'directions'],
            ['keyword' => 'how to get there', 'response' => 'To reach Talisay Beach Resort: From Tacloban City, take the south-bound coastal highway (2.5 hrs). From Ormoc, take the south road via Baybay (2 hrs). From Baybay City proper, drive 20 mins northwest along the coast. Public transport (jeepney) is available from Baybay terminal.', 'category' => 'directions'],
            ['keyword' => 'facilities', 'response' => 'Our facilities include: beachfront cottages, premium cabins with AC, shower and changing rooms, restaurant and bar, bonfire area, snorkeling and kayak rental, beach volleyball court, children play area, and free parking.', 'category' => 'facilities'],
            ['keyword' => 'amenities', 'response' => 'Talisay Beach Resort amenities: Beachfront cottages, Air-conditioned cabins, Restaurant & bar, Shower/changing rooms, Bonfire area, Water sports equipment rental, Beach volleyball, Children\'s play area, Free parking, WiFi at the pavilion.', 'category' => 'facilities'],
            ['keyword' => 'wifi', 'response' => 'Free WiFi is available at the main pavilion and restaurant area. Connection speed is suitable for browsing and social media. For video streaming, we recommend enjoying the beach instead!', 'category' => 'facilities'],
            ['keyword' => 'food', 'response' => 'Our beachfront restaurant serves fresh seafood, local Filipino dishes, and refreshing beverages. Operating hours: 7:00 AM - 9:00 PM. Guests can also arrange for grilled beachside meals for group packages.', 'category' => 'facilities'],
            ['keyword' => 'restaurant', 'response' => 'The Talisay Beach Restaurant is open daily from 7:00 AM to 9:00 PM. We serve fresh catch seafood, Filipino cuisine, and tropical drinks. Cottage and cabin packages include selected meals.', 'category' => 'facilities'],
            ['keyword' => 'booking', 'response' => 'You can book through our mobile app or website. Select your preferred package, choose a date, specify the number of guests, and proceed to payment. You\'ll receive a confirmation with your booking reference number.', 'category' => 'booking_help'],
            ['keyword' => 'reserve', 'response' => 'To make a reservation: 1) Choose a package, 2) Select your preferred date, 3) Enter the number of guests, 4) Complete payment. You\'ll receive a booking reference number via email and app notification.', 'category' => 'booking_help'],
            ['keyword' => 'availability', 'response' => 'You can check real-time availability on our booking page. Select your desired date and package to see available slots. During peak season (March-May), we recommend booking at least 2 weeks in advance.', 'category' => 'booking_help'],
            ['keyword' => 'payment', 'response' => 'We accept payments via credit/debit card (Visa, Mastercard) through our secure online payment system, or via bank transfer/manual payment at the resort. A payment link will be provided after booking confirmation.', 'category' => 'booking_help'],
            ['keyword' => 'snorkeling', 'response' => 'Our Snorkeling Adventure Add-on is PHP 350 per person, available daily from 9:00 AM to 4:00 PM. Includes mask, snorkel, fins, and a certified guide. The Talisay coral reef is just 50 meters from shore!', 'category' => 'rates'],
            ['keyword' => 'kayak', 'response' => 'Kayak Rental is PHP 250 per session. Single and double kayaks are available. Life jackets and a brief orientation are included. Available daily from 9:00 AM to 4:00 PM.', 'category' => 'rates'],
            ['keyword' => 'hello', 'response' => 'Welcome to Talisay Beach Resort! How can I help you today? You can ask me about our rates, facilities, booking process, directions, or resort policies.', 'category' => 'general'],
            ['keyword' => 'hi', 'response' => 'Hello! Welcome to Talisay Beach Resort! I can help you with information about rates, hours, booking, directions, and more. What would you like to know?', 'category' => 'general'],
            ['keyword' => 'help', 'response' => 'I can help you with: Rates & Pricing, Operating Hours, Booking & Reservations, Directions & Location, Facilities & Amenities, Resort Policies, and Emergency Assistance. Just ask away!', 'category' => 'general'],
            ['keyword' => 'emergency', 'response' => 'For emergencies, please use the Emergency button in the app or alert any staff member immediately. Our security team is on-site 24/7. For life-threatening emergencies, call 911 or the local emergency number.', 'category' => 'general'],
            ['keyword' => 'contact', 'response' => 'You can reach us at: Email: info@talisayresort.com, Phone: +63-53-XXX-XXXX, or visit us at Barangay Maslug, Baybay City, Leyte. Our staff is also available on-site during operating hours.', 'category' => 'general'],
            ['keyword' => 'parking', 'response' => 'Free parking is available for all guests. Our parking area can accommodate up to 50 vehicles. For oversized vehicles, please inform us in advance so we can reserve an appropriate spot.', 'category' => 'facilities'],
            ['keyword' => 'children', 'response' => 'Children are welcome at Talisay Beach Resort! Kids under 5 stay free with a paying adult. We have a children\'s play area and shallow swimming zones. Parental supervision is required at all times near the water.', 'category' => 'policies'],
            ['keyword' => 'pet', 'response' => 'Unfortunately, pets are not allowed within the resort premises to ensure the comfort and safety of all guests. Service animals are permitted with proper documentation.', 'category' => 'policies'],
            ['keyword' => 'weather', 'response' => 'Baybay City enjoys a tropical climate. Dry season is typically March to May (best beach weather). Rainy season is June to October. Always check the forecast before your visit. We post weather updates on our social media.', 'category' => 'general'],
            ['keyword' => 'group', 'response' => 'Our Group Adventure Package is perfect for groups of 5-15 people at PHP 800 per person. It includes beach games, island hopping, snorkeling, grilled lunch, and a group photo session. Book at least 3 days in advance!', 'category' => 'rates'],
        ];

        foreach ($intents as $intent) {
            ChatbotIntent::create([
                'keyword' => $intent['keyword'],
                'response' => $intent['response'],
                'category' => $intent['category'],
                'is_active' => true,
            ]);
        }

        // ─── Chatbot Logs (sample conversations) ─────────────────
        $sampleConversations = [
            ['user_idx' => 0, 'message' => 'What are your rates?', 'response' => 'Our rates start at PHP 250 for kayak rental up to PHP 3,500 for our Beachfront Cabin Suite. Day Tour is PHP 500 per person.', 'intent' => 'rates'],
            ['user_idx' => 0, 'message' => 'What time do you open?', 'response' => 'Talisay Beach Resort is open daily from 8:00 AM to 5:00 PM for day tours.', 'intent' => 'hours'],
            ['user_idx' => 1, 'message' => 'How do I cancel my booking?', 'response' => 'Cancellations made at least 48 hours before the booking date receive a full refund.', 'intent' => 'cancel'],
            ['user_idx' => 2, 'message' => 'Is there WiFi?', 'response' => 'Free WiFi is available at the main pavilion and restaurant area.', 'intent' => 'wifi'],
            ['user_idx' => 3, 'message' => 'How to get there?', 'response' => 'Talisay Beach Resort is located in Barangay Maslug, Baybay City, Leyte. From Tacloban, take the coastal road south.', 'intent' => 'directions'],
            ['user_idx' => 4, 'message' => 'Can I bring my dog?', 'response' => 'Unfortunately, pets are not allowed within the resort premises to ensure the comfort and safety of all guests.', 'intent' => 'pet'],
        ];

        foreach ($sampleConversations as $sc) {
            ChatbotLog::create([
                'user_id' => $tourists[$sc['user_idx']]->id,
                'session_id' => 'session-' . $tourists[$sc['user_idx']]->id . '-' . now()->subDays(rand(1, 5))->format('Ymd'),
                'message' => $sc['message'],
                'response' => $sc['response'],
                'intent' => $sc['intent'],
            ]);
        }

        // ─── Reviews ─────────────────────────────────────────────
        $completedBookings = Booking::where('status', 'completed')->get();
        $reviewComments = [
            'Amazing beach experience! The sand was pristine and the water was crystal clear. Will definitely come back with family.',
            'The overnight cottage was cozy and the bonfire experience was magical. Staff were very accommodating.',
            'Great value for money. The day tour package had everything we needed. The welcome drink was a nice touch!',
            'The beachfront cabin exceeded our expectations. Waking up to the sound of waves was unforgettable. Highly recommended!',
            'Our group of 8 had an absolute blast! The island hopping and snorkeling were the highlights. The grilled lunch was delicious.',
        ];

        foreach ($completedBookings as $index => $booking) {
            if (isset($reviewComments[$index])) {
                Review::create([
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'rating' => rand(4, 5),
                    'comment' => $reviewComments[$index],
                    'is_approved' => $index < 3, // First 3 approved, rest pending
                ]);
            }
        }

        // Add a couple more reviews with lower ratings
        Review::create([
            'user_id' => $tourists[3]->id,
            'booking_id' => $completedBookings[2]->id ?? $completedBookings->first()->id,
            'rating' => 3,
            'comment' => 'The beach was nice but the cottage was a bit dated. Could use some renovation. Food was average.',
            'is_approved' => true,
        ]);

        Review::create([
            'user_id' => $tourists[4]->id,
            'booking_id' => $completedBookings[0]->id ?? $completedBookings->first()->id,
            'rating' => 2,
            'comment' => 'Water supply was interrupted during our stay. Staff could have been more responsive. The beach itself is beautiful though.',
            'is_approved' => false,
        ]);

        // ─── Memory Timeline Items ───────────────────────────────
        $completedBookingsForTimeline = $completedBookings->take(3);
        $timelineItemsData = [
            ['type' => 'photo', 'caption' => 'Arrival at Talisay Beach - The view took our breath away!', 'selected' => true],
            ['type' => 'note', 'caption' => 'First dip in the crystal clear waters. The sand was so fine and white!', 'selected' => true],
            ['type' => 'photo', 'caption' => 'Cottage view from the beachfront - paradise!', 'selected' => true],
            ['type' => 'video', 'caption' => 'Sunset time-lapse from the veranda', 'selected' => true],
            ['type' => 'note', 'caption' => 'Best grilled seafood lunch ever! Fresh catch of the day.', 'selected' => false],
            ['type' => 'photo', 'caption' => 'Group photo with the amazing Talisay sunset', 'selected' => true],
            ['type' => 'photo', 'caption' => 'Snorkeling adventure - saw Nemo!', 'selected' => true],
            ['type' => 'note', 'caption' => 'Bonfire night under the stars - magical evening', 'selected' => true],
            ['type' => 'video', 'caption' => 'Kayaking along the coast', 'selected' => false],
        ];

        foreach ($completedBookingsForTimeline as $bookingIdx => $booking) {
            $itemOffset = $bookingIdx * 3;
            for ($i = 0; $i < 3; $i++) {
                $itemData = $timelineItemsData[$itemOffset + $i] ?? $timelineItemsData[$i];
                MemoryTimelineItem::create([
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'type' => $itemData['type'],
                    'file_path' => $itemData['type'] !== 'note' ? "memory-items/sample-{$itemData['type']}-" . ($itemOffset + $i + 1) . ".jpg" : null,
                    'caption' => $itemData['caption'],
                    'is_selected' => $itemData['selected'],
                ]);
            }
        }

        // ─── Memory Timelines ────────────────────────────────────
        foreach ($completedBookingsForTimeline as $booking) {
            MemoryTimeline::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'My Talisay Beach Memories - ' . $booking->booking_date->format('F Y'),
                'generated_pdf_path' => null,
                'is_generated' => false,
            ]);
        }

        // ─── Tour Assets ─────────────────────────────────────────
        $tourAssets = [
            [
                'title' => 'Main Beach Entrance',
                'description' => 'Welcome to Talisay Beach Resort! This panoramic view shows our main entrance, the reception area, and the stunning beachfront beyond.',
                'hotspots' => [
                    ['pitch' => 10, 'yaw' => -30, 'text' => 'Reception Hall', 'type' => 'info'],
                    ['pitch' => -5, 'yaw' => 45, 'text' => 'Beachfront Cottages', 'type' => 'scene', 'sceneId' => 'cottage-area'],
                ],
            ],
            [
                'title' => 'Beachfront Cottage Area',
                'description' => 'Our cozy beachfront cottages line the shore, offering direct access to the sand and sea. Each cottage has a private veranda with sea views.',
                'hotspots' => [
                    ['pitch' => 5, 'yaw' => 60, 'text' => 'Cottage A1', 'type' => 'info'],
                    ['pitch' => -10, 'yaw' => -45, 'text' => 'Restaurant & Bar', 'type' => 'scene', 'sceneId' => 'restaurant'],
                ],
            ],
            [
                'title' => 'Restaurant and Bar',
                'description' => 'Our beachfront restaurant serves fresh seafood and local Filipino dishes. Enjoy your meal with a panoramic view of the Camotes Sea.',
                'hotspots' => [
                    ['pitch' => 0, 'yaw' => 0, 'text' => 'Outdoor Dining', 'type' => 'info'],
                    ['pitch' => -5, 'yaw' => 90, 'text' => 'Beachfront View', 'type' => 'scene', 'sceneId' => 'main-entrance'],
                ],
            ],
            [
                'title' => 'Premium Cabin Suite',
                'description' => 'Step inside our premium Beachfront Cabin Suite. Fully air-conditioned with a private veranda, king-size bed, and direct beach access.',
                'hotspots' => [
                    ['pitch' => 10, 'yaw' => -20, 'text' => 'Private Veranda', 'type' => 'info'],
                    ['pitch' => 0, 'yaw' => 60, 'text' => 'Sea View Balcony', 'type' => 'info'],
                ],
            ],
            [
                'title' => 'Snorkeling and Water Sports Area',
                'description' => 'The coral reef is just 50 meters from shore! Our water sports area offers snorkeling gear, kayaks, and guided reef tours.',
                'hotspots' => [
                    ['pitch' => -15, 'yaw' => 0, 'text' => 'Coral Reef Zone', 'type' => 'info'],
                    ['pitch' => 5, 'yaw' => -90, 'text' => 'Kayak Launch', 'type' => 'info'],
                    ['pitch' => 10, 'yaw' => 120, 'text' => 'Back to Beach', 'type' => 'scene', 'sceneId' => 'main-entrance'],
                ],
            ],
        ];

        foreach ($tourAssets as $idx => $asset) {
            TourAsset::create([
                'title' => $asset['title'],
                'description' => $asset['description'],
                'panorama_path' => "tour-assets/panorama-" . ($idx + 1) . ".jpg",
                'type' => 'image',
                'hotspots' => $asset['hotspots'],
                'sort_order' => $idx + 1,
                'is_active' => true,
            ]);
        }

        // ─── System Settings ─────────────────────────────────────
        $settings = [
            'daily_visitor_cap' => '100',
            'cancellation_window_hours' => '48',
            'late_cancellation_refund_percent' => '50',
            'same_day_cancellation_refund' => '0',
            'check_in_time' => '14:00',
            'check_out_time_cottage' => '11:00',
            'check_out_time_cabin' => '12:00',
            'max_booking_advance_days' => '90',
            'min_booking_advance_hours' => '6',
            'max_guests_per_booking' => '15',
            'resort_contact_email' => 'info@talisayresort.com',
            'resort_contact_phone' => '+63-53-XXX-XXXX',
            'resort_address' => 'Barangay Maslug, Baybay City, Leyte',
            'notification_booking_created' => 'New booking created: {reference_no}',
            'notification_booking_confirmed' => 'Your booking {reference_no} has been confirmed!',
            'notification_booking_cancelled' => 'Booking {reference_no} has been cancelled.',
            'notification_emergency_alert' => 'Emergency alert at Talisay Beach Resort',
            'notification_payment_received' => 'Payment of PHP {amount} received for booking {reference_no}',
            'stripe_mode' => 'test',
            'timezone' => 'Asia/Manila',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::create([
                'key' => $key,
                'value' => $value,
            ]);
        }

        // ─── Notifications ───────────────────────────────────────
        NotificationModel::create([
            'user_id' => $admin->id,
            'type' => 'booking',
            'title' => 'New Booking Received',
            'message' => 'A new booking (TBR-DEMO001) has been placed by Ana Reyes for the Day Tour Package.',
            'data' => ['booking_id' => $bookings[0]->id ?? 1],
            'read_at' => now()->subHours(3),
        ]);

        NotificationModel::create([
            'user_id' => $admin->id,
            'type' => 'emergency',
            'title' => 'Emergency Alert',
            'message' => 'New emergency reported: Medical - Guest experienced heat exhaustion near the beachfront.',
            'data' => ['emergency_id' => 1],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'user_id' => $admin->id,
            'type' => 'payment',
            'title' => 'Payment Received',
            'message' => 'Payment of PHP 1,500.00 has been received for booking TBR-DEMO003.',
            'data' => ['payment_id' => 1],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'user_id' => $staff1->id,
            'type' => 'booking',
            'title' => 'New Booking Assigned',
            'message' => 'A new group booking has been placed for this Saturday. 10 guests for the Group Adventure Package.',
            'data' => ['booking_id' => $bookings[15]->id ?? 16],
            'read_at' => null,
        ]);

        // Notification for a tourist
        NotificationModel::create([
            'user_id' => $tourists[0]->id,
            'type' => 'booking',
            'title' => 'Booking Confirmed',
            'message' => 'Your Day Tour booking for today has been confirmed. We look forward to seeing you!',
            'data' => ['booking_id' => $bookings[10]->id ?? 11],
            'read_at' => null,
        ]);

        // ─── Audit Logs ──────────────────────────────────────────
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user_login',
            'model_type' => User::class,
            'model_id' => $admin->id,
            'old_values' => null,
            'new_values' => ['ip' => '127.0.0.1', 'login_at' => now()->subHours(5)->toIso8601String()],
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'package_created',
            'model_type' => Package::class,
            'model_id' => 1,
            'old_values' => null,
            'new_values' => ['name' => 'Day Tour Package', 'price' => 500.00],
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'payment_approved',
            'model_type' => Payment::class,
            'model_id' => 1,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'success'],
        ]);

        AuditLog::create([
            'user_id' => $staff1->id,
            'action' => 'emergency_status_updated',
            'model_type' => Emergency::class,
            'model_id' => 1,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'resolved'],
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'review_approved',
            'model_type' => Review::class,
            'model_id' => 1,
            'old_values' => ['is_approved' => false],
            'new_values' => ['is_approved' => true],
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'settings_updated',
            'model_type' => SystemSetting::class,
            'model_id' => 1,
            'old_values' => ['key' => 'daily_visitor_cap', 'value' => '50'],
            'new_values' => ['key' => 'daily_visitor_cap', 'value' => '100'],
        ]);
    }
}
