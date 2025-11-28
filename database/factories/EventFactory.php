<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+3 months');
        $endTime = (clone $startTime)->modify('+'.fake()->numberBetween(1, 4).' hours');

        return [
            'type' => 'course',
            'title' => fake()->randomElement([
                'Mathématiques',
                'Physique',
                'Informatique',
                'Électronique',
                'Mécanique',
                'Chimie',
            ]),
            'description' => fake()->optional()->sentence(),
            'location' => fake()->optional()->randomElement([
                'Amphithéâtre A',
                'Salle B101',
                'Lab C203',
                'Atelier D',
            ]),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'all_day' => false,
            'subject' => fake()->randomElement([
                'Mathématiques',
                'Physique',
                'Informatique',
                'Électronique',
                'Mécanique',
            ]),
            'teacher' => fake()->name(),
            'course_type' => fake()->randomElement(['CM', 'TD', 'TP']),
            'room' => fake()->optional()->bothify('?###'),
            'color' => fake()->optional()->hexColor(),
            'source' => 'manual',
            'created_by' => User::factory(),
        ];
    }

    /**
     * État : Cours
     */
    public function course(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'course',
            'course_type' => fake()->randomElement(['CM', 'TD', 'TP']),
            'subject' => fake()->randomElement([
                'Mathématiques',
                'Physique',
                'Informatique',
                'Électronique',
            ]),
            'teacher' => fake()->name(),
            'room' => fake()->bothify('?###'),
        ]);
    }

    /**
     * État : Devoir
     */
    public function homework(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'homework',
            'title' => fake()->randomElement([
                'DM de Mathématiques',
                'TP à rendre',
                'Projet de groupe',
                'Exercices à faire',
            ]),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'completed' => false,
            'subject' => fake()->randomElement([
                'Mathématiques',
                'Physique',
                'Informatique',
            ]),
        ]);
    }

    /**
     * État : Examen
     */
    public function exam(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'exam',
            'title' => fake()->randomElement([
                'Examen de Mathématiques',
                'Contrôle continu',
                'Partiel de Physique',
                'Examen final',
            ]),
            'subject' => fake()->randomElement([
                'Mathématiques',
                'Physique',
                'Informatique',
            ]),
            'room' => fake()->bothify('?###'),
            'color' => '#ef4444', // Rouge pour les examens
        ]);
    }

    /**
     * État : Réunion
     */
    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'meeting',
            'title' => fake()->randomElement([
                'Réunion de promotion',
                'Point projet',
                'Conseil de classe',
            ]),
            'location' => fake()->randomElement([
                'Salle de réunion A',
                'Bureau du directeur',
                'Amphithéâtre',
            ]),
        ]);
    }

    /**
     * État : Événement toute la journée
     */
    public function allDay(): static
    {
        return $this->state(function (array $attributes) {
            $date = fake()->dateTimeBetween('now', '+3 months');

            return [
                'all_day' => true,
                'start_time' => (clone $date)->setTime(0, 0),
                'end_time' => (clone $date)->setTime(23, 59),
            ];
        });
    }

    /**
     * État : Devoir complété
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => true,
        ]);
    }

    /**
     * État : Événement importé depuis ICS
     */
    public function fromIcs(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'ics_import',
            'external_id' => fake()->uuid(),
        ]);
    }

    /**
     * État : Événement de cette semaine
     */
    public function thisWeek(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = fake()->dateTimeBetween('monday this week', 'friday this week');
            $endTime = (clone $startTime)->modify('+'.fake()->numberBetween(1, 4).' hours');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }
}
