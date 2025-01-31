<?php
namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\Course;
use App\Models\Room;
use App\Models\Inscription;
use App\Models\User;
use App\Models\Formation;
use Illuminate\Http\Request;
use App\Events\NewPlanningEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $formationId = $request->query('formation_id');
        
        // Charger les plannings avec les informations des cours, des formateurs et des formations
        $plannings = Planning::with(['courses.user', 'formations','rooms']) 
            ->when($formationId, function ($query, $formationId) {
                return $query->where('formation_id', $formationId);
            })
            ->get();

        return response()->json($plannings);
    }

    public function planning(Request $request)
    {
        $formationId = $request->query('formation_id');
        
        // Charger les plannings avec les informations des cours, des formateurs et des formations
        $plannings = Planning::with(['courses.user', 'formations'])
            ->when($formationId, function ($query, $formationId) {
                return $query->where('formation_id', $formationId);
            })
            ->get();

        return response()->json($plannings);
    }

    public function Planning_apprenant()
    {
        $userId = Auth::guard('api')->id();
        $plannings = Planning::whereHas('courses.module.formation.inscriptions', function ($query) use ($userId) {
            $query->where('user_id', $userId); 
        })
        ->with('courses.module.formation')
        ->get();
        return response()->json($plannings);
    }

    public function Planning_formateur()
    {
        $userId = Auth::guard('api')->id();
    
        // Récupérer la date d'aujourd'hui
        $today = now()->toDateString();  // Format 'YYYY-MM-DD'
    
        $plannings = Planning::whereHas('courses', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('courses', 'formations', 'rooms')
            // Vérifiez si la colonne 'date' est de type 'DATE', sinon ajustez ici
            ->whereDate('date', '<=', $today)  // Filtrer les plannings à partir d'aujourd'hui
            ->orderBy('date', 'desc')  // Trier par date, du plus ancien au plus récent
            ->get();
    
        // Si rien n'est retourné, vous pouvez loguer pour comprendre où ça bloque
        if ($plannings->isEmpty()) {
            \Log::info('Aucun planning trouvé pour aujourd\'hui et après.');
        }
    
        return response()->json($plannings);
    }
    
    

    public function show($id)
    {
        $planning = Planning::findOrFail($id);
        return response()->json($planning);
    }

    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'course_id' => 'required|integer',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'room_id' => 'required|exists:rooms,id', // Validation pour l'ID de la salle
            'formation_id' => 'required',
        ]);
    
        // Récupérer le formateur du cours pour vérifier sa disponibilité
        $formateurId = Course::where('id', $validated['course_id'])->first()->user_id;
    
        // Vérifier s'il existe un conflit pour le formateur
        $conflitFormateur = Planning::whereHas('courses', function ($query) use ($formateurId) {
                $query->where('user_id', $formateurId);
            })
            ->where('date', $validated['date'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($subQuery) use ($validated) {
                    $subQuery->where('start_time', '<', $validated['end_time'])
                             ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->exists();
    
        if ($conflitFormateur) {
            return response()->json(['message' => 'Le formateur est déjà occupé pour cette plage horaire dans une autre salle.'], 400);
        }
    
        // Vérifier s'il existe un conflit pour la salle
        $conflitSalle = Planning::where('room_id', $validated['room_id'])  // Utilisation de room_id
            ->where('date', $validated['date'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($subQuery) use ($validated) {
                    $subQuery->where('start_time', '<', $validated['end_time'])
                             ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->exists();
    
        if ($conflitSalle) {
            return response()->json(['message' => 'La salle est déjà occupée pour cette plage horaire.'], 400);
        }
    
        // Création du planning avec room_id au lieu de room
        $planning = Planning::create([
            'course_id' => $validated['course_id'],
            'formation_id' => $validated['formation_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room_id' => $validated['room_id'],  // Utilisation de room_id
        ]);
    
        // Récupérer le nom du cours et de la formation
        $CourseName = Course::where('id', $planning->course_id)->first()->name;
        $formationTitle = Formation::where('id', $planning->formation_id)->first()->name;
    
        // Récupérer les apprenants pour notifier
        $apprenantIds = Inscription::where('formation_id', $planning->formation_id)->pluck('user_id');
        $apprenants = User::whereIn('id', $apprenantIds)->get();
        $room = Room::find($planning->room_id); // Trouve la salle par son ID
        $roomNumber = $room ? $room->room_number : null; // Récupère room_number si la salle existe

    
        // Log de la création du planning
        Log::info("Création du planning - Formateur: {$formateurId}, Apprenants: {$apprenants}, Cours: {$CourseName}, Formation: {$formationTitle}, Heure de début: {$planning->start_time}, Heure de fin: {$planning->end_time}, Salle: {$planning->room_id}");
    
        // Émettre l'événement de création
        event(new NewPlanningEvent($formationTitle, $formateurId, $apprenants, $planning->start_time, $planning->end_time, $CourseName, $planning->date, $roomNumber, false));
    
        return response()->json(['message' => 'Planning créé avec succès!', 'schedule' => $planning], 201);
    }
    

    public function update(Request $request, $id)
    {
        // Validation des données
        $validated = $request->validate([
            'course_id' => 'integer',
            'date' => 'date',
            'start_time' => 'string',
            'end_time' => 'string',
            'room_id' => 'exists:rooms,id', // Validation pour l'ID de la salle
            'formation_id' => 'string',
        ]);
    
        // Trouver le planning à mettre à jour
        $planning = Planning::findOrFail($id);
    
        // Mise à jour des données du planning
        $planning->update($validated);
    
        return response()->json(['message' => 'Planning mis à jour avec succès!', 'schedule' => $planning]);
    }
    
    public function destroy($id)
    {
        // Trouver le planning à annuler
        $planning = Planning::findOrFail($id);

        // Récupérer les informations nécessaires pour l'événement
        $formateurId = Course::where('id', $planning->course_id)->first()->user_id;
        $CourseName = Course::where('id', $planning->course_id)->first()->name;
        $formationTitle = Formation::where('id', $planning->formation_id)->first()->name;

        $apprenantIds = Inscription::where('formation_id', $planning->formation_id)->pluck('user_id');
        $apprenants = User::whereIn('id', $apprenantIds)->get();

        // Annuler le planning
        $planning->delete();

        // Émettre l'événement d'annulation
        event(new NewPlanningEvent($formationTitle, $formateurId, $apprenants, $planning->start_time, $planning->end_time, $CourseName, $planning->date, $planning->room, true));

        return response()->json(['message' => 'Planning annulé avec succès!']);
    }
}
