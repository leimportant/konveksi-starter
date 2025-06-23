<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\DocumentAttachment;


class DocumentAttachmentController extends Controller
{
    public function upload(Request $request)
    {
        // Validasi input
        $request->validate([
            'file' => 'required|file|max:10240', // maksimal 10MB
            'doc_id' => 'required|string',
            'reference_id' => 'required|integer',
            'remark' => 'nullable|string',
        ]);

        try {
            // Ambil file dari request
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

            // Simpan ke Google Drive
            $filePath = Storage::disk('google')->putFileAs('', $file, $fileName);
            $fileId = basename($filePath);

            // Generate URL dan preview
            $url = "";
            $preview = "";
            // Simpan metadata ke database
            $attachment = DocumentAttachment::create([
                'id' => (string) Str::uuid(),
                'doc_id' => $request->doc_id,
                'reference_id' => $request->reference_id,
                'path' => $filePath,
                'extension' => $extension,
                'url' => $url,
                'filename' => $originalName,
                'preview' => $preview,
                'remark' => $request->remark,
                'created_by' => Auth::id(), // pastikan nullable jika tidak login
            ]);

            return response()->json([
                'message' => 'File uploaded and saved successfully',
                'document' => $attachment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'File upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadDrive(Request $request)
    {
        // Validasi input
        $request->validate([
            'file' => 'required|file|max:10240', // maksimal 10MB
            'doc_id' => 'required|string',
            'reference_id' => 'required|integer',
            'remark' => 'nullable|string',
        ]);

        try {
            // Ambil file dari request
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

            // Simpan ke Google Drive
            $filePath = Storage::disk('google')->putFileAs('', $file, $fileName);
            $fileId = basename($filePath);

            // Generate URL dan preview
            $url = "https://drive.google.com/uc?id={$fileId}&export=download";
            $preview = match (true) {
                in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => "https://drive.google.com/uc?id={$fileId}&export=view",
                in_array($extension, ['mp4', 'webm', 'ogg']) => "https://drive.google.com/file/d/{$fileId}/preview",
                default => $url,
            };

            // Simpan metadata ke database
            $attachment = DocumentAttachment::create([
                'id' => (string) Str::uuid(),
                'doc_id' => $request->doc_id,
                'reference_id' => $request->reference_id,
                'path' => $filePath,
                'extension' => $extension,
                'url' => $url,
                'filename' => $originalName,
                'preview' => $preview,
                'remark' => $request->remark,
                'created_by' => Auth::id(), // pastikan nullable jika tidak login
            ]);

            return response()->json([
                'message' => 'File uploaded and saved successfully',
                'document' => $attachment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'File upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $documentAttachments = DocumentAttachment::all();
        return response()->json(['data' => $documentAttachments], 200);
    }

    public function destroy($id)
    {
        try {
            $documentAttachment = DocumentAttachment::findOrFail($id);
            Storage::disk('google')->delete($documentAttachment->path);
            $documentAttachment->delete();

            return response()->json(['message' => 'File deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'File deletion failed', 'error' => $e->getMessage()], 500);
        }
    }
}
