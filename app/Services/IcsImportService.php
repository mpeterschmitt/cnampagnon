<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use ICal\ICal;
use Illuminate\Support\Facades\DB;

/**
 * Service d'importation ICS vers les événements
 *
 * Ce service permet de parser un fichier ICS et de créer des événements
 * dans la base de données.
 */
class IcsImportService
{
    /**
     * Parse un fichier ICS et retourne un aperçu des événements
     *
     * @param  string  $filePath  Chemin vers le fichier ICS
     * @return array{events: array, summary: array}
     */
    public function parseIcsFile(string $filePath): array
    {
        // Parser le contenu avec johngrogg/ics-parser
        $ical = new ICal($filePath);

        $events = [];
        $summary = [
            'total' => 0,
            'courses' => 0,
            'exams' => 0,
            'other' => 0,
        ];

        // Obtenir tous les événements
        $vevents = $ical->events();

        foreach ($vevents as $index => $vevent) {
            $eventNumber = $index + 1;

            $eventData = $this->extractEventData($vevent);

            if ($eventData) {
                $events[] = $eventData;
                $summary['total']++;

                // Comptabiliser par type
                if (str_contains(strtolower($eventData['title']), 'exam') || str_contains(strtolower($eventData['title']), 'contrôle')) {
                    $summary['exams']++;
                } elseif (! empty($eventData['subject'])) {
                    $summary['courses']++;
                } else {
                    $summary['other']++;
                }
            } else {
                \Log::warning('Event #'.$eventNumber.' returned null data');
            }
        }

        return [
            'events' => $events,
            'summary' => $summary,
        ];
    }

    /**
     * Extrait les données d'un événement ICS
     */
    protected function extractEventData(\ICal\Event $vevent): ?array
    {
        try {
            $summary = $vevent->summary;
            $description = $vevent->description;
            $location = $vevent->location;
            $dtstart = $vevent->dtstart;
            $dtend = $vevent->dtend;
            $uid = $vevent->uid;

            if (! $summary || ! $dtstart) {
                \Log::warning('Event rejected: missing SUMMARY or DTSTART');

                return null;
            }

            // Convertir les dates en Carbon
            $startTime = $this->parseDatetime($dtstart);
            $endTime = $dtend ? $this->parseDatetime($dtend) : $startTime->copy()->addHour();

            // Extraire les informations du titre
            $title = $summary;
            $parsedInfo = $this->parseEventTitle($title);

            // Extraire le teacher depuis la description si présent
            $teacherFromDescription = null;
            if ($description) {
                // Chercher une ligne commençant par "teacher:" ou "Teacher:" ou "Enseignant:"
                if (preg_match('/^(?:teacher|Teacher|Enseignant|TEACHER):\s*(.+?)$/m', $description, $matches)) {
                    $teacherFromDescription = trim($matches[1]);
                }
            }

            // Helper function to sanitize UTF-8 strings
            $sanitizeUtf8 = function (?string $str): ?string {
                if ($str === null) {
                    return null;
                }

                // Remove any non-UTF-8 characters and convert to valid UTF-8
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');

                // Remove control characters except newline and tab
                $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);

                return $str;
            };

            $result = [
                'title' => $sanitizeUtf8($title),
                'description' => $sanitizeUtf8($description),
                'location' => $sanitizeUtf8($location),
                'start_time' => $startTime->toIso8601String(),
                'end_time' => $endTime->toIso8601String(),
                'subject' => $sanitizeUtf8($parsedInfo['subject'] ?? null),
                'teacher' => $sanitizeUtf8($teacherFromDescription ?? $parsedInfo['teacher'] ?? null),
                'course_type' => $sanitizeUtf8($parsedInfo['course_type'] ?? null),
                'room' => "ITII",
                'external_id' => $sanitizeUtf8($uid),
                'type' => $this->determineEventType($title, $description),
            ];

            return $result;
        } catch (\Exception $e) {
            \Log::error('extractEventData exception: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return null;
        }
    }

    /**
     * Parse une date/heure depuis le format ICS
     */
    protected function parseDatetime($datetime)
    {
        // johngrogg/ics-parser retourne généralement des timestamps ou strings
        if ($datetime instanceof Carbon) {
            return $datetime;
        }

        if (is_string($datetime)) {
            // Format ISO8601 ou ICS format: 20251201T100000Z ou timestamp
            try {
                return Carbon::parse($datetime);
            } catch (\Exception $e) {
                \Log::warning('Failed to parse datetime string: '.$datetime);

                return Carbon::now();
            }
        }

        if (is_numeric($datetime)) {
            // Timestamp Unix
            return Carbon::createFromTimestamp($datetime);
        }

        if (is_array($datetime)) {
            // Format tableau: [year, month, day, hour, minute, second]
            return Carbon::create(
                $datetime['year'] ?? null,
                $datetime['month'] ?? null,
                $datetime['day'] ?? null,
                $datetime['hour'] ?? 0,
                $datetime['min'] ?? 0,
                $datetime['sec'] ?? 0
            );
        }

        // Si c'est un objet DateTime
        if ($datetime instanceof \DateTime) {
            return Carbon::instance($datetime);
        }

        return Carbon::now();
    }

    /**
     * Parse le titre de l'événement pour extraire les informations
     *
     * Format attendu : "Matière - Type (CM/TD/TP) - Enseignant - Salle"
     */
    protected function parseEventTitle(string $title): array
    {
        $result = [
            'title' => $title,
            'subject' => null,
        ];

        // Séparer par tirets ou virgules
        $parts = preg_split('/\s*[-–—|,]\s*/', $title);

        if (count($parts) >= 2) {
            $result['subject'] = trim($parts[0]);
            $result['title'] = $result['subject'];
        }

        return $result;
    }

    /**
     * Détermine le type d'événement basé sur le titre et la description
     */
    protected function determineEventType(string $title, ?string $description): string
    {
        $titleLower = strtolower($title);
        $descLower = strtolower($description ?? '');

        if (str_contains($titleLower, 'exam') || str_contains($titleLower, 'contrôle') || str_contains($titleLower, 'partiel')) {
            return 'exam';
        }

        if (str_contains($titleLower, 'devoir') || str_contains($titleLower, 'dm') || str_contains($descLower, 'à rendre')) {
            return 'homework';
        }

        if (str_contains($titleLower, 'réunion') || str_contains($titleLower, 'meeting')) {
            return 'meeting';
        }

        return 'course';
    }

    /**
     * Importe les événements dans la base de données
     *
     * @param  array  $events  Événements à importer
     * @param  int  $userId  ID de l'utilisateur qui importe
     * @param  array  $options  Options d'importation
     * @return array{imported: int, skipped: int, errors: array}
     *
     * @throws \Throwable
     */
    public function importEvents(array $events, int $userId, array $options = []): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $replaceExisting = $options['replace_existing'] ?? false;
        $ignorePastEvents = $options['ignore_past_events'] ?? true;

        DB::beginTransaction();

        try {
            // Supprimer les événements existants si demandé
            if ($replaceExisting) {
                Event::query()
                    ->where('source', 'ics_import')
                    ->delete();
            }

            foreach ($events as $eventData) {
                try {
                    // Convert ISO8601 strings back to Carbon for database
                    if (isset($eventData['start_time']) && is_string($eventData['start_time'])) {
                        $eventData['start_time'] = Carbon::parse($eventData['start_time']);
                    }
                    if (isset($eventData['end_time']) && is_string($eventData['end_time'])) {
                        $eventData['end_time'] = Carbon::parse($eventData['end_time']);
                    }
                    if (isset($eventData['due_date']) && is_string($eventData['due_date'])) {
                        $eventData['due_date'] = Carbon::parse($eventData['due_date']);
                    }

                    // Ignorer les événements passés si demandés
                    if ($ignorePastEvents && $eventData['end_time'] < now()) {
                        $skipped++;

                        continue;
                    }

                    // Vérifier si l'événement existe déjà (basé sur external_id)
                    if (! empty($eventData['external_id'])) {
                        $existing = Event::where('external_id', $eventData['external_id'])->first();

                        if ($existing && ! $replaceExisting) {
                            $skipped++;

                            continue;
                        }

                        if ($existing) {
                            $existing->update(array_merge($eventData, [
                                'updated_by' => $userId,
                                'source' => 'ics_import',
                            ]));
                            $imported++;

                            continue;
                        }
                    }

                    // Créer un nouvel événement
                    Event::create(array_merge($eventData, [
                        'created_by' => $userId,
                        'source' => 'ics_import',
                    ]));

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'event' => $eventData['title'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Valide un fichier ICS
     *
     * @param  string  $filePath  Chemin vers le fichier
     * @return array{valid: bool, error: ?string}
     */
    public function validateIcsFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['valid' => false, 'error' => 'Le fichier n\'existe pas'];
        }

        if (! is_readable($filePath)) {
            return ['valid' => false, 'error' => 'Le fichier n\'est pas lisible'];
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'ics') {
            return ['valid' => false, 'error' => 'Le fichier doit avoir l\'extension .ics'];
        }

        try {
            // Lire le contenu du fichier
            $fileContent = file_get_contents($filePath);

            if (empty($fileContent)) {
                return ['valid' => false, 'error' => 'Le fichier est vide'];
            }

            // Parser avec johngrogg/ics-parser
            $ical = new ICal;
            $ical->initString($fileContent);

            // Vérifier la présence d'événements
            $vevents = $ical->events();

            if (empty($vevents)) {
                return ['valid' => false, 'error' => 'Le fichier ne contient aucun événement'];
            }

            return ['valid' => true, 'error' => null];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Erreur lors du parsing: '.$e->getMessage()];
        }
    }
}
