<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rating',
        'event_id',
        'author_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rating';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'rating_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the event associated with the rating.
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * Get the author associated with the rating.
     */
    public function author()
    {
        return $this->belongsTo(UserClass::class, 'author_id', 'user_id');
    }

   
}