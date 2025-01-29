<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    protected $fillable = ['planning_id', 'user_id', 'status'];

    public function planning() {
        return $this->belongsTo(Planning::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
