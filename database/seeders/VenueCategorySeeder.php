<?php

namespace Database\Seeders;

use App\Models\VenueCategory;
use App\Models\VenueType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VenueCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $indoorId = VenueType::where('slug', 'indoor')->first()->id;
        $outdoorId = VenueType::where('slug', 'outdoor')->first()->id;
        $openAreaId = VenueType::where('slug', 'open-area')->first()->id;
        $onlineId = VenueType::where('slug', 'online')->first()->id;

        $venueCategories = [
            // Indoor categories
            [
                'name' => 'Restaurant',
                'slug' => 'restaurant',
                'description' => 'Restaurants, diners, food courts',
                'icon' => 'utensils',
                'venue_type_id' => $indoorId,
                'sort_order' => 1
            ],
            [
                'name' => 'Cafe',
                'slug' => 'cafe',
                'description' => 'Coffee shops, cafes, tea houses',
                'icon' => 'coffee',
                'venue_type_id' => $indoorId,
                'sort_order' => 2
            ],
            [
                'name' => 'Bar/Pub',
                'slug' => 'bar-pub',
                'description' => 'Bars, pubs, lounges, nightclubs',
                'icon' => 'wine-glass',
                'venue_type_id' => $indoorId,
                'sort_order' => 3
            ],
            [
                'name' => 'Museum',
                'slug' => 'museum',
                'description' => 'Museums, art galleries, exhibitions',
                'icon' => 'landmark',
                'venue_type_id' => $indoorId,
                'sort_order' => 4
            ],
            [
                'name' => 'Shopping Center',
                'slug' => 'shopping-center',
                'description' => 'Malls, shopping centers, markets',
                'icon' => 'shopping-bag',
                'venue_type_id' => $indoorId,
                'sort_order' => 5
            ],
            [
                'name' => 'Gym/Fitness',
                'slug' => 'gym-fitness',
                'description' => 'Gyms, fitness centers, yoga studios',
                'icon' => 'dumbbell',
                'venue_type_id' => $indoorId,
                'sort_order' => 6
            ],
            [
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'description' => 'Cinemas, theaters, bowling alleys, arcades',
                'icon' => 'film',
                'venue_type_id' => $indoorId,
                'sort_order' => 7
            ],
            
            // Outdoor categories
            [
                'name' => 'Park',
                'slug' => 'park',
                'description' => 'Parks, gardens, nature reserves',
                'icon' => 'tree',
                'venue_type_id' => $outdoorId,
                'sort_order' => 1
            ],
            [
                'name' => 'Beach',
                'slug' => 'beach',
                'description' => 'Beaches, lakeshores, waterfront',
                'icon' => 'waves',
                'venue_type_id' => $outdoorId,
                'sort_order' => 2
            ],
            [
                'name' => 'Trail/Hiking',
                'slug' => 'trail-hiking',
                'description' => 'Hiking trails, walking paths, nature walks',
                'icon' => 'mountain',
                'venue_type_id' => $outdoorId,
                'sort_order' => 3
            ],
            [
                'name' => 'Sports Field',
                'slug' => 'sports-field',
                'description' => 'Sports fields, courts, outdoor activities',
                'icon' => 'futbol',
                'venue_type_id' => $outdoorId,
                'sort_order' => 4
            ],
            [
                'name' => 'Outdoor Dining',
                'slug' => 'outdoor-dining',
                'description' => 'Outdoor restaurants, food trucks, picnic areas',
                'icon' => 'utensils',
                'venue_type_id' => $outdoorId,
                'sort_order' => 5
            ],
            
            // Open Area categories
            [
                'name' => 'Town Square',
                'slug' => 'town-square',
                'description' => 'Town squares, public plazas, city centers',
                'icon' => 'map-pin',
                'venue_type_id' => $openAreaId,
                'sort_order' => 1
            ],
            [
                'name' => 'Event Space',
                'slug' => 'event-space',
                'description' => 'Convention centers, event halls, community centers',
                'icon' => 'building',
                'venue_type_id' => $openAreaId,
                'sort_order' => 2
            ],
            [
                'name' => 'Transportation Hub',
                'slug' => 'transportation-hub',
                'description' => 'Airports, train stations, bus terminals',
                'icon' => 'plane',
                'venue_type_id' => $openAreaId,
                'sort_order' => 3
            ],
            
            // Online categories
            [
                'name' => 'Video Call',
                'slug' => 'video-call',
                'description' => 'Zoom, Google Meet, Microsoft Teams',
                'icon' => 'video',
                'venue_type_id' => $onlineId,
                'sort_order' => 1
            ],
            [
                'name' => 'Gaming',
                'slug' => 'gaming',
                'description' => 'Online gaming, virtual worlds',
                'icon' => 'gamepad',
                'venue_type_id' => $onlineId,
                'sort_order' => 2
            ],
            [
                'name' => 'Virtual Event',
                'slug' => 'virtual-event',
                'description' => 'Webinars, virtual conferences, online workshops',
                'icon' => 'desktop',
                'venue_type_id' => $onlineId,
                'sort_order' => 3
            ]
        ];

        foreach ($venueCategories as $category) {
            VenueCategory::create($category);
        }
    }
}
