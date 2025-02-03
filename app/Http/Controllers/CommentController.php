<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index()
    {
        $comments = Comment::with('replies','user')->get();
    
        return response()->json([
            'results' => $comments
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'postforum_id' => 'required|exists:postforums,id',
            'comment_body' => 'required|string',
            'file' => 'nullable|file|max:2048'
        ]);

        try {
            $fileName = null;
            if ($request->hasFile('file')) {
                $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
                Storage::disk('public')->put('comments/' . $fileName, file_get_contents($request->file));
            }

            $comment = Comment::create([
                'user_id' => Auth::guard('api')->id(),
                'postforum_id' => $request->postforum_id,
                'comment_body' => $request->comment_body,
                'file' => $fileName,
            ]);

            return response()->json([
                'message' => 'Comment added successfully.',
                'comment' => $comment
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
        $comment = Comment::with('replies')->find($id);
        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found.'
            ], 404);
        }

        return response()->json([
            'comment' => $comment
        ], 200);
    }

    public function updatecomment(Request $request, $id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found.'
            ], 404);
        }

        $storage = Storage::disk('public');

        if ($request->hasFile('file')) {
            if ($storage->exists('comments/' . $comment->file)) {
                $storage->delete('comments/' . $comment->file);
            }

            $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
            $storage->put('comments/' . $fileName, file_get_contents($request->file));
            $comment->file = $fileName;
        }

        $comment->update([
            'comment_body' => $request->comment_body ?? $comment->comment_body,
        ]);

        return response()->json([
            'message' => 'Comment updated successfully.',
            'comment' => $comment
        ], 200);
    }

    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found.'
            ], 404);
        }

        if ($comment->file) {
            Storage::disk('public')->delete('comments/' . $comment->file);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.'
        ], 200);
    }
}
