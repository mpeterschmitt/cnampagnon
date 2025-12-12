<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Redirect extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'url',
        'title',
        'description',
        'is_active',
        'clicks',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'clicks' => 'integer',
        ];
    }

    /**
     * Get the user that created this redirect.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all click records for this redirect.
     */
    public function clickRecords(): HasMany
    {
        return $this->hasMany(RedirectClick::class);
    }

    /**
     * Increment the clicks counter.
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Record a click with detailed tracking information.
     */
    public function recordClick(?int $userId, ?string $ipAddress, ?string $userAgent, ?string $referer): RedirectClick
    {
        $this->incrementClicks();

        return $this->clickRecords()->create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'clicked_at' => now(),
        ]);
    }
}
