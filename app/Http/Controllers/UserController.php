<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationEmail;
use Illuminate\Support\Facades\Log;


use Illuminate\Support\Facades\URL;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::latest()->with('courses', 'inscriptions', 
    'certifications')->get();
        return response()->json($users, 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info($request->all());

        // Validation des champs
        $request->validate([
            'name' => 'required',
            'type' => 'required',
            'sex' => 'required',
            'contact' => 'nullable',
            'linkedin_link'=> 'nullable',
            'facebook_link'=> 'nullable',
            'email' => 'required|email|unique:users',
            'image' => 'nullable|mimes:png,jpeg,svg,jpg|max:2048',
        ]);
        // Création de l'utilisateur sans mot de passe
        $user = new User();
        $user->name = $request->name;
        $user->sex = $request->sex;
        $user->email = $request->email;
        $user->type = $request->type;
        $user->linkedin_link = $request->linkedin_link;
        $user->facebook_link = $request->facebook_link;
        $user->contact = $request->contact;
        $user->password = null;
    
        // Gestion de l'image si elle est présente
 // Gestion de l'image si elle est présente
if ($request->hasFile('image')) {
    $file = $request->file('image');
    $file_name = time() . '_' . $file->getClientOriginalName();
    $file->move(public_path('uploads'), $file_name);
    $user->image = $file_name;
} else {
    // Si aucune image n'est envoyée, utiliser la valeur par défaut 'user.jpg'
    $user->image = 'user.jpg';
}

    
        $user->save();
    
        // Création du token de réinitialisation de mot de passe
        $token = Password::createToken($user);
        $frontendUrl = config('app.frontend_url') ?? env('FRONTEND_URL');
        $url = "{$frontendUrl}/password-set/{$token}?email={$user->email}";
    
        // Envoi de l'email d'invitation
        Mail::to($user->email)->send(new InvitationEmail($user, $url));
    
        return response()->json([
            'message' => 'User created successfully and invitation email sent.',
            'user' => $user
        ], 201);
    }
    
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */

     public function updateuser(Request $request, $id)
     {
         // Trouver l'utilisateur par ID
         $user = User::find($id);
         if (!$user) {
             return response()->json([
                 'message' => 'User not found.'
             ], 404);
         }    
     
         // Vérifier si une nouvelle image a été téléchargée
         if ($request->hasFile('image')) {
             $file = $request->file('image');
             $file_name = time() . '_' . $file->getClientOriginalName();
     
             // Supprimer l'ancienne image si elle existe
             if ($user->image && File::exists(public_path('uploads/' . $user->image))) {
                
            if ($user->image != "user.jpg") {
                File::delete(public_path('uploads/' . $user->image));}
             }
     
             // Déplacer la nouvelle image vers le répertoire public/uploads
             $file->move(public_path('uploads'), $file_name);
     
             // Mettre à jour le champ image de l'utilisateur
             $user->image = $file_name;
         }
     
         // Mise à jour des autres champs
         $user->name = $request->name ?? $user->name;
         $user->email = $request->email ?? $user->email;
         $user->sex = $request->sex ?? $user->sex;
         $user->linkedin_link = $request->linkedin_link ?? $user->linkedin_link;
         $user->facebook_link = $request->facebook_link ?? $user->facebook_link;
         $user->type = $request->type ?? $user->type;
         $user->contact = $request->contact ?? $user->contact;
         
         // Mettre à jour le mot de passe seulement si il est fourni
         if ($request->password) {
             $user->password = Hash::make($request->password);
         }
     
         $user->save(); // Sauvegarder les modifications
     
         return response()->json([
             'message' => 'User successfully updated.',
             'user' => $user
         ], 200);
     }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->image && File::exists(public_path('uploads/' . $user->image))) {

 
            if ($user->image != "user.jpg") {
            File::delete(public_path('uploads/' . $user->image));}
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }



    public function sendSetLink($email)
    {
        $user = User::where('email', $email)->firstOrFail();
        $token = Password::createToken($user);
        $url = URL::to("/password-set/{$token}?email={$user->email}");
    
        Mail::to($email)->send(new InvitationEmail($user, $url));
    
        return response()->json(['message' => 'Invitation email sent successfully.']);
    }
    




    

}
