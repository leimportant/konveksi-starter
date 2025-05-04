<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reference;
use App\Models\DocumentAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function upload(Request $request, $referenceId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'doc_id' => 'required|string',
            'remark' => 'nullable|string'
        ]);

        // Get reference data
        $reference = Reference::findOrFail($referenceId);
        
        // Get file details
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();
        
        // Create folder path based on reference name and date
        $folderPath = sprintf(
            'documents/%s/%s/%s',
            strtolower($reference->name),
            date('Y'),
            date('m')
        );

        // Generate unique filename
        $filename = uniqid() . '.' . $extension;
        
        // Store file
        $path = $file->storeAs($folderPath, $filename, 'public');
        
        // Create document record
        $document = DocumentAttachment::create([
            'doc_id' => $request->doc_id,
            'reference_id' => $referenceId,
            'path' => $path,
            'extension' => $extension,
            'url' => Storage::url($path),
            'filename' => $originalName,
            'remark' => $request->remark,
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document
        ]);
    }
}