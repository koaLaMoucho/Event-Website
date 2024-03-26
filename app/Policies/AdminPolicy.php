<?php

namespace App\Policies;

use Illuminate\Support\Facades\Auth;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    public function verifyAdmin() {
        return Auth::check() && Auth::user()->is_admin;
    } 
}
