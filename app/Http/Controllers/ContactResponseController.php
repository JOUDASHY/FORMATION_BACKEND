<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Contact_response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactResponseController extends Controller
{
    /**
     * Ajouter une réponse à un message de contact existant.
     */
    public function store(Request $request, $contactId)
    {
        try {
            // Validation des données de la requête
            $validated = $request->validate([
                'response' => 'required|string',  // Le message de réponse est requis
                'email' => 'required|string|email', // L'email doit être valide
            ]);
    
            // Vérifier si le contact existe
            $contact = Contact::findOrFail($contactId);
    
            // Envoyer l'email avec la réponse
            Mail::raw($validated['response'], function ($message) use ($validated) {
                $message->to($validated['email'])
                        ->subject('La réponse à votre mail');
            });
    
            // Créer une nouvelle réponse dans la base de données
            $response = Contact_response::create([
                'contact_id' => $contact->id,
                'response' => $validated['response'],
            ]);
    
            return response()->json([
                'message' => 'Réponse ajoutée avec succès.',
                'response' => $response,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422); // Erreur de validation
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'ajout de la réponse : " . $e->getMessage());
            return response()->json([
                'message' => "Une erreur s'est produite lors de l'ajout de la réponse.",
                'error' => $e->getMessage(),
            ], 500); // Erreur serveur
        }
    }
    

    /**
     * Afficher toutes les réponses pour un contact spécifique.
     */
    public function index($contactId)
    {
        try {
            // Vérifier si le contact existe
            $contact = Contact::findOrFail($contactId);

            // Récupérer toutes les réponses associées au contact
            $responses = $contact->responses;

            return response()->json($responses, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur s'est produite lors de la récupération des réponses.",
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
