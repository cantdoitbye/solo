<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingQuestion extends Model
{
    protected $fillable = [
        'question_key',
        'question_text',
        'placeholder_text',
        'input_type',
        'max_length',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
