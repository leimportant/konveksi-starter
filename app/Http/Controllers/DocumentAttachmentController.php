<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\DocumentAttachment;


class DocumentAttachmentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'doc_id' => 'required|string',
            'reference_id' => 'required|integer',
            'remark' => 'nullable|string',
        ]);

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $filePath = Storage::disk('google')->putFileAs('', $file, $fileName);
            $fileId = basename($filePath);

            $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $isVideo = in_array(strtolower($fileExtension), ['mp4', 'webm', 'ogg']);

            $fileId = basename($filePath);
            $fileUrl = "https://drive.google.com/uc?id={$fileId}&export=download";

            if ($isImage) {
                $previewFile = "https://drive.google.com/uc?id={$fileId}&export=view";
            } elseif ($isVideo) {
                $previewFile = "https://drive.google.com/file/d/{$fileId}/preview";
            } else {
                $previewFile = $fileUrl;
            }


            $attachment = DocumentAttachment::create([
                'id' => (string) Str::uuid(),
                'doc_id' => $request->doc_id,
                'reference_id' => $request->reference_id,
                'path' => $filePath,
                'extension' => $fileExtension,
                'url' => $fileUrl,
                'filename' => $file->getClientOriginalName(),
                'preview' => $previewFile,
                'remark' => $request->remark,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'File uploaded and saved successfully',
                'data' => $attachment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'File upload failed',
                'error' => $e->getMessage()
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
