<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{   protected $table = 'modules';
    protected $primaryKey = 'id';
    protected $fillable = [
          'name',
          'description',
     
          'image',
          'formation_id',
        //   'inscription_id',
      
      ];


  
      // Relation vers un module (chaque cours appartient à un module)
      public function courses()
      {
          return $this->hasMany(Course::class);
      }

      public function formation()
      {
          return $this->belongsTo(Formation::class, 'formation_id');
      }
      
 
    use HasFactory;
}
