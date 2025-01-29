<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms'; // Nom explicite de la table
    protected $primaryKey = 'id'; // Clé primaire (par défaut pour Laravel)

    protected $fillable = ['room_number', 'capacity']; // Colonnes modifiables

    // Relation avec les plannings
    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }
}
