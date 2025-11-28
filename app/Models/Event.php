<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle Event - Représente un événement générique dans le calendrier
 *
 * Peut être utilisé pour :
 * - Cours (type: course)
 * - Devoirs (type: homework)
 * - Examens (type: exam)
 * - Réunions (type: meeting)
 * - Autres événements personnalisés
 */
class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'all_day',
        'subject',
        'teacher',
        'course_type',
        'room',
        'due_date',
        'priority',
        'completed',
        'color',
        'metadata',
        'source',
        'external_id',
        'is_recurring',
        'recurrence_rule',
        'parent_event_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'due_date' => 'datetime',
            'all_day' => 'boolean',
            'completed' => 'boolean',
            'is_recurring' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Relation : Utilisateur qui a créé l'événement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation : Utilisateur qui a mis à jour l'événement
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation : Événement parent (pour les événements récurrents)
     */
    public function parentEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'parent_event_id');
    }

    /**
     * Relation : Événements enfants (occurrences d'un événement récurrent)
     */
    public function childEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'parent_event_id');
    }

    /**
     * Scope : Filtrer par type d'événement
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope : Cours uniquement
     */
    public function scopeCourses($query)
    {
        return $query->where('type', 'course');
    }

    /**
     * Scope : Devoirs uniquement
     */
    public function scopeHomework($query)
    {
        return $query->where('type', 'homework');
    }

    /**
     * Scope : Examens uniquement
     */
    public function scopeExams($query)
    {
        return $query->where('type', 'exam');
    }

    /**
     * Scope : Événements dans une plage de dates
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_time', [$startDate, $endDate])
                ->orWhereBetween('end_time', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate);
                });
        });
    }

    /**
     * Scope : Événements d'une semaine spécifique
     */
    public function scopeForWeek($query, $date)
    {
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();

        return $query->betweenDates($startOfWeek, $endOfWeek);
    }

    public function scopeForMonth($query, $date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        return $query->betweenDates($startOfMonth, $endOfMonth);
    }

    /**
     * Scope : Événements à venir
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>=', now())->orderBy('start_time');
    }

    /**
     * Scope : Événements passés
     */
    public function scopePast($query)
    {
        return $query->where('end_time', '<', now())->orderBy('start_time', 'desc');
    }

    /**
     * Scope : Devoirs non complétés
     */
    public function scopeIncomplete($query)
    {
        return $query->where('type', 'homework')->where('completed', false);
    }

    /**
     * Scope : Filtrer par matière
     */
    public function scopeForSubject($query, ?string $subject)
    {
        if ($subject) {
            return $query->where('subject', $subject);
        }

        return $query;
    }

    /**
     * Scope : Filtrer par enseignant
     */
    public function scopeForTeacher($query, ?string $teacher)
    {
        if ($teacher) {
            return $query->where('teacher', $teacher);
        }

        return $query;
    }

    /**
     * Scope : Filtrer par type de cours
     */
    public function scopeForCourseType($query, ?string $courseType)
    {
        if ($courseType) {
            return $query->where('course_type', $courseType);
        }

        return $query;
    }

    /**
     * Vérifie si l'événement est un cours
     */
    public function isCourse(): bool
    {
        return $this->type === 'course';
    }

    /**
     * Vérifie si l'événement est un devoir
     */
    public function isHomework(): bool
    {
        return $this->type === 'homework';
    }

    /**
     * Vérifie si l'événement est un examen
     */
    public function isExam(): bool
    {
        return $this->type === 'exam';
    }

    /**
     * Obtient la durée de l'événement en minutes
     */
    public function getDurationInMinutes(): int
    {
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Obtient la durée de l'événement formatée
     */
    public function getFormattedDuration(): string
    {
        $minutes = $this->getDurationInMinutes();
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h{$remainingMinutes}";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$remainingMinutes}min";
    }

    /**
     * Vérifie si l'événement est en cours
     */
    public function isOngoing(): bool
    {
        return now()->between($this->start_time, $this->end_time);
    }

    /**
     * Vérifie si l'événement est terminé
     */
    public function isFinished(): bool
    {
        return now()->isAfter($this->end_time);
    }

    /**
     * Vérifie si l'événement est à venir
     */
    public function isUpcoming(): bool
    {
        return now()->isBefore($this->start_time);
    }
}
