<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = ['inscription_id', 'montant', 'type_paiement','date_paiement'];

    public function inscriptions()
    {
        return $this->belongsTo(Inscription::class,'inscription_id')->withDefault();
    }
}



