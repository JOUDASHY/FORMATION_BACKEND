<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Import Log facade for logging
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{




    public function coursesByFormation_formateur($formationId)
    {
        // Récupérer l'ID de l'utilisateur connecté
        $userId = Auth::guard('api')->id();
    
        // Récupérer les modules associés à la formation donnée
        $moduleIds = \App\Models\Module::where('formation_id', $formationId)->pluck('id');
    
        // Récupérer les cours dans ces modules enseignés par l'utilisateur connecté
        $courses = \App\Models\Course::whereIn('module_id', $moduleIds)
            ->where('user_id', $userId)
            ->get();
    
        // Vérifiez s'il y a des cours disponibles
        if ($courses->isEmpty()) {
            return response()->json(['error' => 'Aucun cours trouvé pour cet utilisateur dans cette formation.'], 404);
        }
    
        return response()->json([
            'courses' => $courses
        ], 200);
    }
    
    public function coursesByFormation($formationId)
    {
        // Récupérer tous les modules associés à la formation
        $modules = \App\Models\Module::where('formation_id', $formationId)
                    ->with('courses') // Charger les cours associés à chaque module
                    ->get();
    
        // Vérifiez s'il y a des modules avec des cours
        if ($modules->isEmpty()) {
            return response()->json(['error' => 'Aucun module trouvé pour cette formation.'], 404);
        }
    
        // Extraire tous les cours des modules et les afficher
        $courses = $modules->flatMap(function ($module) {
            return $module->courses; // Récupère les cours pour chaque module
        });
    
        // Vérifiez s'il y a des cours disponibles
        if ($courses->isEmpty()) {
            return response()->json(['error' => 'Aucun cours trouvé pour cette formation.'], 404);
        }
    
        return response()->json([
            'courses' => $courses
        ], 200);
    }
    


    public function getCoursesByTeacher()
{
    $userId = Auth::guard('api')->id();

    // Recherche des cours où le formateur est assigné
    $courses = Course::where('user_id', $userId)
                     ->with('module', 'planning')  // Vous pouvez ajouter d'autres relations si nécessaire
                     ->get();

    // Vérifier si des cours ont été trouvés pour le formateur
    if ($courses->isEmpty()) {
        return response()->json(['message' => 'Aucun cours trouvé pour ce formateur.'], 404);
    }

    return response()->json(['courses' => $courses], 200);
}

    public function index()
    {
        $courses = Course::with('user', 'module', 'planning')->get();
        return response()->json(['results' => $courses], 200);
    }

    public function store(Request $request)
    {
        // Validate input data
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'duration' => 'nullable', // Ensure duration is numeric
            'module_id' => 'required|integer',
            'user_id' => 'nullable|integer', // Ensure user_id is provided
            'image' => 'nullable|image|mimes:jpeg,svg,png,jpg|max:2048'
        ]);

        try {
            $imageName = null;
            if ($request->hasFile('image')) {
                $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
                Storage::disk('public')->put($imageName, file_get_contents($request->image));
            }

            Course::create([
                'name' => $request->name,
                'description' => $request->description,
                'duration' => $request->duration,
                'module_id' => $request->module_id,
                'user_id' => $request->user_id,
                'image' => $imageName
            ]);

            return response()->json(['message' => "Course successfully created."], 201);
        } catch (\Exception $e) {
            Log::error('Error creating course: ' . $e->getMessage()); // Log the error
            return response()->json(['message' => "Something went really wrong!", 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $course = Course::findOrFail($id); // Use findOrFail to automatically return 404
        return response()->json(['course' => $course], 200);
    }

    public function updatecourse(Request $request, $id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json([
                'message' => 'course not found.'
            ], 404);
        }    
    
        $storage = Storage::disk('public');
    
        // Vérifier si une nouvelle image a été téléchargée
        if ($request->hasFile('image')) {
    
            // Supprimer l'ancienne image si elle existe
            if ($storage->exists($course->image)) {
                $storage->delete($course->image);
            }
    
            // Enregistrer la nouvelle image
            $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
            $storage->put($imageName, file_get_contents($request->image));
    
            // Mettre à jour le chemin de l'image dans la base de données
            $course->image = $imageName;
        }
    
        // Mise à jour des autres champs
        $course->update([
            'name' => $request->name,
            'description' => $request->description,
            'module_id' => $request->module_id,
            'user_id' => $request->user_id,
        ]);
    
        return response()->json([
            'message' => 'course successfully updated.',
            'course' => $course
        ], 200);
    }

    public function destroy($id)
    {
        $course =CCourse::findOrFail($id); // Use findOrFail to automatically return 404

        $storage = Storage::disk('public');
        if ($course->image && $storage->exists($course->image)) {
            $storage->delete($course->image);
        }

        $course->delete();
        return response()->json(['message' => "Course successfully deleted."], 200);
    }
}
