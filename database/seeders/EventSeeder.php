<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();

        if (! $admin) {
            $admin = User::factory()->create(['is_admin' => true]);
        }

        // Créer des cours pour cette semaine et les prochaines
        Event::factory()
            ->count(20)
            ->course()
            ->thisWeek()
            ->create(['created_by' => $admin->id]);

        // Créer des cours pour le reste du mois
        Event::factory()
            ->count(30)
            ->course()
            ->create(['created_by' => $admin->id]);

        // Créer des devoirs
        Event::factory()
            ->count(10)
            ->homework()
            ->create(['created_by' => $admin->id]);

        // Créer quelques devoirs complétés
        Event::factory()
            ->count(5)
            ->homework()
            ->completed()
            ->create(['created_by' => $admin->id]);

        // Créer des examens
        Event::factory()
            ->count(3)
            ->exam()
            ->create(['created_by' => $admin->id]);

        // Créer quelques réunions
        Event::factory()
            ->count(5)
            ->meeting()
            ->create(['created_by' => $admin->id]);
    }
}
