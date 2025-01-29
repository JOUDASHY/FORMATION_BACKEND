<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact_response extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'response',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
