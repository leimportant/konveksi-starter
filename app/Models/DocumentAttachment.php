<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tr_document_attachment';

    protected $fillable = [
        'doc_id',
        'reference_id',
        'path',
        'extension',
        'url',
        'filename',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id');
    }
}