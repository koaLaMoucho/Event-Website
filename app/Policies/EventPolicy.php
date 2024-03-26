<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    public function myEvents(User $user, Event $event) : bool
    {
      return $user->user_id === $event->creator_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function auth(User $user): bool
    {
        return Auth::check();
    }

    /**
     * Determine whether the user can create models.
     */
    public function createEvent(User $user): bool
    {
        return Auth::check();
    }

    public function createTicketType(Event $event): bool
    {
        $user = Auth::user();
        return $user->user_id === $event->organizer_id;
    }
    
    /**
     * Determine whether the user can update the model.
     */
    public function updateEvent(User $user, Event $event): bool
    {
        // Somente o criador do evento pode atualizá-lo
        return $user->user_id === $event->creator_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        //
    }

    public function purchaseTickets(User $user, Event $event): bool
    {
        return Auth::check();
    }


}
