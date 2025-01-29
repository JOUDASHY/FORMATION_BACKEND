<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    protected $table = 'inscriptions'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'formation_id',
        'inscription_date',
        'payment_state',
        'payed'
    ];
    public function users()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function formations()
    {
        return $this->belongsTo(Formation::class,'formation_id');
    }
    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }
    use HasFactory;
}
