<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Module;
class Course extends Model
{
    protected $table = 'courses';  // Assurez-vous que le nom de la table est correct
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'image', 
        'duration',
        'user_id',
        'module_id'
    ];

    // Relation vers un utilisateur (chaque cours appartient à un utilisateur)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relation vers un module (chaque cours appartient à un module)
    public function module()
    {
        return $this->belongsTo(Module::class,'module_id');
    }
    public function Planning()
    {
        return $this->hasMany(Planning::class);
    }

    use HasFactory;
}
