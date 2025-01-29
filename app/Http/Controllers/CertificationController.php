<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certification;
use App\Events\NewCertificationEvent;
use App\Models\Inscription;

class CertificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $certifications = Certification::with('users', 'formations.modules')->get();  // Ajout de 'modules' ici
    
        // Retourner la réponse JSON
        return response()->json([
            'results' => $certifications
        ], 200);
    }

    public function certificationByFormation(Request $request)
    {
        // Récupérer l'identifiant de la formation depuis la requête
        $formationId = $request->query('formation_id');
    
        if (!$formationId) {
            // Retourner une erreur si l'identifiant n'est pas fourni
            return response()->json([
                'error' => 'Le paramètre formation_id est requis.'
            ], 400);
        }
    
        // Charger les certifications avec les relations nécessaires
        $certifications = Certification::with(['users', 'formations.modules'])
            ->where('formation_id', $formationId)
            ->get();
    
        // Retourner les résultats dans une réponse JSON
        return response()->json([
            'results' => $certifications
        ], 200);
    }
    

    public function indexForUser()
{
    $userId = auth()->user()->id;

    // Récupérer uniquement les certifications liées à l'utilisateur avec 'user_id'
    $certifications = Certification::with('users', 'formations.modules')
        ->where('user_id', $userId) // Filtrage basé sur l'user_id
        ->get();
    
    // Retourner la réponse JSON
    return response()->json([
        'results' => $certifications
    ], 200);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Vérifier si l'utilisateur a déjà une certification pour cette formation
            $existingCertification = Certification::where('formation_id', $request->formation_id)
                ->where('user_id', $request->user_id)
                ->first();
    
            if ($existingCertification) {
                return response()->json([
                    'message' => "Cet utilisateur a déjà obtenu une certification pour cette formation."
                ], 400);
            }
    
            // Calculer la moyenne générale de l'apprenant pour la formation
            $moyenne = \App\Models\Evaluation::where('formation_id', $request->formation_id)
                ->where('user_id', $request->user_id)
                ->avg('note');
    
            if ($moyenne < 7) {
                return response()->json([
                    'message' => "Cet utilisateur ne peut pas être certifié car sa moyenne générale pour cette formation est inférieure à 7."
                ], 400);
            }
    
            // Créer la certification
            $certifications = Certification::create([
                'formation_id' => $request->formation_id,
                'user_id' => $request->user_id,
                'obtention_date' => $request->obtention_date
            ]);
    
            // Émettre l'événement de notification
            $apprenants = \App\Models\User::where('id', $certifications->user_id)->get();
            $formationTitle = \App\Models\Formation::where('id', $certifications->formation_id)->first()->name;
            event(new NewCertificationEvent($formationTitle, $apprenants, $certifications->obtention_date));
    
            return response()->json([
                'message' => "Certification créée avec succès."
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur est survenue : " . $e->getMessage()
            ], 500);
        }
    }
    
    


    public function autocertification(Request $request)
    {
        try {
            // Vérifier si l'utilisateur est inscrit à la formation et si le paiement est complet
            $inscription = Inscription::where('formation_id', $request->formation_id)
                ->where('user_id', $request->user_id)
                ->first();
    
            if (!$inscription) {
                return response()->json([
                    'message' => "L'utilisateur n'est pas inscrit à cette formation."
                ], 400); // Code 400 pour une requête invalide
            }
    
            if ($inscription->payment_state !== 'payée') {
                return response()->json([
                    'message' => "Le paiement pour cette formation n'est pas encore complet."
                ], 400); // Code 400 pour une requête invalide
            }
    
            // Vérifier si l'utilisateur a déjà une certification pour cette formation
            $existingCertification = Certification::where('formation_id', $request->formation_id)
                ->where('user_id', $request->user_id)
                ->first();
    
            if ($existingCertification) {
                return response()->json([
                    'message' => "Cet utilisateur a déjà obtenu une certification pour cette formation."
                ], 400);
            }
    
            // Créer la certification
            $certifications = Certification::create([
                'formation_id' => $request->formation_id,
                'user_id' => $request->user_id,
                'obtention_date' => $request->obtention_date
            ]);
    
            // Émettre l'événement de notification
            $apprenants = \App\Models\User::where('id', $certifications->user_id)->get();
            $formationTitle = \App\Models\Formation::where('id', $certifications->formation_id)->first()->name;
            event(new NewCertificationEvent($formationTitle, $apprenants, $certifications->obtention_date));
    
            return response()->json([
                'message' => "Certification créée avec succès."
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur est survenue : " . $e->getMessage()
            ], 500);
        }
    }



   
    public function show($id)
    {
       // Certification Detail 
       $certifications = Certification::find($id);
       if(!$certifications){
         return response()->json([
            'message'=>'Certification Not Found.'
         ],404);
       }
       
       // Return Json Response
       return response()->json([
          'Certifications' => $certifications
       ],200);
    }
   
    public function update(Request $request, $id)
    {
        try {
            // Récupérer la certification
            $certifications = Certification::find($id);
            if (!$certifications) {
                return response()->json([
                    'message' => 'Certification introuvable.'
                ], 404);
            }
    
            // Vérifier si l'utilisateur est inscrit à la formation et si le paiement est complet
            $inscription = Inscription::where('formation_id', $request->formation_id)
                ->where('user_id', $request->user_id)
                ->first();
    
            if (!$inscription) {
                return response()->json([
                    'message' => "L'utilisateur n'est pas inscrit à cette formation."
                ], 400);
            }
    
            if ($inscription->payment_state !== 'payée') {
                return response()->json([
                    'message' => "Le paiement pour cette formation n'est pas encore complet."
                ], 400);
            }
    
            // Mettre à jour la certification
            $certifications->formation_id = $request->formation_id;
            $certifications->user_id = $request->user_id;
            $certifications->obtention_date = $request->obtention_date;
            $certifications->save();
    
            return response()->json([
                'message' => "Certification mise à jour avec succès."
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur est survenue : " . $e->getMessage()
            ], 500);
        }
    }
   
    public function destroy($id)
    {
        // Detail 
        $certifications = Certification::find($id);
        if(!$certifications){
          return response()->json([
             'message'=>'Certification Not Found.'
          ],404);
        }
         
        // Delete Certification
        $certifications->delete();
       
        // Return Json Response
        return response()->json([
            'message' => "Certification successfully deleted."
        ],200);
    }
}
