<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Events\NewFormationEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FormationController extends Controller
{
    public function index()
    {
        // Charger toutes les formations avec leurs relations inscriptions et modules
        $formations = Formation::with('inscriptions', 'modules')->get();
    
        return response()->json([
            'results' => $formations
        ], 200);
    }

    public function PresentationFormation()
    {
        // Charger toutes les formations avec leurs relations inscriptions et modules
        // et filtrer celles dont la date de début est dans le futur
        $currentDate = Carbon::now();
    
        $formations = Formation::with('inscriptions', 'modules')
            ->where('start_date', '>=', $currentDate) // Assurez-vous que la colonne 'start_date' existe dans la table 'formations'
            ->get();
    
        return response()->json([
            'results' => $formations
        ], 200);
    }
    

    public function Formation_apprenant()
    {
        $userId = Auth::guard('api')->id(); // Obtenez l'ID de l'utilisateur connecté
    
        // Récupérer les formations dont l'utilisateur est inscrit et où l'inscription est payée
        $formations = Formation::whereHas('inscriptions', function ($query) use ($userId) {
            $query->where('user_id', $userId) // Filtrer par l'utilisateur connecté
                  ->where('payment_state', 'payée'); // Filtrer par le statut de paiement
        })
        // Charger les relations associées
        ->get();
    
        return response()->json([
            'results' => $formations
        ], 200);
    }
    
    public function Formation_formateur()
    {
        // Récupérer l'ID de l'utilisateur connecté
        $userId = Auth::guard('api')->id();
    
        // Récupérer les cours associés à l'utilisateur
        $courses = \App\Models\Course::where('user_id', $userId)->get();
    
        // Vérifiez s'il y a des cours disponibles
        if ($courses->isEmpty()) {
            return response()->json(['error' => 'Aucun cours trouvé pour cet utilisateur.'], 404);
        }
    
        // Récupérer les IDs des modules associés à ces cours
        $moduleIds = $courses->pluck('module_id');
    
        // Récupérer les formations associées à ces modules
        $formationIds = \App\Models\Module::whereIn('id', $moduleIds)->pluck('formation_id');
    
        // Récupérer les formations associées à ces IDs
        $formations = \App\Models\Formation::whereIn('id', $formationIds)->get();
    
        return response()->json([
            'results' => $formations
        ], 200);
    }
    
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable',
            'start_date' => 'nullable|date',
            'tariff' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg,gif|max:2048',
        ]);
    
        try {
            $imageName = null;
    
            // Vérifier si une image a été téléchargée
            if ($request->hasFile('image')) {
                // Générer un nom unique pour l'image
                $imageName = Str::random(32) . '.' . $request->image->getClientOriginalExtension();
    
                // Sauvegarder l'image
                Storage::disk('public')->put($imageName, file_get_contents($request->image));
            }
    
            // Créer la formation
            $formation = Formation::create([
                'name' => $request->name,
                'description' => $request->description,
                'duration' => $request->duration,
                'image' => $imageName, // Cela peut être null si aucune image n'a été téléchargée
                'start_date' => $request->start_date,
                'tariff' => $request->tariff
            ]);
    
            // Récupérer les apprenants pour notifier
            $apprenants = \App\Models\User::where('type', 'apprenant')->get(); // Récupérer tous les apprenants
    
            // Émettre l'événement
            event(new NewFormationEvent($formation->name, $apprenants));
    
            return response()->json([
                'message' => 'Formation successfully created.',
                'formation' => $formation
            ], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $formation = Formation::find($id);
        if (!$formation) {
            return response()->json([
                'message' => 'Formation not found.'
            ], 404);
        }

        return response()->json([
            'formation' => $formation
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */



public function updateformation(Request $request, $id)
{
    $formation = Formation::find($id);
    if (!$formation) {
        return response()->json([
            'message' => 'Formation not found.'
        ], 404);
    }    

    $storage = Storage::disk('public');

    // Vérifier si une nouvelle image a été téléchargée
    if ($request->hasFile('image')) {

        // Supprimer l'ancienne image si elle existe
        if ($storage->exists($formation->image)) {
            $storage->delete($formation->image);
        }

        // Enregistrer la nouvelle image
        $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
        $storage->put($imageName, file_get_contents($request->image));

        // Mettre à jour le chemin de l'image dans la base de données
        $formation->image = $imageName;
    }

    // Mise à jour des autres champs
    $formation->update([
        'name' => $request->name,
        'description' => $request->description,
        'duration' => $request->duration,

        'start_date' => $request->start_date,
        'tariff' => $request->tariff,
    ]);

    return response()->json([
        'message' => 'Formation successfully updated.',
        'formation' => $formation
    ], 200);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $formation = Formation::find($id);
        if (!$formation) {
            return response()->json([
                'message' => 'Formation not found.'
            ], 404);
        }

        // Public storage
        $storage = Storage::disk('public');

        // Delete image
        if ($storage->exists($formation->image)) {
            $storage->delete($formation->image);
        }

        // Delete Formation
        $formation->delete();

        return response()->json([
            'message' => 'Formation successfully deleted.'
        ], 200);
    }
}
