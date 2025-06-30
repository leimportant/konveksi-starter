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

    protected $appends = ['image_path'];

    public function getImagePathAttribute()
    {
        if ($this->url) {
            return $this->url;
        }

        // If path already contains the filename, return path directly
        if ($this->path && str_contains($this->path, $this->filename ?? '')) {
            return $this->path;
        }

        // If filename is present, combine path and filename
        if ($this->path && $this->filename) {
            return $this->path . '/' . $this->filename;
        }

        // If only path is present (e.g., 'not_available.png'), return path
        if ($this->path) {
            return $this->path;
        }

        return null; // Or a default image path if desired
    }

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