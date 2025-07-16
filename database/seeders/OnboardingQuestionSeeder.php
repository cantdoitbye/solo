<?php

namespace Database\Seeders;

use App\Models\OnboardingQuestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OnboardingQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'question_key' => 'what_i_care_about',
                'question_text' => 'What I care most about is:',
                'placeholder_text' => 'e.g., Meaningful convos, local food, good energy',
                'input_type' => 'text',
                'max_length' => 200,
                'sort_order' => 1,
                'is_required' => true,
            ],
            [
                'question_key' => 'three_words_describe_me',
                'question_text' => 'Three words my friends would use to describe me:',
                'placeholder_text' => 'e.g., Chill, curious, creative',
                'input_type' => 'text',
                'max_length' => 100,
                'sort_order' => 2,
                'is_required' => true,
            ],
            [
                'question_key' => 'favorite_saturday',
                'question_text' => 'My favorite way to spend a Saturday:',
                'placeholder_text' => 'e.g., Exploring cafes or hiking with friends',
                'input_type' => 'text',
                'max_length' => 200,
                'sort_order' => 3,
                'is_required' => true,
            ],
            [
                'question_key' => 'travel_with_others',
                'question_text' => 'Somewhere I\'d love to travel with others:',
                'placeholder_text' => 'e.g., Kyoto, Japan or a beach town nearby',
                'input_type' => 'text',
                'max_length' => 200,
                'sort_order' => 4,
                'is_required' => true,
            ],
            [
                'question_key' => 'class_to_take',
                'question_text' => 'I\'ve always wanted to take a class in:',
                'placeholder_text' => 'e.g., Pottery, improv, salsa dancing',
                'input_type' => 'text',
                'max_length' => 200,
                'sort_order' => 5,
                'is_required' => true,
            ],
        ];

        foreach ($questions as $question) {
            OnboardingQuestion::create($question);
        }

    }
}
