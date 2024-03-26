<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_id',
        'type',
        'comment_id',
        'author_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'report_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the comment associated with the report.
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id', 'comment_id');
    }

    /**
     * Get the author associated with the report.
     */
    public function author()
    {
        return $this->belongsTo(UserClass::class, 'author_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'report_id');
    }

}