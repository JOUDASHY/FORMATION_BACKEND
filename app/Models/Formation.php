<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    protected $table = 'formations';
    protected $primaryKey = 'id';
    protected $fillable = [
  
          'name',
          'description',
          'duration',
          'image', 
          'start_date',
          'tariff',

      ];
      public function inscriptions()
      {
          return $this->hasMany(Inscription::class,'formation_id');      }
      // Relation vers un module (chaque cours appartient à un module)
      public function modules()
      {
          return $this->hasMany(Module::class);
      }

      public function evaluations()
      {
          return $this->hasMany(Evaluation::class);
      }
      
    use HasFactory;
}