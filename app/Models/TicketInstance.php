<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketInstance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_type_id',
        'order_id',
        'qr_code_path'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ticketinstance';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ticket_instance_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the ticket type associated with the ticket instance.
     */
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id', 'ticket_type_id');
    }

    /**
     * Get the order associated with the ticket instance.
     */
    public function order()
    {
        return $this->belongsTo(TicketOrder::class, 'order_id', 'order_id');
    }
}