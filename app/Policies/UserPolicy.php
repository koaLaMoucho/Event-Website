<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function updateProfile(User $loggedInUser, User $targetUser)
    {
        // Verifica se o usuário autenticado está atualizando seu próprio perfil
        return $loggedInUser->user_id === $targetUser->user_id;
    }
}
