<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FAQ;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = FAQ::all();

        if (Auth::check()){
            $user = Auth::user();
            $notifications = $user->notifications;
            return view('pages.faq', compact('faqs', 'notifications'));
        }
        else {
            return view('pages.faq', compact('faqs'));
        }
       
        
    }
}
