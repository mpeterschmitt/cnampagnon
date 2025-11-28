<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service d'exportation ICS depuis les événements
 *
 * Ce service permet de créer un fichier ICS à partir des événements
 * de la base de données pour l'importation dans des calendriers externes.
 */
class IcsExportService
{
    /**
     * Génère un fichier ICS à partir d'une collection d'événements
     *
     * @param  Collection<int, Event>  $events
     */
    public function generateIcs(Collection $events, string $calendarName = 'Emploi du Temps'): string
    {
        $lines = [];

        // En-tête du fichier ICS
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Emploi du Temps//FR';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:'.$this->escapeString($calendarName);
        $lines[] = 'X-WR-TIMEZONE:Europe/Paris';
        $lines[] = 'X-WR-CALDESC:Emploi du temps exporté';

        // Ajouter chaque événement
        foreach ($events as $event) {
            $lines = array_merge($lines, $this->generateEventLines($event));
        }

        // Fin du fichier ICS
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    /**
     * Génère les lignes ICS pour un événement
     *
     * @return array<int, string>
     */
    protected function generateEventLines(Event $event): array
    {
        $lines = [];

        $lines[] = 'BEGIN:VEVENT';

        // UID unique pour l'événement
        $lines[] = 'UID:event-'.$event->id.'@emploidutemps.local';

        // Date de création et modification
        $lines[] = 'DTSTAMP:'.$this->formatDateTime($event->created_at ?? now());
        $lines[] = 'CREATED:'.$this->formatDateTime($event->created_at ?? now());
        $lines[] = 'LAST-MODIFIED:'.$this->formatDateTime($event->updated_at ?? now());

        // Dates de début et fin
        if ($event->all_day) {
            $lines[] = 'DTSTART;VALUE=DATE:'.$this->formatDate($event->start_time);
            $lines[] = 'DTEND;VALUE=DATE:'.$this->formatDate($event->end_time ?? $event->start_time->copy()->addDay());
        } else {
            $lines[] = 'DTSTART:'.$this->formatDateTime($event->start_time);
            $lines[] = 'DTEND:'.$this->formatDateTime($event->end_time ?? $event->start_time->copy()->addHour());
        }

        // Titre de l'événement
        $summary = $this->buildEventTitle($event);
        $lines[] = 'SUMMARY:'.$this->escapeString($summary);

        // Description
        $description = $this->buildEventDescription($event);
        if ($description) {
            $lines[] = 'DESCRIPTION:'.$this->escapeString($description);
        }

        // Lieu
        if ($event->location || $event->room) {
            $location = $event->location ?? $event->room;
            $lines[] = 'LOCATION:'.$this->escapeString($location);
        }

        // Catégorie basée sur le type
        $category = match ($event->type) {
            'course' => 'COURS',
            'exam' => 'EXAMEN',
            'homework' => 'DEVOIR',
            'meeting' => 'REUNION',
            default => 'AUTRE',
        };
        $lines[] = 'CATEGORIES:'.$category;

        // Couleur si définie (non-standard mais supporté par certains clients)
        if ($event->color) {
            $lines[] = 'COLOR:'.$event->color;
        }

        // Statut
        if ($event->type === 'homework') {
            $lines[] = 'STATUS:'.($event->completed ? 'COMPLETED' : 'NEEDS-ACTION');
        } else {
            $lines[] = 'STATUS:CONFIRMED';
        }

        // Priorité pour les devoirs
        if ($event->type === 'homework' && $event->priority) {
            // ICS priority: 0=undefined, 1=high, 5=medium, 9=low
            $icalPriority = match ($event->priority) {
                'high' => 1,
                'low' => 9,
                default => 5,
            };
            $lines[] = 'PRIORITY:'.$icalPriority;
        }

        // Transparence (afficher comme libre/occupé)
        $lines[] = 'TRANSP:OPAQUE';

        // Séquence pour le versioning
        $lines[] = 'SEQUENCE:0';

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Construit le titre de l'événement pour l'export ICS
     */
    protected function buildEventTitle(Event $event): string
    {
        $parts = [$event->title];

        if ($event->course_type) {
            $parts[] = '['.$event->course_type.']';
        }

        return implode(' ', $parts);
    }

    /**
     * Construit la description de l'événement pour l'export ICS
     */
    protected function buildEventDescription(Event $event): string
    {
        $parts = [];

        if ($event->description) {
            $parts[] = $event->description;
        }

        if ($event->subject) {
            $parts[] = 'Matière: '.$event->subject;
        }

        if ($event->teacher) {
            $parts[] = 'Enseignant: '.$event->teacher;
        }

        if ($event->room) {
            $parts[] = 'Salle: '.$event->room;
        }

        if ($event->type === 'homework' && $event->due_date) {
            $parts[] = 'À rendre pour le: '.$event->due_date->format('d/m/Y H:i');
        }

        return implode("\n", $parts);
    }

    /**
     * Formate une date/heure au format ICS (YYYYMMDDTHHMMSSZ)
     */
    protected function formatDateTime(Carbon $dateTime): string
    {
        return $dateTime->copy()->setTimezone('UTC')->format('Ymd\THis\Z');
    }

    /**
     * Formate une date au format ICS pour événements toute la journée (YYYYMMDD)
     */
    protected function formatDate(Carbon $date): string
    {
        return $date->format('Ymd');
    }

    /**
     * Échappe les caractères spéciaux pour ICS
     */
    protected function escapeString(string $text): string
    {
        // Remplacer les caractères spéciaux selon RFC 5545
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);

        return $text;
    }

    /**
     * Génère un nom de fichier ICS basé sur les paramètres
     */
    public function generateFilename(?string $startDate = null, ?string $endDate = null): string
    {
        $parts = ['emploi-du-temps'];

        if ($startDate && $endDate) {
            $parts[] = Carbon::parse($startDate)->format('Y-m-d');
            $parts[] = 'au';
            $parts[] = Carbon::parse($endDate)->format('Y-m-d');
        } elseif ($startDate) {
            $parts[] = Carbon::parse($startDate)->format('Y-m-d');
        } else {
            $parts[] = now()->format('Y-m-d');
        }

        return implode('-', $parts).'.ics';
    }
}
