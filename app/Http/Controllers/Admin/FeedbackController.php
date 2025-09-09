<?php
// app/Http/Controllers/Admin/FeedbackController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of feedbacks
     */
    public function index(Request $request)
    {
        $feedbacks = Feedback::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.feedbacks.index', compact('feedbacks'));
    }

    /**
     * Display the specified feedback
     */
    public function show(int $id)
    {
        $feedback = Feedback::with('user')->findOrFail($id);
        
        return view('admin.feedbacks.show', compact('feedback'));
    }

    /**
     * Remove the specified feedback from storage
     */
    public function destroy(int $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return redirect()->route('admin.feedbacks.index')
            ->with('success', 'Feedback deleted successfully');
    }
}