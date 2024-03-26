<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'text',
        'media',
        'private',
        'event_id',
        'author_id',
        'likes',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comment_';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'comment_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the event associated with the comment.
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * Get the author associated with the comment.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'comment_id');
    }

    public function isReported()
    {
        $userId = auth()->user()->user_id;

        return $this->reports()->where('author_id', $userId)->exists();
    }

    public function notifications()
{
    return $this->hasMany(Notification::class, 'comment_id');
}



public function likes()
    {
        return $this->hasMany(UserLikes::class, 'comment_id');
    }




}