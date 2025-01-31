<?php
// app/Listeners/BroadcastNewFormation.php
// app/Listeners/BroadcastNewFormation.php
namespace App\Listeners;

use App\Events\NewLessonEvent;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastNewLesson
{
    public function handle(NewLessonEvent $event)
    {
        foreach ($event->apprenant_inscrits as $apprenant_inscrit) {
            // Enregistrer la notification en base de données
            Notification::create([
                'user_id' => $apprenant_inscrit->id,
                'message' => "Vous avez une nouvelle leçon sur {$event->title}, par {$event->formateur} dans le cours {$event->course}.",

                'is_read' => false,
            ]);

            // Envoyer une requête HTTP au serveur WebSocket Node.js
        // Envoi de la requête au serveur WebSocket
$response = Http::post(config('app.socket_notif_url', 'http://localhost:3000') . '/broadcast', [
    'userId' => $apprenant_inscrit->id,
    'message' => "Vous avez une nouvelle leçon sur {$event->title}, par {$event->formateur} dans le cours {$event->course}.",

]);

Log::info("Réponse du serveur WebSocket pour l'utilisateur {$apprenant_inscrit->id}: " . $response->body());

if ($response->failed()) {
    Log::error("Échec de la diffusion au WebSocket pour l'utilisateur {$apprenant_inscrit->id}");
}

        }
    }
}
