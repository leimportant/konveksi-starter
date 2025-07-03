<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentAttachment;

class DocumentAttachmentController extends Controller
{
    public function upload(Request $request)
    {
        // Validasi input
       $request->validate([
            'file' => 'required|file|mimetypes:video/mp4,image/jpeg,image/png|max:20480',
            'doc_id' => 'string',
            'reference_id' => 'required',
            'reference_type' => 'required|string',
            'remark' => 'nullable|string',
        ]);

        // Validasi tambahan khusus ukuran untuk selain mp4
        if ($request->file('file')->getMimeType() !== 'video/mp4') {
            if ($request->file('file')->getSize() > 50 * 1024 * 1024) {
                return back()->withErrors(['file' => 'File maksimal 50MB untuk jenis non-video.']);
            }
        }

        try {
            // Ambil file dari request
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

            // Simpan Storage di web
            $reference_type = $request->reference_type;
            $filePath = Storage::disk('public')->putFileAs($reference_type, $file, $fileName);
            $fileId = basename($filePath);

            // Generate URL dan preview
            $url = "";
            $preview = "";

            // Penyesuaian field jika kosong
            $docId = $request->doc_id ?: $request->reference_id;
            $filename = $originalName ?: $fileName;
            $url = $url ?: $filePath;
            $path = $filePath ?: '';
            // Simpan metadata ke database
            $id = DocumentAttachment::generateUniqueShortId();
            $attachment = DocumentAttachment::create([
                'id' =>  $id,
                'doc_id' => $docId,
                'reference_id' => $request->reference_id,
                'reference_type' => $request->reference_type,
                'path' => $path,
                'extension' => $extension,
                'url' => $url,
                'filename' => $filename,
                'preview' => $preview,
                'remark' => $request->remark,
                'created_by' => Auth::id(), // pastikan nullable jika tidak login
            ]);

            return response()->json($attachment, 201);

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

    public function index(Request $request)
    {
        $referenceType = $request->query('reference_type', 'document');
        $referenceId = $request->query('reference_id');


        $documentAttachments = DocumentAttachment::where('reference_type', $referenceType)
            ->when($referenceId, function ($query) use ($referenceId) {
                return $query->where('reference_id', $referenceId)
                            ->orWhere('doc_id', $referenceId); // Tambahkan kondisi untuk doc_id
            })
            ->whereNull('deleted_at') // Pastikan tidak mengambil yang sudah dihapus
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $documentAttachments], 200);
    }

    public function destroy($id)
    {
        try {
            $startTime = microtime(true);
            $documentAttachment = DocumentAttachment::findOrFail($id);
            $findTime = microtime(true);
            Log::info('DocumentAttachment find time: ' . ($findTime - $startTime) . ' seconds');

            Storage::disk('public')->delete($documentAttachment->path);
            $storageDeleteTime = microtime(true);
            Log::info('Storage delete time: ' . ($storageDeleteTime - $findTime) . ' seconds');

            $documentAttachment->delete();
            $dbDeleteTime = microtime(true);
            Log::info('DB delete time: ' . ($dbDeleteTime - $storageDeleteTime) . ' seconds');

            Log::info('Total destroy method time: ' . ($dbDeleteTime - $startTime) . ' seconds');

            return response()->json(['message' => 'File berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus file', 'error' => $e->getMessage()], 500);
        }
    }

    public function viewAttachment(Request $request, $id)
    {
        try {
            $attachment = DocumentAttachment::where('doc_id', $id);

            if ($request->has('doc_id')) {
                $attachment->orWhere('reference_id', $request->query('doc_id'));
            }
            if ($request->has('reference_type')) {
                $attachment->where('reference_type', $request->query('reference_type'));
            }   
            if ($attachment->isEmpty()) {
                return response()->json(['message' => 'Attachment not found'], 404);
            }
            $filePath = $attachment->first()->path;
            $fileUrl = Storage::disk('public')->url($filePath);
            return response()->json(['url' => $fileUrl], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve attachment', 'error' => $e->getMessage()], 500);
        }
    }
}
