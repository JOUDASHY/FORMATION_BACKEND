<?php

namespace App\Http\Controllers;

use App\Models\Postforum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PostforumController extends Controller
{
public function index()
{
    // Charger les posts par ordre décroissant (du plus récent au plus ancien)
    $postforums = Postforum::with(['comments', 'user'])
        ->orderBy('created_at', 'desc') // Ajouter l'ordre
        ->get(); 
    
    return response()->json([
        'results' => $postforums
    ], 200);
}

    

    public function store(Request $request)
    {
        $request->validate([
           
            'body' => 'required|string',
            'file' => 'nullable|file|max:2048'
        ]);

        try {
            $fileName = null;
            if ($request->hasFile('file')) {
                $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
                Storage::disk('public')->put('postforums/' . $fileName, file_get_contents($request->file));
            }

            $postforum = Postforum::create([
                'user_id' => Auth::guard('api')->id(),
              
                'body' => $request->body,
                'file' => $fileName,
            ]);

            return response()->json([
                'message' => 'Post created successfully.',
                'postforum' => $postforum
            ], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $postforum = Postforum::with('comments')->find($id);
        if (!$postforum) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        return response()->json([
            'postforum' => $postforum
        ], 200);
    }

    public function updatepostforum(Request $request, $id)
    {
        $postforum = Postforum::find($id);
        if (!$postforum) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        $request->validate([
          
            'body' => 'string',
            'file' => 'nullable|file|max:2048'
        ]);

        $storage = Storage::disk('public');

        if ($request->hasFile('file')) {
            if ($storage->exists('postforums/' . $postforum->file)) {
                $storage->delete('postforums/' . $postforum->file);
            }

            $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
            $storage->put('postforums/' . $fileName, file_get_contents($request->file));
            $postforum->file = $fileName;
        }

        $postforum->update([
            'title' => $request->title ?? $postforum->title,
            'body' => $request->body ?? $postforum->body,
        ]);

        return response()->json([
            'message' => 'Post updated successfully.',
            'postforum' => $postforum
        ], 200);
    }

    public function destroy($id)
    {
        $postforum = Postforum::find($id);
        if (!$postforum) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        if ($postforum->file) {
            Storage::disk('public')->delete('postforums/' . $postforum->file);
        }

        $postforum->delete();

        return response()->json([
            'message' => 'Post deleted successfully.'
        ], 200);
    }
}
