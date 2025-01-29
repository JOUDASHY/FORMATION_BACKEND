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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->nullable()->constrained()->onDelete('set null'); // Clé étrangère vers `inscriptions`
            $table->integer('montant');
            $table->date('date_paiement');
            $table->string('type_paiement')->default('espèce'); // Colonne pour le type de paiement avec une valeur par défaut
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
