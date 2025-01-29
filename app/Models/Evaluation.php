<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'formation_id', // Ajout du champ formation_id
        'note',
        'commentaire',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class,'course_id');
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class,'formation_id');
    }
}
