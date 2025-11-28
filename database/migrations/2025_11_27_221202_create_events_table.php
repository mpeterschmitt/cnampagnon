<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Type d'événement (course, homework, exam, meeting, etc.)
            $table->string('type')->default('course');

            // Informations de base
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            // Dates et heures
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('all_day')->default(false);

            // Informations spécifiques aux cours
            $table->string('subject')->nullable();
            $table->string('teacher')->nullable();
            $table->string('course_type')->nullable(); // CM, TD, TP
            $table->string('room')->nullable();

            // Informations pour les devoirs
            $table->dateTime('due_date')->nullable();
            $table->string('priority')->nullable(); // low, medium, high
            $table->boolean('completed')->default(false);

            // Métadonnées
            $table->string('color')->nullable();
            $table->json('metadata')->nullable();

            // Source de l'événement
            $table->string('source')->nullable(); // ics_import, manual, api
            $table->string('external_id')->nullable(); // Pour synchronisation ICS

            // Récurrence (pour future implémentation)
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->foreignId('parent_event_id')->nullable()->constrained('events')->onDelete('cascade');

            // Gestion
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Index pour améliorer les performances
            $table->index('type');
            $table->index('start_time');
            $table->index('end_time');
            $table->index(['start_time', 'end_time']);
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
