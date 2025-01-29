<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modules = Module::with('formation', 'courses')->get();
        
        // Return Json Response
        return response()->json([
            'results' => $modules
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate input data
        $request->validate([
            'name' => 'required',
            'description' => 'required',
          
            'formation_id' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,svg,png,jpg|max:2048'
        ]);

        try {
            $imageName = null;
            if ($request->hasFile('image')) {
                // Generate a unique name for the image
                $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
                // Save Image in the storage
                Storage::disk('public')->put($imageName, file_get_contents($request->image));
            }

            // Create Module
            Module::create([
                'name' => $request->name,
                'description' => $request->description,
                'formation_id' => $request->formation_id,
           
                'image' => $imageName // Save the image name in the database
            ]);

            // Return Json Response
            return response()->json([
                'message' => "Module successfully created."
            ], 201);
        } catch (\Exception $e) {
            // Return Json Response
            return response()->json([
                'message' => "Something went really wrong!"
            ], 500);
        }
    }

    public function show($id)
    {
        // Module Detail
        $module = Module::find($id);
        if (!$module) {
            return response()->json([
                'message' => 'Module Not Found.'
            ], 404);
        }

        // Return Json Response
        return response()->json([
            'module' => $module
        ], 200);
    }


    public function updatemodule(Request $request, $id)
    {
        $module = Module::find($id);
        if (!$module) {
            return response()->json([
                'message' => 'module not found.'
            ], 404);
        }    
    
        $storage = Storage::disk('public');
    
        // Vérifier si une nouvelle image a été téléchargée
        if ($request->hasFile('image')) {
    
            // Supprimer l'ancienne image si elle existe
            if ($storage->exists($module->image)) {
                $storage->delete($module->image);
            }
    
            // Enregistrer la nouvelle image
            $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
            $storage->put($imageName, file_get_contents($request->image));
    
            // Mettre à jour le chemin de l'image dans la base de données
            $module->image = $imageName;
        }
    
        // Mise à jour des autres champs
        $module->update([
            'name' => $request->name,
            'description' => $request->description,
        
            'formation_id' => $request->formation_id,
        ]);
    
        return response()->json([
            'message' => 'module successfully updated.',
            'module' => $module
        ], 200);
    }
    





    public function destroy($id)
    {
        // Find Module
        $module = Module::find($id);
        if (!$module) {
            return response()->json([
                'message' => 'Module Not Found.'
            ], 404);
        }

        // Public storage
        $storage = Storage::disk('public');

        // Delete image if exists
        if ($module->image && $storage->exists($module->image)) {
            $storage->delete($module->image);
        }

        // Delete Module
        $module->delete();

        // Return Json Response
        return response()->json([
            'message' => "Module successfully deleted."
        ], 200);
    }
}
