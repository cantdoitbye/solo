<?php

namespace Database\Seeders;

use App\Models\SuggestedLocation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuggestedLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suggestedLocations = [
            [
                'name' => 'Yardbird Table & Bar',
                'description' => 'Popular restaurant and bar with great atmosphere for social gatherings',
                'google_maps_url' => 'https://www.google.com/maps/place/Yardbird+Table+%26+Bar/@39.7616806,-105.0033001,15z/data=!4m6!3m5!1s0x876c793ab3839931:0x667ad8eae4eda241!8m2!3d39.7616806!4d-104.9852757!16s%2Fg%2F11txzybxx0?entry=ttu&g_ep=EgoyMDI1MDcyMS4wIKXMDSoASAFQAw%3D%3D',
                'google_place_id' => 'ChIJMZODszl5bIcRQaLt5Kqt1mY', // Extracted from the URL
                'venue_name' => 'Yardbird Table & Bar',
                'venue_address' => '1875 29th St, Boulder, CO 80301, USA', // Approximate address based on coordinates
                'latitude' => 39.7616806,
                'longitude' => -104.9852757,
                'city' => 'Boulder',
                'state' => 'Colorado',
                'country' => 'USA',
                'postal_code' => '80301',
                'google_place_details' => [
                    'place_id' => 'ChIJMZODszl5bIcRQaLt5Kqt1mY',
                    'name' => 'Yardbird Table & Bar',
                    'rating' => null,
                    'price_level' => null,
                    'types' => ['restaurant', 'bar', 'establishment']
                ],
                'category' => 'restaurant',
                'image_url' => null,
                'sort_order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Local Restaurants Area',
                'description' => 'Popular dining area with various restaurant options for group events',
                'google_maps_url' => 'https://www.google.com/maps/search/Restaurants/@39.7528742,-104.9918108,15z/data=!3m1!4b1?entry=tts&g_ep=EgoyMDI1MDcxNi4wIPu8ASoASAFQAw%3D%3D&skid=490223ec-ee0b-4848-9b0f-1ff05105947b',
                'google_place_id' => null, // This is a search area, not a specific place
                'venue_name' => 'Downtown Restaurant District',
                'venue_address' => 'Downtown Boulder, CO, USA', // General area based on coordinates
                'latitude' => 39.7528742,
                'longitude' => -104.9918108,
                'city' => 'Boulder',
                'state' => 'Colorado',
                'country' => 'USA',
                'postal_code' => null,
                'google_place_details' => [
                    'search_query' => 'Restaurants',
                    'location' => [
                        'lat' => 39.7528742,
                        'lng' => -104.9918108
                    ],
                    'zoom_level' => 15,
                    'search_id' => '490223ec-ee0b-4848-9b0f-1ff05105947b'
                ],
                'category' => 'restaurant',
                'image_url' => null,
                'sort_order' => 2,
                'is_active' => true
            ]
        ];

        foreach ($suggestedLocations as $location) {
            SuggestedLocation::create($location);
        }
    }
}
