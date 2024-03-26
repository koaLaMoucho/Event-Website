<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AboutUsController extends Controller
{
    public function index(): View
    {
        if (Auth::check()){
            $user = Auth::user();
            $notifications = $user->notifications;
            return view('pages.about_us', compact('notifications'));
        }
        else {
            return view('pages.about_us');
        }
    }
}
