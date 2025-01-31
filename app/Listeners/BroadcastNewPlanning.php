<?php
// app/Listeners/BroadcastNewPlanning.php

namespace App\Listeners;

use App\Events\NewPlanningEvent;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastNewPlanning
{
    public function handle(NewPlanningEvent $event)
    {
        $messagePrefix = $event->isCancelled ? "Le cours de {$event->CourseName} prévu le {$event->date} de {$event->start_time} à {$event->end_time} pour la formation {$event->formationTitle} en salle {$event->room} a été annulé." : 
                                               "Vous aurez du cours de {$event->CourseName} le {$event->date} de {$event->start_time} à {$event->end_time} pour la formation {$event->formationTitle} en salle {$event->room}.";

        foreach ($event->apprenants as $apprenant) {
            // Enregistrer la notification avec le message approprié
            Notification::create([
                'user_id' => $apprenant->id,
                'message' => $messagePrefix,
                'is_read' => false,
            ]);

            // Envoyer la requête WebSocket
            $response = Http::post(config('app.socket_notif_url', 'http://localhost:3000') . '/broadcast', [
                'userId' => $apprenant->id,
                'message' => $messagePrefix,
            ]);

            Log::info("Réponse du serveur WebSocket pour l'utilisateur {$apprenant->id}: " . $response->body());

            if ($response->failed()) {
                Log::error("Échec de la diffusion au WebSocket pour l'utilisateur {$apprenant->id}");
            }
        }

        // Enregistrer la notification pour le formateur
        Notification::create([
            'user_id' => $event->formateurId,
            'message' => $messagePrefix,
            'is_read' => false,
        ]);

        // Envoyer la requête WebSocket pour le formateur
        $response = Http::post(config('app.socket_notif_url', 'http://localhost:3000') . '/broadcast', [
            'userId' => $event->formateurId,
            'message' => $messagePrefix,
        ]);

        Log::info("Réponse du serveur WebSocket pour le formateur {$event->formateurId}: " . $response->body());

        if ($response->failed()) {
            Log::error("Échec de la diffusion au WebSocket pour le formateur {$event->formateurId}");
        }
    }
}
