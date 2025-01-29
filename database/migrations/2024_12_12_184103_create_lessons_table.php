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
        // Migration pour la table lessons
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Titre de la leçon
            $table->text('description')->nullable(); // Description de la leçon
            $table->string('file_path')->nullable(); // Chemin vers la vidéo, PDF ou image
            $table->foreignId('course_id')->constrained()->onDelete('cascade'); // Clé étrangère vers la table courses
            $table->foreignId('user_id')->constrained('users'); // Formateur qui a créé la leçon
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
