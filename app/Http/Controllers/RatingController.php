<?php

namespace App\Http\Controllers;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function submitRating(Request $request, $eventId)
    {
      
        $request->validate([
            'rate' => 'required|integer|between:1,5',
        ]);

      
        $rating = new Rating();
        $rating->event_id = $eventId;
        $rating->author_id = auth()->user()->user_id;
        $rating->rating = $request->input('rate');
        $rating->save();

      
        return redirect()->back()->with('success', 'Rating submitted successfully');
    }

    public function editRating(Request $request, $eventId)
    {
        

        $userRating = Rating::where('event_id', $eventId)
            ->where('author_id', auth()->user()->user_id)
            ->first();

        if ($userRating) {
            $userRating->update([
                'rating' => $request->input('rate'),
            ]);
        }

        return redirect()->back()->with('success', 'Rating edited successfully');
    }
}
