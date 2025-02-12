<?php
// app/Listeners/BroadcastNewFormation.php
// app/Listeners/BroadcastNewFormation.php
namespace App\Listeners;

use App\Events\NewInscriptionEvent;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastNewInscription
{
    public function handle(NewInscriptionEvent $event)
    {
        if ($event->action === 'created') {
            $message = "L'apprenant {$event->apprenant} a effectué le paiement de l'inscription à la formation {$event->formationTitle} pour la somme de {$event->payed}";
        } elseif ($event->action === 'deleted') {
            $message = "L'apprenant {$event->apprenant} a été désinscrit de la formation {$event->formationTitle}";  // Remarque ici : "désinscrit"
        }
        
        foreach ($event->admins as $admin) {
            // Enregistrer la notification en base de données
            Notification::create([
                'user_id' => $admin->id,
    'message' => $message,

                'is_read' => false,
            ]);

            // Envoyer une requête HTTP au serveur WebSocket Node.js
        // Envoi de la requête au serveur WebSocket
$response = Http::post(config('app.socket_url', 'http://localhost:3000') . '/api/notifications/broadcast', [
    'userId' => $admin->id,
'message' => $message,

]);

Log::info("Réponse du serveur WebSocket pour l'utilisateur {$admin->id}: " . $response->body());

if ($response->failed()) {
    Log::error("Échec de la diffusion au WebSocket pour l'utilisateur {$admin->id}");
}

        }
    }
}
