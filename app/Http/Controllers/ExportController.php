<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\IcsExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contrôleur pour l'exportation des événements
 *
 * Gère l'exportation des événements au format ICS pour l'importation
 * dans des calendriers externes (Google Calendar, Outlook, Apple Calendar, etc.)
 */
class ExportController extends Controller
{
    public function __construct(
        protected IcsExportService $icsExportService
    ) {}

    /**
     * Exporte les événements au format ICS
     */
    public function exportIcs(Request $request): Response
    {
        // Valider les paramètres
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'subject' => 'nullable|string',
            'teacher' => 'nullable|string',
            'course_type' => 'nullable|string|in:CM,TD,TP',
            'type' => 'nullable|string|in:course,exam,homework,meeting',
        ]);

        // Construire la requête des événements
        $query = Event::query();

        // Filtrer par dates
        if (isset($validated['start_date']) && isset($validated['end_date'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $query->betweenDates($startDate, $endDate);
        } elseif (isset($validated['start_date'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $query->forWeek($startDate);
        } else {
            // Par défaut, exporter le mois prochain
            $query->forMonth(Carbon::now()->addMonth());
        }

        // Appliquer les filtres
        if (isset($validated['subject'])) {
            $query->forSubject($validated['subject']);
        }

        if (isset($validated['teacher'])) {
            $query->forTeacher($validated['teacher']);
        }

        if (isset($validated['course_type'])) {
            $query->forCourseType($validated['course_type']);
        }

        if (isset($validated['type'])) {
            $query->ofType($validated['type']);
        }

        // Récupérer les événements
        $events = $query->orderBy('start_time')->get();

        // Générer le contenu ICS
        $icsContent = $this->icsExportService->generateIcs($events, 'Emploi du Temps');

        // Générer le nom du fichier
        $filename = $this->icsExportService->generateFilename(
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        // Retourner la réponse avec le fichier ICS
        return response($icsContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
