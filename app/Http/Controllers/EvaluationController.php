<?php
namespace App\Http\Controllers;

use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    // Méthode pour ajouter une évaluation
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'formation_id' => 'required|exists:formations,id', // Validation pour formation_id
            'note' => 'required|numeric|min:0|max:20',
            'commentaire' => 'nullable|string',
        ]);

        $evaluation = new Evaluation();
        $evaluation->user_id = $request->user_id;
        $evaluation->course_id = $request->course_id;
        $evaluation->formation_id = $request->formation_id; // Assignation de formation_id
        $evaluation->note = $request->note;
        $evaluation->commentaire = $request->commentaire;
        $evaluation->save();

        return response()->json($evaluation, 201);
    }

    // Méthode pour afficher les évaluations d'un utilisateur
// Méthode pour afficher les évaluations d'un utilisateur pour une formation spécifique
public function show($formation_id, Request $request)
{
    $userId = Auth::guard('api')->user(); // Obtenir l'ID de l'utilisateur connecté

    // Calcul de la moyenne des notes
    $moyenne = \App\Models\Evaluation::where('formation_id', $formation_id)
        ->where('user_id', $userId)
        ->avg('note');
        
    // Récupérer les évaluations de l'utilisateur pour cette formation
    $evaluations = \App\Models\Evaluation::where('user_id', $userId)
        ->where('formation_id', $formation_id)
        ->with(['course', 'user', 'formation']) // Inclure les relations 'course', 'user', et 'formation'
        ->get();

    // Récupérer le nom de l'utilisateur et le nom de la formation
    $user = auth()->user(); // Utilisateur connecté
    $formation = \App\Models\Formation::find($formation_id); // Formation associée

    // Retourner les évaluations, la moyenne, et les informations supplémentaires
    return response()->json([
        'evaluations' => $evaluations,
        'moyenne' => $moyenne,
        'user' => $user->name, // Nom de l'utilisateur
        'formation' => $formation->name // Nom de la formation
    ]);
}



    // Méthode pour afficher les évaluations d'un apprenant dans une formation spécifique
   
   
   
   
    public function getEvaluationByFormationAndCourse($formation_id, $course_id)
    {
        // Récupérer toutes les évaluations pour la formation et le cours spécifiés
        $evaluations = Evaluation::where('formation_id', $formation_id)
            ->where('course_id', $course_id) // Filtrage par cours spécifique
            ->with(['user', 'course']) // Inclure les relations pour obtenir les infos des apprenants et du cours
            ->get();
    
        // Retourner les évaluations sous forme de réponse JSON
        return response()->json($evaluations);
    }
    
   
   
   
   
   
    public function apprenant_evaluation()
    {
        $userId = Auth::id();

        $evaluations = Evaluation::where('user_id', $userId)
           
            ->with('course','formation') // Charger les informations de cours associées
            ->get();

        return response()->json($evaluations);
    }

    // Méthode pour mettre à jour une évaluation
    public function update(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|numeric|min:0|max:20',
            'commentaire' => 'nullable|string',
        ]);

        $evaluation = Evaluation::findOrFail($id);
        $evaluation->note = $request->note;
        $evaluation->commentaire = $request->commentaire;
        $evaluation->save();

        return response()->json($evaluation);
    }
    public function destroy($id)
    {
        // Detail 
        $evaluations = Evaluation::find($id);
        if(!$evaluations){
          return response()->json([
             'message'=>'evaluation Not Found.'
          ],404);
        }
         
        // Delete evaluation
        $evaluations->delete();
       
        // Return Json Response
        return response()->json([
            'message' => "evaluation successfully deleted."
        ],200);
    }
}
