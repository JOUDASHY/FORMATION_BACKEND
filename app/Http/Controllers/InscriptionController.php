<?php

namespace App\Http\Controllers;
use App\Events\NewInscriptionEvent;
use Illuminate\Http\Request;
use App\Models\Inscription;
use App\Models\User;
use App\Models\Paiement; // Assurez-vous d'importer le modèle Paiement si vous en avez un
use App\Models\Formation; // Pour récupérer le coût de la formation dans la méthode addPaiement
// use App\Models\Paiement;
// use Illuminate\Http\Request;
// ...



class InscriptionController extends Controller
{
     /**
     * Display a listing of the resource.
     */

     public function getPaiements()
     {
         $paiements = Paiement::with('inscriptions.users', 'inscriptions.formations')
                              ->orderBy('created_at', 'desc')
                              ->get();
                              
         return response()->json(['results' => $paiements], 200);
     }
     



    


     public function apprenant_inscription()
     {
         $userId = auth()->user()->id;
 
         $inscriptions = Inscription::where("user_id",$userId)->with( 'formations')->get(); 
           
         // Return Json Response
         return response()->json([
              'results' => $inscriptions
         ],200);
     }
 
 
     public function addPaiement(Request $request, $inscription_id)
     {
         try {
             // Trouver l'inscription concernée
             $inscription = Inscription::findOrFail($inscription_id);
             
             // Créer un nouveau paiement associé à cette inscription
             $paiement = Paiement::create([
                 'inscription_id' => $inscription->id,
                 'montant' => $request->montant, // Montant payé lors de cette transaction
                 'date_paiement' => now(), // Date du paiement
                 'type_paiement' => $request->type_paiement, // Type de paiement (carte bancaire, espèce, etc.)
             ]);
     
             // Calculer le montant total payé après ce paiement
             $inscription->payed += $paiement->montant;
     
             // Récupérer le coût total de la formation
             $formation = Formation::findOrFail($inscription->formation_id);
             $coutFormation = $formation->tariff;
     
             // Mettre à jour l'état de paiement en fonction du montant payé
             $inscription->payment_state = ($inscription->payed >= $coutFormation) ? 'payée' : 'en cours';
             
             // Sauvegarder les modifications
             $inscription->save();
     
             return response()->json([
                 'message' => "Paiement ajouté avec succès.",
                 'inscription' => $inscription,
                 'paiement' => $paiement
             ], 200);
     
         } catch (\Exception $e) {
             \Log::error('Erreur lors de l\'ajout du paiement : ' . $e->getMessage());
             return response()->json([
                 'message' => "Erreur lors de l'ajout du paiement !"
             ], 500);
         }
     }
     

    public function index()
    {
        $inscriptions = Inscription::with('users', 'formations', 'paiements')->get();
        return response()->json(['results' => $inscriptions], 200);
    }

    public function InscriptionbyFomation(Request $request)
{
    // Récupérer l'ID de la formation depuis les paramètres de la requête
    $formationId = $request->query('formation_id');

    // Filtrer les inscriptions par formation si un ID est fourni
    $query = Inscription::with('users', 'formations', 'paiements');
    if ($formationId) {
        $query->where('formation_id', $formationId);
    }

    $inscriptions = $query->get();

    return response()->json(['results' => $inscriptions], 200);
}


    public function show($id)
    {
        $inscription = Inscription::with('paiements')->find($id);
        if (!$inscription) {
            return response()->json(['message' => 'Inscription Not Found.'], 404);
        }
        return response()->json(['Inscription' => $inscription], 200);
    }
    public function store(Request $request)
    {
        try {
            $existingInscription = Inscription::where('user_id', $request->user_id)
                ->where('formation_id', $request->formation_id)
                ->first();
    
            if ($existingInscription) {
                return response()->json([
                    'message' => "Il est déjà inscrit à cette formation."
                ], 400);
            }
    
            $inscription = Inscription::create([
                'user_id' => $request->user_id,
                'formation_id' => $request->formation_id,
                'inscription_date' => now(),
                'payment_state' => 'en cours', 
                'payed' => $request->payed,
            ]);
    
            $paiement = Paiement::create([
                'inscription_id' => $inscription->id,
                'montant' => $request->payed,
                'date_paiement' => now(),
                'type_paiement' => $request->type_paiement, // Type de paiement
            ]);
    
            $formation = Formation::findOrFail($inscription->formation_id);
            $coutFormation = $formation->tariff;
    
            if ($inscription->payed >= $coutFormation) {
                $inscription->payment_state = 'payée';
                $inscription->save();
            }
    
            $admins = User::where('type', "admin")->get();
            $formationTitle = $formation->name;
            $apprenant = User::where('id', $inscription->user_id)->first()->name;
    
            event(new NewInscriptionEvent($formationTitle, $admins, $apprenant, $inscription->payed, 'created'));
    
            return response()->json([
                'message' => "Inscription et paiement initial ajoutés avec succès.",
                'inscription' => $inscription,
                'paiement' => $paiement
            ], 200);
    
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de l\'inscription : ' . $e->getMessage());
            return response()->json([
                'message' => "Une erreur est survenue lors de la création de l'inscription."
            ], 500);
        }
    }
    
    public function update(Request $request, $id)
    {
        try {
            $inscriptions = Inscription::find($id);
            if(!$inscriptions){
              return Inscriptions()->json([
                'message'=>'Inscription Not Found.'
              ],404);}
            $inscriptions->user_id = $request->user_id;
            $inscriptions->formation_id = $request->formation_id;
            $inscriptions->inscription_date = $request->inscription_date;
            $inscriptions->payment_state = $request->payment_state;
            $inscriptions->payed = $request->payed;
            $inscriptions->save();
            return response()->json([
                'message' => "Inscription successfully updated."
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Something went really wrong!"
            ],500);
        }
    }
    public function destroy($id)
    {
        try {
            $inscription = Inscription::with(['paiements'])->findOrFail($id);
    
            // Mettre à NULL l'inscription_id dans les paiements liés
            foreach ($inscription->paiements as $paiement) {
                $paiement->inscription_id = null;
                $paiement->save();
            }
    
            $admins = User::where('type', "admin")->get(); 
            $formationTitle = $inscription->formations->name; 
            $apprenant = $inscription->users->name;
    
            \Log::info('Émission de l\'événement de suppression pour l\'inscription ID: ' . $id);
            event(new NewInscriptionEvent($formationTitle, $admins, $apprenant, $inscription->payed, 'deleted'));
    
            // Suppression de l'inscription
            $inscription->delete();
    
            return response()->json([
                'message' => 'Inscription successfully deleted.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Inscription not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'inscription : ' . $e->getMessage());
            return response()->json([
                'message' => 'Something went really wrong!'
            ], 500);
        }
    }
    
    
    
    public function getUsersByFormation($formation_id)
    {
        // Récupérer les IDs des utilisateurs inscrits à la formation et ayant payé
        $apprenantIds = Inscription::where('formation_id', $formation_id)
            ->where('payment_state', 'payée') // Ajouter la condition sur payment_state
            ->pluck('user_id');
        
        // Récupérer les utilisateurs correspondants
        $apprenants = User::whereIn('id', $apprenantIds)->get();
        
        // Retourner la réponse JSON avec les utilisateurs
        return response()->json([
            'users' => $apprenants
        ], 200);
    }
    
    
}
