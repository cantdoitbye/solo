<?php

namespace Database\Seeders;

use App\Models\VenueType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VenueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venueTypes = [
            [
                'name' => 'Indoor',
                'slug' => 'indoor',
                'description' => 'Indoor venues like restaurants, cafes, bars, museums, etc.',
                'icon' => 'building',
                'sort_order' => 1
            ],
            [
                'name' => 'Outdoor',
                'slug' => 'outdoor',
                'description' => 'Outdoor venues like parks, beaches, hiking trails, etc.',
                'icon' => 'tree',
                'sort_order' => 2
            ],
            [
                'name' => 'Open Area',
                'slug' => 'open-area',
                'description' => 'Open areas like town squares, public spaces, etc.',
                'icon' => 'map',
                'sort_order' => 3
            ],
            [
                'name' => 'Online',
                'slug' => 'online',
                'description' => 'Virtual events and online meetups',
                'icon' => 'globe',
                'sort_order' => 4
            ]
        ];

        foreach ($venueTypes as $venueType) {
            VenueType::create($venueType);
        }
    }
}
