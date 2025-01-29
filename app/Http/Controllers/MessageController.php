<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Inscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class MessageController extends Controller
{
    /**
     * Envoyer un message avec option d'attachement.
     */
    public function sendMessage(Request $request)
    {
        // Valider les données
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,mp4,avi,mkv|max:20480', // Autorise les vidéos et fichiers jusqu'à 20MB
        ]);
        
    
        // Gestion de l'attachement (si présent)
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            
            if ($file) {
                // Générer un nom de fichier unique avec son extension
                $attachmentPath = Str::random(32) . "." . $file->getClientOriginalExtension();
                
                // Sauvegarder le fichier dans le répertoire 'message' sur le disque 'public'
                Storage::disk('public')->put('message/' . $attachmentPath, file_get_contents($file));
            }
        }
    
        // Créer un nouveau message
        $message = Message::create([
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'attachment' => $attachmentPath,
        ]);
    
        // Envoyer le message au serveur WebSocket (si nécessaire)
        Http::post('http://localhost:3000/broadcast', [
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'attachment' => $attachmentPath,
            'created_at' => $message->created_at,
        ]);
    
        return response()->json([
            'status' => 'Message sent',
            'message' => $message,
            'attachmentUrl' => $attachmentPath ? $attachmentPath : null,  // URL complète du fichier si présent
        ], 200);
    }
    

    /**
     * Marquer un message comme lu.
     */
// App\Http\Controllers\MessageController.php

public function markAsRead(Request $request, $id)
{
    $message = Message::findOrFail($id);

    // Vérifiez que seul le destinataire peut marquer un message comme lu
    if ($message->receiver_id != Auth::id()) {
        return response()->json(['status' => 'Unauthorized', 'message' => 'You cannot mark this message as read'], 403);
    }

    $message->markAsRead();

    return response()->json(['status' => 'Success', 'message' => 'Message marked as read'], 200);
}


    /**
     * Récupérer tous les messages entre deux utilisateurs.
     */
    public function getMessages(Request $request)
    {
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
        ]);

        $messages = Message::where(function ($query) use ($validated) {
            $query->where('sender_id', $validated['sender_id'])
                ->where('receiver_id', $validated['receiver_id']);
        })
            ->orWhere(function ($query) use ($validated) {
                $query->where('sender_id', $validated['receiver_id'])
                    ->where('receiver_id', $validated['sender_id']);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages, 200);
    }

    /**
     * Récupérer la liste des dernières conversations.
     */



    //  POur mysql
    // public function getConversations(Request $request)
    // {
    //     $validated = $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //     ]);

    //     $userId = $validated['user_id'];

    //     $subquery = DB::table('messages')
    //         ->selectRaw('MAX(id) as max_id')
    //         ->where('sender_id', $userId)
    //         ->orWhere('receiver_id', $userId)
    //         ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');

    //     $conversations = Message::select('messages.*')
    //         ->joinSub($subquery, 'subquery', 'messages.id', '=', 'subquery.max_id')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return response()->json($conversations, 200);
    // }

    public function getConversations(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $userId = $validated['user_id'];

    // Utilisation de CASE pour simuler LEAST et GREATEST
    $subquery = DB::table('messages')
        ->selectRaw('MAX(id) as max_id')
        ->where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
        })
        ->groupByRaw('
            CASE 
                WHEN sender_id < receiver_id THEN sender_id 
                ELSE receiver_id 
            END, 
            CASE 
                WHEN sender_id > receiver_id THEN sender_id 
                ELSE receiver_id 
            END
        ');

    $conversations = Message::select('messages.*')
        ->joinSub($subquery, 'subquery', 'messages.id', '=', 'subquery.max_id')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($conversations, 200);
}



    public function CountConversationsNotRead(Request $request){
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
    
        $userId = $validated['user_id'];
    
        // Sous-requête pour récupérer l'ID du dernier message de chaque conversation
        $subquery = DB::table('messages')
            ->selectRaw('MAX(id) as max_id')
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');
    
        // Récupération des derniers messages de chaque conversation
        $conversations = Message::select('messages.*')
            ->joinSub($subquery, 'subquery', 'messages.id', '=', 'subquery.max_id')
            ->orderBy('created_at', 'desc')
            ->get();
        $unreadCount = $conversations->where('receiver_id', $userId)
        ->where('is_read', false)
        ->count();

    return response()->json([
       
        'unreadCount' => $unreadCount,
    ], 200);

    }

    /**
     * Envoyer un message à tous les apprenants d'une formation.
     */
    public function sendMessageFormation(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id',
            // 'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,mp4,avi,mkv|max:20480', // Autorise les vidéos et fichiers jusqu'à 20MB
            'formation_id' => 'required|exists:formations,id', // Id de la formation
        ]);
        
        // Gestion de l'attachement (si présent)
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if ($file) {
                // Stocker le fichier et générer un chemin
                $attachmentPath = Str::random(32) . "." . $file->getClientOriginalExtension();
                Storage::disk('public')->put('message/' . $attachmentPath, file_get_contents($file));
            }
        }
    
        // Récupérer les utilisateurs inscrits à la formation
        $inscrits = Inscription::where('formation_id', $validated['formation_id'])->pluck('user_id');
    
        // Si aucun inscrit n'est trouvé, retourner une erreur
        if ($inscrits->isEmpty()) {
            return response()->json(['status' => 'Error', 'message' => 'No learners found for this formation'], 404);
        }
    
        // Créer un tableau de messages envoyés
        $messages = [];
        foreach ($inscrits as $userId) {
            // Créer un message pour chaque utilisateur inscrit
            $message = Message::create([
                'sender_id' => $validated['sender_id'],
                'receiver_id' => $userId,
                'message' => $validated['message'],
                'attachment' => $attachmentPath, // Stocke le chemin de l'attachement
            ]);
    
            // Ajouter l'URL de l'attachement si le fichier est présent
            $attachmentUrl = $attachmentPath ? asset('storage/message/' . $attachmentPath) : null;
    
            // Ajout du message avec l'URL de l'attachement dans la réponse
            $messages[] = [
                'sender_id' => $validated['sender_id'],
                'receiver_id' => $userId,
                'message' => $validated['message'],
                'attachment' => $attachmentUrl, // Inclure l'URL du fichier
            ];
        }
    
        // Retourner la réponse avec la liste des messages envoyés
        return response()->json([
            'status' => 'Messages sent',
            'message' => 'All messages sent successfully.',
            'messages' => $messages, // Renvoie les messages avec l'URL de l'attachement
        ], 200);
    }
    
    
}
