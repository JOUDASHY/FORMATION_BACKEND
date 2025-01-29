<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['title', 'description', 'file_path', 'course_id', 'user_id'];

    public function courses()
    {
        return $this->belongsTo(Course::class,'course_id');
    }

    public function users()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
