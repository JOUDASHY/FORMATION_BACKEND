<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InscriptionNotification extends Notification
{
    use Queueable;

    public $inscription;

    public function __construct($inscription)
    {
        $this->inscription = $inscription;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Nouvel apprenant inscrit : ' . $this->inscription->user->name,
            'inscription_id' => $this->inscription->id
        ];
    }
}
