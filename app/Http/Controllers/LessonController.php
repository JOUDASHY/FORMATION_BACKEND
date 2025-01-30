<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Events\NewLessonEvent;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lessons = Lesson::with('users', 'courses')->get();
        return response()->json([
            'results' => $lessons
        ], 200);
    }

    public function Lesson_apprenant()
    {
        $userId = Auth::guard('api')->user(); // Obtenez l'ID de l'utilisateur connecté
    
        // Récupérer les leçons associées aux formations dont l'inscription est payée
        $lessons = Lesson::whereHas('courses.module.formation.inscriptions', function ($query) use ($userId) {
            $query->where('user_id', $userId) // Filtrer par l'utilisateur connecté
                  ->where('payment_state', 'payée'); // Filtrer par le statut de paiement
        })
        ->with('courses.module.formation') // Charger les relations associées
        ->orderBy('created_at', 'desc')
        ->get();
    
        return response()->json([
            'results' => $lessons
        ], 200);
    }
    
    
    
    public function Lesson_formateur()
 
    {
        $userId = Auth::guard('api')->user(); // Obtenez l'ID de l'utilisateur connecté
    
        // Récupérer les leçons associées aux formations auxquelles l'utilisateur est inscrit
        $lessons = Lesson::whereHas('courses', function ($query) use ($userId) {
            $query->where('user_id', $userId); // Filtrer par l'utilisateur connecté
        })
        ->with('courses') // Charger les relations associées
        ->orderBy('created_at', 'desc')
        ->get();
    
        return response()->json([
            'results' => $lessons
        ], 200);
    }







    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
         'file' => 'nullable|file|max:504800', // taille maximale de 200 Mo (204800 Ko)
// taille maximale de 200 Mo (204800 Ko)

            'course_id' => 'required'
        ]);
        $userId = Auth::guard('api')->user();

        try {
            // Générer un nom de fichier unique
            $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();

            // Vérifier si le fichier est bien accessible
            $fileContent = file_get_contents($request->file);
            if ($fileContent === false) {
                throw new \Exception('Le fichier ne peut pas être lu.');
            }

            // Créer la leçon
            $lesson = Lesson::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $fileName,
                'course_id' => $request->course_id,
                'user_id' => $userId,
            ]);

            // Sauvegarder le fichier dans le dossier public
            Storage::disk('public')->put($fileName, $fileContent);



            $formateur = \App\Models\User::where('id',$lesson->user_id )->first()->name; 
            $course = \App\Models\Course::where('id',$lesson->course_id )->first()->name; 

        $apprenant_inscrits = \App\Models\User::whereHas('inscriptions.formations.modules.courses', function ($query) use ($lesson) {
            $query->where('courses.id', $lesson->course_id);
        })->get();
            
        
            event(new NewLessonEvent( $apprenant_inscrits,$lesson->title,$formateur,$course));
 


            return response()->json([
                'message' => 'Lesson successfully created.',
                'lesson' => $lesson
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
     * Display the specified resource.
     */
    public function show($id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found.'
            ], 404);
        }

        return response()->json([
            'lesson' => $lesson
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updatelesson(Request $request, $id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found.'
            ], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
          
            'course_id' => 'required|exists:courses,id',
           
        ]);
        $userId = Auth::guard('api')->user();


        $storage = Storage::disk('public');

        // Vérifier si un nouveau fichier est uploadé
        if ($request->hasFile('file')) {
            // Supprimer l'ancien fichier s'il existe
            if ($storage->exists($lesson->file_path)) {
                $storage->delete($lesson->file_path);
            }

            // Lire le contenu du nouveau fichier
            $fileContent = file_get_contents($request->file);
            if ($fileContent === false) {
                return response()->json([
                    'message' => 'Le fichier ne peut pas être lu.',
                ], 500);
            }

            // Sauvegarder le nouveau fichier
            $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
            $storage->put($fileName, $fileContent);

            // Mettre à jour le chemin du fichier dans la base de données
            $lesson->file_path = $fileName;
        }

        // Mettre à jour les autres champs
        $lesson->update([
            'title' => $request->title,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'user_id' => $userId,
        ]);

        return response()->json([
            'message' => 'Lesson successfully updated.',
            'lesson' => $lesson
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found.'
            ], 404);
        }

        // Accéder au disque public
        $storage = Storage::disk('public');

        // Supprimer le fichier si existant
        if ($storage->exists($lesson->file_path)) {
            $storage->delete($lesson->file_path);
        }

        // Supprimer la leçon de la base de données
        $lesson->delete();

        return response()->json([
            'message' => 'Lesson successfully deleted.'
        ], 200);
    }
}
