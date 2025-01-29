<?php
// app/Listeners/BroadcastNewFormation.php
// app/Listeners/BroadcastNewFormation.php
namespace App\Listeners;

use App\Events\NewCertificationEvent;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastNewCertification
{
    public function handle(NewCertificationEvent $event)
    {
        foreach ($event->apprenants as $apprenant) {
            // Enregistrer la notification en base de données
            Notification::create([
                'user_id' => $apprenant->id,
                'message' => "Vous serez certifiée de la formation {$event->formationTitle} le {$event->obtention_date}",
                'is_read' => false,
            ]);

            // Envoyer une requête HTTP au serveur WebSocket Node.js
        // Envoi de la requête au serveur WebSocket
$response = Http::post('http://localhost:6001/broadcast', [
    'userId' => $apprenant->id,
    'message' => "Vous serez certifiée de la formation {$event->formationTitle} le {$event->obtention_date}",

]);

Log::info("Réponse du serveur WebSocket pour l'utilisateur {$apprenant->id}: " . $response->body());

if ($response->failed()) {
    Log::error("Échec de la diffusion au WebSocket pour l'utilisateur {$apprenant->id}");
}

        }
    }
}
