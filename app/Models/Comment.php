<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'postforum_id', 'comment_body', 'file'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postforum()
    {
        return $this->belongsTo(Postforum::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }
}
