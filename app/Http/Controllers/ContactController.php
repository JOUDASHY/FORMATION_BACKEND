<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Mail\ReplyEmail; // Import du Mailable
use Illuminate\Support\Facades\Mail; // Import de la façade Mail


class ContactController extends Controller
{
    public function index()
    {
        $contacts = Contact::all();          
        // Return Json Response
        return response()->json([
             'results' => $contacts
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Créer le contact
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
            ]);
 
     




            Mail::raw('Merci d\'avoir nous contacter. Nous avons reçu votre message: ' . $contact->message, function ($message) use ($contact) {
                $message->to($contact->email)
                        ->subject('Merci pour votre message!');
            });
            // Si le nom n'est pas 'unit-fianar', retour normal
            return response()->json([
                'message' => "Contact créé avec succès."
            ], 200);

        } catch (\Exception $e) {
            // Gérer toute erreur générale pendant la création du contact
            return response()->json([
                'message' => "Une erreur s'est produite."
            ], 500);
        }
    }

    public function show($id)
    {
       // Détail du contact
       $contact = Contact::find($id);
       if (!$contact) {
           return response()->json([
               'message' => 'Contact non trouvé.'
           ], 404);
       }
       
       // Retourne une réponse JSON
       return response()->json([
           'contact' => $contact
       ], 200);
    }
   
    public function destroy($id)
    {
        // Détail du contact
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'message' => 'Contact non trouvé.'
            ], 404);
        }   
        // Supprimer le contact
        $contact->delete();
        // Retourne une réponse JSON
        return response()->json([
            'message' => "Contact supprimé avec succès."
        ], 200);
    }
}
