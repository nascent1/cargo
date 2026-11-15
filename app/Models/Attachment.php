<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A file attached to any record (a signed quotation, a proof of delivery, a damage photo).
 * Files are kept on the private disk; store, read and delete them through App\Services\AttachmentService.
 */
class Attachment extends Model
{
    public const UPDATED_AT = null; // an attachment is never edited, only added or removed

    protected $fillable = [
        'attachable_type', 'attachable_id', 'kind', 'file_path', 'original_name', 'mime', 'size', 'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** "245 KB", "1.2 MB". */
    public function getReadableSizeAttribute(): string
    {
        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1) . ' MB'
            : max(1, (int) round($this->size / 1024)) . ' KB';
    }
}
