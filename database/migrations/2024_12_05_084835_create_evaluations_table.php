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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Référence à l'utilisateur (apprenant)
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade'); // Référence au cours
            $table->float('note'); // Note de l'évaluation
            $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade'); // Ajout du champ formation_id
            $table->text('commentaire')->nullable(); // Commentaires supplémentaires
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
