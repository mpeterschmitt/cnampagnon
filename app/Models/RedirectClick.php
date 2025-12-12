<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedirectClick extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'redirect_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'clicked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * Get the redirect that this click belongs to.
     */
    public function redirect(): BelongsTo
    {
        return $this->belongsTo(Redirect::class);
    }

    /**
     * Get the user who clicked (null if anonymous).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
