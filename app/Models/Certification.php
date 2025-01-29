<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $table = 'certifications';  // Assurez-vous que le nom de la table est correct
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'obtention_date',
        'formation_id',

    ];
    public function users()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function formations()
    {
        return $this->belongsTo(Formation::class,'formation_id');
    }
    use HasFactory;
}
