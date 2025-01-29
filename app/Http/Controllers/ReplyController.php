<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReplyController extends Controller
{
    public function index()
    {
        $replies = Reply::with(['comment', 'user'])->get();
    
        return response()->json([
            'results' => $replies
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'comment_id' => 'required|exists:comments,id',
            'reply_body' => 'required|string',
            'file' => 'nullable|file|max:2048'
        ]);

        try {
            $fileName = null;
            if ($request->hasFile('file')) {
                $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
                Storage::disk('public')->put('replies/' . $fileName, file_get_contents($request->file));
            }

            $reply = Reply::create([
                'user_id' => auth()->id(),
                'comment_id' => $request->comment_id,
                'reply_body' => $request->reply_body,
                'file' => $fileName,
            ]);

            return response()->json([
                'message' => 'Reply added successfully.',
                'reply' => $reply
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
        $reply = Reply::find($id);
        if (!$reply) {
            return response()->json([
                'message' => 'Reply not found.'
            ], 404);
        }

        return response()->json([
            'reply' => $reply
        ], 200);
    }

    public function updatereply(Request $request, $id)
    {
        $reply = Reply::find($id);
        if (!$reply) {
            return response()->json([
                'message' => 'Reply not found.'
            ], 404);
        }

        $storage = Storage::disk('public');

        if ($request->hasFile('file')) {
            if ($storage->exists('replies/' . $reply->file)) {
                $storage->delete('replies/' . $reply->file);
            }

            $fileName = Str::random(32) . "." . $request->file->getClientOriginalExtension();
            $storage->put('replies/' . $fileName, file_get_contents($request->file));
            $reply->file = $fileName;
        }

        $reply->update([
            'reply_body' => $request->reply_body ?? $reply->reply_body,
        ]);

        return response()->json([
            'message' => 'Reply updated successfully.',
            'reply' => $reply
        ], 200);
    }

    public function destroy($id)
    {
        $reply = Reply::find($id);
        if (!$reply) {
            return response()->json([
                'message' => 'Reply not found.'
            ], 404);
        }

        if ($reply->file) {
            Storage::disk('public')->delete('replies/' . $reply->file);
        }

        $reply->delete();

        return response()->json([
            'message' => 'Reply deleted successfully.'
        ], 200);
    }
}
