<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventImage extends Model
{
    use HasFactory;

    protected $table = 'eventimage';
    protected $primaryKey = 'event_image_id';

    protected $fillable = [
        'event_id',
        'image_path',
    ];

        /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}