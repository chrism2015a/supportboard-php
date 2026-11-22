<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB
    const ALLOWED_TYPES = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'],
        'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'],
        'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
    ];

    /**
     * Upload a file attachment.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'message_id' => 'nullable|integer|exists:messages,id',
            'file' => 'required|file|max:25600', // 25MB
        ]);

        $file = $request->file('file');

        // Validate mime type
        $allowedMimes = [];
        foreach (self::ALLOWED_TYPES as $types) {
            $allowedMimes = array_merge($allowedMimes, $types);
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json([
                'message' => 'File type not allowed. Allowed: ' . implode(', ', array_keys(self::ALLOWED_TYPES)),
            ], 422);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'message' => 'File too large. Maximum size is 25MB.',
            ], 422);
        }

        // Determine file type
        $mimeType = $file->getMimeType();
        $fileType = 'file';
        foreach (self::ALLOWED_TYPES as $type => $mimes) {
            if (in_array($mimeType, $mimes)) {
                $fileType = $type;
                break;
            }
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = 'attachments/' . date('Y/m');

        // Store file
        $storedPath = $file->storeAs($path, $filename, 'public');

        if (!$storedPath) {
            return response()->json([
                'message' => 'Failed to store file.',
            ], 500);
        }

        $attachment = Attachment::create([
            'message_id' => $validated['message_id'] ?? null,
            'user_id' => auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'type' => $fileType,
        ]);

        return response()->json([
            'attachment' => $attachment,
            'url' => $attachment->url,
        ], 201);
    }

    /**
     * Download an attachment.
     */
    public function download(string $id)
    {
        $attachment = Attachment::findOrFail($id);

        if (!Storage::disk('public')->exists($attachment->stored_path)) {
            return response()->json([
                'message' => 'File not found.',
            ], 404);
        }

        return Storage::disk('public')->download(
            $attachment->stored_path,
            $attachment->original_name
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(string $id)
    {
        $attachment = Attachment::findOrFail($id);

        Storage::disk('public')->delete($attachment->stored_path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted.']);
    }

    /**
     * Get attachments for a message.
     */
    public function forMessage(string $messageId)
    {
        $attachments = Attachment::where('message_id', $messageId)->get();

        return response()->json($attachments);
    }

    /**
     * Preview an image attachment.
     */
    public function preview(string $id)
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->isImage()) {
            return response()->json([
                'message' => 'Not an image.',
            ], 400);
        }

        if (!Storage::disk('public')->exists($attachment->stored_path)) {
            return response()->json([
                'message' => 'File not found.',
            ], 404);
        }

        $content = Storage::disk('public')->get($attachment->stored_path);

        return response($content)
            ->header('Content-Type', $attachment->mime_type)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
