<?php
// app/Listeners/BroadcastNewFormation.php
// app/Listeners/BroadcastNewFormation.php
namespace App\Listeners;

use App\Events\NewFormationEvent;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastNewFormation
{
    public function handle(NewFormationEvent $event)
    {
        foreach ($event->apprenants as $apprenant) {
            // Enregistrer la notification en base de données
            Notification::create([
                'user_id' => $apprenant->id,
                'message' => "Une nouvelle formation intitulée {$event->formationTitle} est maintenant disponible pour inscription.",
                'is_read' => false,
            ]);

            // Envoyer une requête HTTP au serveur WebSocket Node.js
        // Envoi de la requête au serveur WebSocket
$response = Http::post(config('app.socket_notif_url', 'http://localhost:3000') . '/broadcast', [
    'userId' => $apprenant->id,
    'message' => "Une nouvelle formation intitulée {$event->formationTitle} est maintenant disponible pour inscription.",
]);

Log::info("Réponse du serveur WebSocket pour l'utilisateur {$apprenant->id}: " . $response->body());

if ($response->failed()) {
    Log::error("Échec de la diffusion au WebSocket pour l'utilisateur {$apprenant->id}");
}

        }
    }
}
