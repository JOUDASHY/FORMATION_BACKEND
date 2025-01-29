<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // Champs pouvant être remplis en masse
    protected $fillable = ['sender_id', 'receiver_id', 'message', 'is_read', 'attachment'];

    /**
     * Relation avec l'utilisateur (expéditeur).
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relation avec l'utilisateur (destinataire).
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Marquer un message comme lu.
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Vérifier si un message est lu.
     */
    public function isRead()
    {
        return $this->is_read;
    }
}
