<?php

namespace Database\Seeders;

use App\Models\AccommodationUnit;
use Illuminate\Database\Seeder;

class AccommodationUnitSeeder extends Seeder
{
    public function run(): void
    {
        // ─── ROOMS (10 units: 5 Normal + 5 Premium) ───────────────────────────────
        $normalRoomAmenities = [
            'Air-conditioning', 'Queen-size bed', 'Private bathroom', 'Hot & cold shower',
            'Free Wi-Fi', 'Basic toiletries', 'Wardrobe', 'Electric kettle', 'Mirror & dresser',
        ];

        $premiumRoomAmenities = [
            'Strong air-conditioning', 'King-size bed', 'Private bathroom with bathtub',
            'Hot & cold shower', 'Free Wi-Fi', 'Smart TV (43")', 'Mini-refrigerator',
            'Premium toiletries', 'Wardrobe with safe', 'Coffee & tea station', 'Ocean-view window',
        ];

        for ($i = 1; $i <= 5; $i++) {
            AccommodationUnit::create([
                'unit_number'      => 'Room ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'unit_type'        => 'room',
                'variant'          => 'normal',
                'floor_area_sqm'   => rand(18, 22),
                'bed_configuration'=> 'Queen-size bed (sleeps 2)',
                'max_occupancy'    => 2,
                'amenities'        => $normalRoomAmenities,
                'description'      => 'A comfortable air-conditioned room featuring a queen-size bed, private bathroom with hot and cold shower, and free Wi-Fi. Ideal for couples or solo travelers looking for a relaxing beach getaway at Talisay Beach Resort.',
                'images'           => [],
                'tour_video_path'  => null,
                'price_per_night'  => 1500.00,
                'is_available'     => true,
                'sort_order'       => $i,
            ]);
        }

        for ($i = 6; $i <= 10; $i++) {
            AccommodationUnit::create([
                'unit_number'      => 'Room ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'unit_type'        => 'room',
                'variant'          => 'premium',
                'floor_area_sqm'   => rand(28, 35),
                'bed_configuration'=> 'King-size bed (sleeps 2) + sofa bed (optional)',
                'max_occupancy'    => 3,
                'amenities'        => $premiumRoomAmenities,
                'description'      => 'A spacious premium room featuring premium furnishings, a king-size bed, smart TV, mini-refrigerator, and an ocean-view window. The upgraded private bathroom includes a bathtub and premium amenities for a luxurious stay.',
                'images'           => [],
                'tour_video_path'  => null,
                'price_per_night'  => 2800.00,
                'is_available'     => true,
                'sort_order'       => $i,
            ]);
        }

        // ─── COTTAGES (10 units: 5 Normal + 5 Premium) ────────────────────────────
        $normalCottageAmenities = [
            'Open-air design', 'Electric fan', 'Basic seating set (table + chairs)',
            'Shared bathroom access nearby', 'Outdoor garden view', 'BBQ grill area access',
        ];

        $premiumCottageAmenities = [
            'Fully enclosed', 'Air-conditioning', 'Karaoke system (microphone + speakers)',
            'Private bathroom', 'Outdoor dining area', 'Premium furniture set',
            'Electric fan (additional)', 'BBQ grill', 'Mini-ref', 'Cable TV',
            'Beachfront/pool access', 'Outdoor hammock',
        ];

        for ($i = 1; $i <= 5; $i++) {
            AccommodationUnit::create([
                'unit_number'      => 'Cottage ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'unit_type'        => 'cottage',
                'variant'          => 'normal',
                'floor_area_sqm'   => rand(20, 28),
                'bed_configuration'=> 'Open-air seating (no beds)',
                'max_occupancy'    => 10,
                'amenities'        => $normalCottageAmenities,
                'description'      => 'An open-air cottage with a relaxed, natural ambiance. Features electric fans, basic seating, and access to shared bathroom facilities. Perfect for day-use groups who want to enjoy the beach and outdoor dining together.',
                'images'           => [],
                'tour_video_path'  => null,
                'price_per_night'  => 2000.00,
                'is_available'     => true,
                'sort_order'       => 10 + $i,
            ]);
        }

        for ($i = 6; $i <= 10; $i++) {
            AccommodationUnit::create([
                'unit_number'      => 'Cottage ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'unit_type'        => 'cottage',
                'variant'          => 'premium',
                'floor_area_sqm'   => rand(35, 50),
                'bed_configuration'=> 'Sofa beds + outdoor lounge beds included',
                'max_occupancy'    => 20,
                'amenities'        => $premiumCottageAmenities,
                'description'      => 'A fully enclosed premium cottage with air-conditioning, karaoke system, private bathroom, and a beautiful outdoor dining area. Ideal for family reunions, barkada trips, and corporate outings. Features direct beach access.',
                'images'           => [],
                'tour_video_path'  => null,
                'price_per_night'  => 4500.00,
                'is_available'     => true,
                'sort_order'       => 10 + $i,
            ]);
        }
    }
}
