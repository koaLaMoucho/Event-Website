<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'stock',
        'description',
        'private',
        'person_buying_limit',
        'start_timestamp',
        'end_timestamp',
        'price',
        'event_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'private' => 'boolean',
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tickettype';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ticket_type_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the event associated with the ticket type.
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}