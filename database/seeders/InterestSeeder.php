<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Interest;

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interests = [
            // Featured interests (quick suggestions)
            ['name' => 'Bike Riding', 'category' => 'Sports', 'icon' => '🚴', 'is_featured' => true, 'sort_order' => 1],
            ['name' => 'Golf', 'category' => 'Sports', 'icon' => '⛳', 'is_featured' => true, 'sort_order' => 2],
            ['name' => 'Hiking', 'category' => 'Outdoors', 'icon' => '🥾', 'is_featured' => true, 'sort_order' => 3],
            ['name' => 'Live Music', 'category' => 'Music', 'icon' => '🎵', 'is_featured' => true, 'sort_order' => 4],
            ['name' => 'Yoga', 'category' => 'Wellness', 'icon' => '🧘', 'is_featured' => true, 'sort_order' => 5],
            ['name' => 'Rafting', 'category' => 'Outdoors', 'icon' => '🚣', 'is_featured' => true, 'sort_order' => 6],
            ['name' => 'Horse Riding', 'category' => 'Sports', 'icon' => '🐎', 'is_featured' => true, 'sort_order' => 7],
            ['name' => 'Jazz', 'category' => 'Music', 'icon' => '🎷', 'is_featured' => true, 'sort_order' => 8],
            ['name' => 'Ballet', 'category' => 'Arts', 'icon' => '🩰', 'is_featured' => true, 'sort_order' => 9],
            ['name' => 'Movies', 'category' => 'Entertainment', 'icon' => '🎬', 'is_featured' => true, 'sort_order' => 10],
            ['name' => 'Sketching', 'category' => 'Arts', 'icon' => '🎨', 'is_featured' => true, 'sort_order' => 11],
            ['name' => 'Mountains', 'category' => 'Outdoors', 'icon' => '⛰️', 'is_featured' => true, 'sort_order' => 12],

            // Additional interests (not featured)
            ['name' => 'Travel', 'category' => 'Lifestyle', 'icon' => '✈️', 'is_featured' => false, 'sort_order' => 13],
            ['name' => 'Reading', 'category' => 'Education', 'icon' => '📚', 'is_featured' => false, 'sort_order' => 14],
            ['name' => 'Coffee', 'category' => 'Food & Drink', 'icon' => '☕', 'is_featured' => false, 'sort_order' => 15],
            ['name' => 'Wine', 'category' => 'Food & Drink', 'icon' => '🍷', 'is_featured' => false, 'sort_order' => 16],
            ['name' => 'Craft Beer', 'category' => 'Food & Drink', 'icon' => '🍺', 'is_featured' => false, 'sort_order' => 17],
            ['name' => 'Cooking', 'category' => 'Food & Drink', 'icon' => '👨‍🍳', 'is_featured' => false, 'sort_order' => 18],
            ['name' => 'Baking', 'category' => 'Food & Drink', 'icon' => '🧁', 'is_featured' => false, 'sort_order' => 19],
            ['name' => 'Running', 'category' => 'Sports', 'icon' => '🏃', 'is_featured' => false, 'sort_order' => 20],
            ['name' => 'Meditation', 'category' => 'Wellness', 'icon' => '🧘‍♀️', 'is_featured' => false, 'sort_order' => 21],
            ['name' => 'Photography', 'category' => 'Arts', 'icon' => '📸', 'is_featured' => false, 'sort_order' => 22],
            ['name' => 'Dancing', 'category' => 'Arts', 'icon' => '💃', 'is_featured' => false, 'sort_order' => 23],
            ['name' => 'Swimming', 'category' => 'Sports', 'icon' => '🏊', 'is_featured' => false, 'sort_order' => 24],
            ['name' => 'Cycling', 'category' => 'Sports', 'icon' => '🚴', 'is_featured' => false, 'sort_order' => 25],
            ['name' => 'Board Games', 'category' => 'Games', 'icon' => '🎲', 'is_featured' => false, 'sort_order' => 26],
            ['name' => 'Video Games', 'category' => 'Games', 'icon' => '🎮', 'is_featured' => false, 'sort_order' => 27],
            ['name' => 'Gardening', 'category' => 'Outdoors', 'icon' => '🌱', 'is_featured' => false, 'sort_order' => 28],
            ['name' => 'Volunteering', 'category' => 'Community', 'icon' => '🤝', 'is_featured' => false, 'sort_order' => 29],
            ['name' => 'Technology', 'category' => 'Education', 'icon' => '💻', 'is_featured' => false, 'sort_order' => 30],
            ['name' => 'Podcasts', 'category' => 'Education', 'icon' => '🎧', 'is_featured' => false, 'sort_order' => 31],
            ['name' => 'Stand-up Comedy', 'category' => 'Entertainment', 'icon' => '🎤', 'is_featured' => false, 'sort_order' => 32],
            ['name' => 'Rock Climbing', 'category' => 'Sports', 'icon' => '🧗', 'is_featured' => false, 'sort_order' => 33],
            ['name' => 'Sailing', 'category' => 'Water Sports', 'icon' => '⛵', 'is_featured' => false, 'sort_order' => 34],
            ['name' => 'Skiing', 'category' => 'Winter Sports', 'icon' => '⛷️', 'is_featured' => false, 'sort_order' => 35],
            ['name' => 'Surfing', 'category' => 'Water Sports', 'icon' => '🏄', 'is_featured' => false, 'sort_order' => 36],
            ['name' => 'Tennis', 'category' => 'Sports', 'icon' => '🎾', 'is_featured' => false, 'sort_order' => 37],
            ['name' => 'Basketball', 'category' => 'Sports', 'icon' => '🏀', 'is_featured' => false, 'sort_order' => 38],
            ['name' => 'Football', 'category' => 'Sports', 'icon' => '🏈', 'is_featured' => false, 'sort_order' => 39],
            ['name' => 'Soccer', 'category' => 'Sports', 'icon' => '⚽', 'is_featured' => false, 'sort_order' => 40],
        ];

        foreach ($interests as $interest) {
            Interest::create($interest);
        }
    }
}
