<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'tr_document_attachment';

    protected $fillable = [
        'doc_id',
        'reference_id',
        'reference_type',
        'preview',
        'path',
        'extension',
        'url',
        'filename',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public static function generateUniqueShortId()
    {
        do {
            $numbers = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $letters = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4));
            $id = $numbers . $letters;
        } while (self::where('id', $id)->exists());

        return $id;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = self::generateUniqueShortId();
            }
        });
    }
}