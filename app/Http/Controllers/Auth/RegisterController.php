<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use App\Models\User;

class RegisterController extends Controller
{

    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }


    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|email|max:250|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^[0-9]{9}$/',
            ],
        ], [
            'phone_number.regex' => 'O número de telefone deve conter exatamente 9 dígitos.',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            
            'phone_number' => $request->phone_number,
            'is_admin' => false, 
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('all-events')->withSuccess('You have successfully registered & logged in!');
        }

        return back()->withErrors(['email' => 'Registration failed.'])->withInput();
    }
}
