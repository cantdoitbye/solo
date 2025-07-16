<?php

namespace Database\Seeders;

use App\Models\EventTag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Morning activities
            [
                'name' => 'Morning Run',
                'slug' => 'morning-run',
                'color' => '#FF6B6B',
                'category' => 'morning',
                'sort_order' => 1,
                'is_featured' => true
            ],
            [
                'name' => 'Sunrise Yoga',
                'slug' => 'sunrise-yoga',
                'color' => '#4ECDC4',
                'category' => 'morning',
                'sort_order' => 2,
                'is_featured' => true
            ],
            [
                'name' => 'Morning Walk',
                'slug' => 'morning-walk',
                'color' => '#45B7D1',
                'category' => 'morning',
                'sort_order' => 3
            ],
            [
                'name' => 'Breakfast Meet',
                'slug' => 'breakfast-meet',
                'color' => '#F39C12',
                'category' => 'morning',
                'sort_order' => 4
            ],
            
            // Social activities
            [
                'name' => 'Happy Hour',
                'slug' => 'happy-hour',
                'color' => '#9B59B6',
                'category' => 'social',
                'sort_order' => 1,
                'is_featured' => true
            ],
            [
                'name' => 'Networking',
                'slug' => 'networking',
                'color' => '#3498DB',
                'category' => 'social',
                'sort_order' => 2
            ],
            [
                'name' => 'Board Games',
                'slug' => 'board-games',
                'color' => '#E74C3C',
                'category' => 'social',
                'sort_order' => 3
            ],
            [
                'name' => 'Wine Tasting',
                'slug' => 'wine-tasting',
                'color' => '#8E44AD',
                'category' => 'social',
                'sort_order' => 4
            ],
            [
                'name' => 'Karaoke',
                'slug' => 'karaoke',
                'color' => '#F1C40F',
                'category' => 'social',
                'sort_order' => 5
            ],
            
            // Sports & Fitness
            [
                'name' => 'Gym Workout',
                'slug' => 'gym-workout',
                'color' => '#E67E22',
                'category' => 'fitness',
                'sort_order' => 1
            ],
            [
                'name' => 'Hiking',
                'slug' => 'hiking',
                'color' => '#27AE60',
                'category' => 'fitness',
                'sort_order' => 2,
                'is_featured' => true
            ],
            [
                'name' => 'Tennis',
                'slug' => 'tennis',
                'color' => '#2ECC71',
                'category' => 'fitness',
                'sort_order' => 3
            ],
            [
                'name' => 'Swimming',
                'slug' => 'swimming',
                'color' => '#1ABC9C',
                'category' => 'fitness',
                'sort_order' => 4
            ],
            [
                'name' => 'Cycling',
                'slug' => 'cycling',
                'color' => '#16A085',
                'category' => 'fitness',
                'sort_order' => 5
            ],
            
            // Food & Dining
            [
                'name' => 'Dinner',
                'slug' => 'dinner',
                'color' => '#D35400',
                'category' => 'food',
                'sort_order' => 1,
                'is_featured' => true
            ],
            [
                'name' => 'Lunch',
                'slug' => 'lunch',
                'color' => '#F39C12',
                'category' => 'food',
                'sort_order' => 2
            ],
            [
                'name' => 'Coffee',
                'slug' => 'coffee',
                'color' => '#8B4513',
                'category' => 'food',
                'sort_order' => 3
            ],
            [
                'name' => 'Brunch',
                'slug' => 'brunch',
                'color' => '#FF8C00',
                'category' => 'food',
                'sort_order' => 4
            ],
            [
                'name' => 'Food Tour',
                'slug' => 'food-tour',
                'color' => '#DC143C',
                'category' => 'food',
                'sort_order' => 5
            ],
            
            // Entertainment
            [
                'name' => 'Movies',
                'slug' => 'movies',
                'color' => '#2C3E50',
                'category' => 'entertainment',
                'sort_order' => 1
            ],
            [
                'name' => 'Concert',
                'slug' => 'concert',
                'color' => '#8E44AD',
                'category' => 'entertainment',
                'sort_order' => 2
            ],
            [
                'name' => 'Theater',
                'slug' => 'theater',
                'color' => '#C0392B',
                'category' => 'entertainment',
                'sort_order' => 3
            ],
            [
                'name' => 'Comedy Show',
                'slug' => 'comedy-show',
                'color' => '#F39C12',
                'category' => 'entertainment',
                'sort_order' => 4
            ],
            
            // Learning
            [
                'name' => 'Workshop',
                'slug' => 'workshop',
                'color' => '#3498DB',
                'category' => 'learning',
                'sort_order' => 1
            ],
            [
                'name' => 'Book Club',
                'slug' => 'book-club',
                'color' => '#9B59B6',
                'category' => 'learning',
                'sort_order' => 2
            ],
            [
                'name' => 'Language Exchange',
                'slug' => 'language-exchange',
                'color' => '#E74C3C',
                'category' => 'learning',
                'sort_order' => 3
            ],
            [
                'name' => 'Skill Share',
                'slug' => 'skill-share',
                'color' => '#1ABC9C',
                'category' => 'learning',
                'sort_order' => 4
            ],
            
            // Outdoor
            [
                'name' => 'Beach Day',
                'slug' => 'beach-day',
                'color' => '#3498DB',
                'category' => 'outdoor',
                'sort_order' => 1
            ],
            [
                'name' => 'Picnic',
                'slug' => 'picnic',
                'color' => '#27AE60',
                'category' => 'outdoor',
                'sort_order' => 2
            ],
            [
                'name' => 'Camping',
                'slug' => 'camping',
                'color' => '#8B4513',
                'category' => 'outdoor',
                'sort_order' => 3
            ],
            [
                'name' => 'Nature Photography',
                'slug' => 'nature-photography',
                'color' => '#2ECC71',
                'category' => 'outdoor',
                'sort_order' => 4
            ]
        ];

        foreach ($tags as $tag) {
            EventTag::create($tag);
        }
    }
}
