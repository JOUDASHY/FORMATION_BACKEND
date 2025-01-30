<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
// use Validator;

use Illuminate\Support\Facades\File;
use Google_Client;
use Illuminate\Support\Str;  // Ajoutez cette ligne en haut du fichier


use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Http; // Ajouter cette ligne pour effectuer des requêtes HTTP
use Illuminate\Support\Facades\Storage; // Si vous préférez stocker l'image dans le système de fichiers Laravel
// use Google\Client as Google_Client;
use Google\Service\PeopleService;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register','sendResetLink','resetPassword','handleGoogleToken']]);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        if (!$token = Auth::guard('api')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Identifiants incorrects',
            ], 401);
        }
    
        return $this->createNewToken($token);
    }
    public function register(Request $request)
    {
        // Validation des champs
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6', // Validation avec confirmation du mot de passe
            'sex' => 'required|string',
        ]);
    
        // Création de l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'sex' => $request->sex,
            'email' => $request->email,
            'type' => 'apprenant',
            'password' => Hash::make($request->password),
        ]);
    
        // Générer un token JWT pour l'utilisateur
        $token = Auth::guard('api')->login($user);
    
        // Appeler la méthode createNewToken pour générer la réponse avec le token
        return $this->createNewToken($token);
    }
    
    
    public function logout()
    {
        try {
            Auth::guard('api')->logout(); // Invalider le token JWT
            return response()->json(['message' => 'Déconnexion réussie']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Impossible de se déconnecter'], 500);
        }
    }
    
   
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
'expires_in' => auth('api')->factory()->setTTL(7 * 24 * 60)->getTTL() * 60, // 7 jours en secondes

            'user' => auth('api')->user(),
        ]);
    }
    public function me()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
        ]);
    }
    public function userProfile(){
        return response()->json([
            Auth::user()
        ]);
    }
    public function refresh()
    {

        return $this->createNewToken(Auth::refresh());
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    
        $response = Password::sendResetLink($request->only('email'));
    
        return $response === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Un e-mail de réinitialisation a été envoyé à votre adresse.'])
            : response()->json(['message' => 'Erreur lors de l\'envoi de l\'e-mail.'], 400);
    }
    

    public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $response = Password::reset($request->only('email', 'password', 'token'), function ($user, $password) {
        $user->password = Hash::make($password);
        $user->save();
    });

    return $response === Password::PASSWORD_RESET
        ? response()->json(['message' => 'Votre mot de passe a été réinitialisé avec succès.'])
        : response()->json(['message' => 'Erreur lors de la réinitialisation du mot de passe.'], 400);
}

public function setPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    // Vérifiez si le jeton est valide
    if (!Password::tokenExists($user, $request->token)) {
        return response()->json(['message' => 'Invalid token.'], 400);
    }

    // Hachage du nouveau mot de passe
    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json(['message' => 'Password has been set successfully.']);
}





public function redirectToGoogle()
{
    // Redirige l'utilisateur vers Google pour l'authentification
    return Socialite::driver('google')->stateless()->redirect();
}

public function handleGoogleToken(Request $request)
{
    $token = $request->input('token');
    if (!$token) {
        return response()->json(['message' => 'Token manquant.'], 400);
    }

    // Log de vérification
    \Log::info('Google Token:', ['token' => $token]);

    // Créer une instance du client Google
    $client = new Google_Client(['client_id' => '318214150812-75s4p9k7a45ehhesga8t90mdi0hj97jp.apps.googleusercontent.com']);
    
    // Vérifier le jeton
    $payload = $client->verifyIdToken($token);

    if ($payload) {
        \Log::info('Payload valid:', $payload);  // Affichez le payload pour debug

        // Si le jeton est valide, obtenir les données de l'utilisateur
        $email = $payload['email'];
        $name = $payload['name'];
        $avatar = $payload['picture'];  // L'URL de l'avatar
        $googleId = $payload['sub'];  // Google ID
        $gender = isset($payload['gender']) ? $payload['gender'] : null;  // Récupérer le sexe (s'il est présent)

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Si l'utilisateur n'existe pas, créez un nouvel utilisateur
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(16)),  // Mot de passe temporaire
                'type' => 'apprenant',
                'google_id' => $googleId,  // Enregistrer l'ID Google pour lier l'utilisateur
                'sex' => $this->getGenderValue($gender),  // Stockage du sexe (si disponible)
            ]);
        } else {
            // Si l'utilisateur existe déjà, mettre à jour le sexe (si disponible)
            if ($gender && !$user->sex) {
                $user->sex = $this->getGenderValue($gender);
                $user->save();
            }
        }

        // Gestion de l'image si elle est présente (télécharger et enregistrer)
        if ($avatar) {
            // Télécharger l'image depuis l'URL de l'avatar
            $imageContent = file_get_contents($avatar);
            $imageName = time() . '.jpg';  // Vous pouvez aussi utiliser l'extension en fonction de l'image

            // Sauvegarder l'image dans le répertoire public/uploads
            file_put_contents(public_path('uploads/' . $imageName), $imageContent);

            // Mettre à jour l'image de l'utilisateur dans la base de données
            $user->image = $imageName;
            $user->save();
        }

        // Authentifier l'utilisateur et générer un token pour votre API
        $token = Auth::login($user);

        return response()->json([
            'access_token' => $token,
            'expires_in' => 3600,
            'user' => $user,
        ]);
    } else {
        return response()->json(['message' => 'Jeton invalide.'], 400);
    }
}

// Fonction pour convertir la valeur du genre
private function getGenderValue($gender)
{
    if ($gender) {
        // Google peut renvoyer "male", "female" ou parfois rien
        return strtolower($gender) === 'male' ? 'masculin' : (strtolower($gender) === 'female' ? 'féminin' : null);
    }
    return null;
}







}         