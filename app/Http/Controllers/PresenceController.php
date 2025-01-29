<?php

namespace App\Http\Controllers;


use App\Models\Inscription; // Importez le modèle Inscription
use App\Models\Planning;
use App\Models\Presence;
use Illuminate\Http\Request;
use App\Models\User;

class PresenceController extends Controller
{
    public function markPresence(Request $request) {
        $validated = $request->validate([
            'planning_id' => 'required|exists:plannings,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:présent,absent'
        ]);

        $user = User::find($validated['user_id']);

        // Vérifier que l'utilisateur est un apprenant
        if (!$user || !$user->isApprenant()) {
            return response()->json(['error' => 'L\'utilisateur doit être de type apprenant.'], 400);
        }

        // Enregistrer la présence
        $presence = Presence::updateOrCreate(
            ['planning_id' => $validated['planning_id'], 'user_id' => $validated['user_id']],
            ['status' => $validated['status']]
        );

        return response()->json($presence);
    }

    public function getPresencesByPlanning($planningId)
    {
        // Récupérer le planning avec les informations de la formation et des présences
        $planning = Planning::with(['presences.user'])->findOrFail($planningId);
    
        // Récupérer tous les étudiants inscrits dans la formation liée à ce planning
        $inscriptions = Inscription::with('users')
            ->where('formation_id', $planning->formation_id)
            ->where('payment_state', 'payée')
            ->get();
    
        // Préparer la réponse JSON avec les informations du planning et les étudiants inscrits avec leur statut de présence
        return response()->json([
            'planning' => [
                'id' => $planning->id,
                'formation_id' => $planning->formation_id,
                'cours_id' => $planning->course_id,
                'date' => $planning->date,
                'start_time' => $planning->start_time,
                'end_time' => $planning->end_time,
            ],
            'presences' => $inscriptions->map(function ($inscription) use ($planning) {
                // Rechercher la présence de l'étudiant pour ce planning
                $presence = $planning->presences->firstWhere('user_id', $inscription->user_id);
                
                return [
                    'user_id' => $inscription->users->id,
                    'name' => $inscription->users->name,
                    'email' => $inscription->users->email,
                    'status' => $presence ? $presence->status : 'absent', // Si la présence existe, afficher le statut, sinon 'absent'
                ];
            }),
        ]);
    }

    public function updatePresenceStatus($planningId, $userId)
    {
        // Vérifier si la présence existe pour cet utilisateur et ce planning
        $presence = Presence::where('planning_id', $planningId)
                            ->where('user_id', $userId)
                            ->first();
    
        if (!$presence) {
            return response()->json(['error' => 'Aucune présence trouvée pour cet utilisateur et ce planning.'], 404);
        }
    
        // Mettre à jour le statut de présence à "absent"
        $presence->status = 'absent';
        $presence->save();
    
        return response()->json(['message' => 'Le statut de présence a été mis à jour avec succès.']);
    }
    
    
    
    
    
    
}
