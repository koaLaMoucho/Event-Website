<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'timestamp',
        'notified_user',
        'event_id',
        'comment_id',
        'report_id',
        'viewed',
        'notification_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'viewed' => 'boolean',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_';

    /**
     * Primary key
     *
     * @var string
     */
    protected $primaryKey = 'notification_id';
    
    /**
     * Get the user that was notified.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notified_user', 'user_id');
    }

    /**
     * Get the event associated with the notification.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * Get the comment associated with the notification.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'comment_id', 'comment_id');
    }

    /**
     * Get the report associated with the notification.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id', 'report_id');
    }
}