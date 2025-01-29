<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';  
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'email',
        'message', 
    ];
    public function responses()
    {
        return $this->hasMany(Contact_response::class);
    }
}
