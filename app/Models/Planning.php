<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    protected $table = 'plannings';
    protected $primaryKey = 'id';
    protected $fillable = [
        'course_id',  
        'date',
        'start_time',
        'end_time',
        'room_id', // Remplacer 'room' par 'room_id'
        'formation_id'
    ];

    // Relation vers un cours
    public function courses()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // Relation vers une formation
    public function formations()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }

    // Relation vers la salle (room)
    public function rooms()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    // Relation vers les présences
    public function presences() {
        return $this->hasMany(Presence::class);
    }

    use HasFactory;
}

