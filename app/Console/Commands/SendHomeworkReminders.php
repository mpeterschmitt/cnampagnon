<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendHomeworkReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'homework:send-reminders {--all : Envoyer des notifications pour tous les devoirs futurs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie des rappels Discord pour les devoirs à venir (2 jours, 1 semaine, 2 semaines avant)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $webhookUrl = config('services.discord.webhook_url');

        if (! $webhookUrl) {
            $this->error('Discord webhook URL non configurée. Ajoutez DISCORD_WEBHOOK_URL dans votre .env');

            return self::FAILURE;
        }

        $totalSent = 0;

        // Si l'option --all est activée, envoyer pour tous les devoirs futurs
        if ($this->option('all')) {
            $this->info('Mode --all activé : envoi de notifications pour tous les devoirs futurs...');

            $homeworks = Event::homework()
                ->incomplete()
                ->where('due_date', '>', now())
                ->orderBy('due_date')
                ->get();

            foreach ($homeworks as $homework) {
                /** @var int $daysUntilDue */
                $daysUntilDue = now()->diffInDays($homework->due_date);
                $periodLabel = $this->formatPeriodLabel($daysUntilDue);

                $this->sendDiscordNotification($webhookUrl, $homework, $periodLabel);
                $totalSent++;

                $this->info("Rappel envoyé pour : {$homework->title} ({$periodLabel})");
            }
        } else {
            // Mode normal : rappels pour 2 jours, 1 semaine, 2 semaines
            $now = now();
            $reminderPeriods = [
                '2 jours' => $now->copy()->addDays(2),
                '1 semaine' => $now->copy()->addWeek(),
                '2 semaines' => $now->copy()->addWeeks(2),
            ];

            foreach ($reminderPeriods as $periodLabel => $targetDate) {
                // Trouver les devoirs dont la date de rendu est dans cette période (±12 heures)
                $homeworks = Event::homework()
                    ->incomplete()
                    ->whereBetween('due_date', [
                        $targetDate->copy()->subHours(12),
                        $targetDate->copy()->addHours(12),
                    ])
                    ->get();

                foreach ($homeworks as $homework) {
                    $this->sendDiscordNotification($webhookUrl, $homework, $periodLabel);
                    $totalSent++;

                    $this->info("Rappel envoyé pour : {$homework->title} (dans {$periodLabel})");
                }
            }
        }

        if ($totalSent === 0) {
            $this->info('Aucun rappel à envoyer pour le moment.');
        } else {
            $this->info("Total : {$totalSent} rappel(s) envoyé(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Formate le label de période en fonction du nombre de jours
     */
    protected function formatPeriodLabel(int|float $days): string
    {
        $days = (int) $days;

        return match (true) {
            $days === 0 => "aujourd'hui",
            $days === 1 => 'demain',
            $days < 7 => "dans {$days} jours",
            $days < 14 => 'dans 1 semaine',
            $days < 28 => "dans " . round($days / 7) . " semaines",
            $days < 60 => 'dans 1 mois',
            default => "dans " . round($days / 30) . " mois",
        };
    }

    /**
     * Envoie une notification Discord via webhook
     */
    protected function sendDiscordNotification(string $webhookUrl, Event $homework, string $period): void
    {
        $color = match ($homework->priority) {
            'high' => 15548997, // Rouge
            'medium' => 16776960, // Jaune
            'low' => 8421504, // Gris
            default => 3447003, // Bleu
        };

        $priorityLabel = match ($homework->priority) {
            'high' => '🔴 Priorité élevée',
            'medium' => '🟡 Priorité moyenne',
            'low' => '⚪ Priorité faible',
            default => 'Priorité non définie',
        };

        $fields = [
            [
                'name' => '📚 Matière',
                'value' => $homework->subject ?? 'Non spécifiée',
                'inline' => true,
            ],
            [
                'name' => '⏰ À rendre',
                'value' => $homework->due_date->format('d/m/Y à H:i'),
                'inline' => true,
            ],
            [
                'name' => '⚠️ Priorité',
                'value' => $priorityLabel,
                'inline' => true,
            ],
        ];

        if ($homework->teacher) {
            $fields[] = [
                'name' => '👤 Enseignant',
                'value' => $homework->teacher,
                'inline' => true,
            ];
        }

        if ($homework->location) {
            $fields[] = [
                'name' => '📍 Lieu',
                'value' => $homework->location,
                'inline' => true,
            ];
        }

        $payload = [
            'embeds' => [
                [
                    'title' => "📝 Rappel : {$homework->title}",
                    'description' => $homework->description ?? 'Devoir à rendre dans '.$period,
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => "Rappel envoyé {$period} avant l'échéance",
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        try {
            Http::post($webhookUrl, $payload);
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'envoi du webhook : {$e->getMessage()}");
        }
    }
}

