<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\View\View;

use Illuminate\Validation\Rule;


use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function getCurrentUser()
    {
        $user = Auth::user();
        $notifications = $user->notifications;
        return view('pages.profile', compact('user', 'notifications'));
    }

   public function updateProfile(Request $request)
{
    $user = Auth::user();

    if (User::emailExists($request->input('edit_email'), $user->user_id)) {
        return response()->json(['message' => 'The email address is already in use by another user.']);
    }

    $request->validate([
        'edit_email' => [
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')
        ],
        'edit_name' => 'required|string|max:255',
        
        'edit_phone_number' => [
            'required',
            'string',
            'max:20',
            'regex:/^[0-9]+$/'
        ],
], 
    ['edit_email.unique' => 'The email address is already in use.',
        'phone_number.numeric' => 'The phone number must be a number.',
        'phone_number.regex' => 'The phone number must only contain numbers.']);

    $user->update([
        'email' => $request->input('edit_email'),
        'name' => $request->input('edit_name'),
        
        'phone_number' => $request->input('edit_phone_number'),
    ]);

    
    $user->save();

    return response()->json(['message' => 'Perfil atualizado com sucesso']);
}
public function editProfile()
{
    
    $user = auth()->user();

    return view('edit_profile', compact('user'));
}
}
