<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores, lists, serves and removes the files attached to a record.
 *
 * Files live on the private disk (storage/app/private), never under public/, so they are only
 * reachable through a controller that has checked the user's permission.
 */
class AttachmentService
{
    public const DISK = 'local';

    /** Largest file accepted, in kilobytes (10 MB). */
    public const MAX_KB = 10240;

    /** Documents and photos only; the type is checked from the file's content, not its name. */
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];

    /**
     * Attaches an uploaded file to a saved record.
     *
     * @param  string  $kind  what the file is, in lower case: "signed_quotation", "proof_of_delivery", "damage_photo"…
     *
     * @throws \Illuminate\Validation\ValidationException when the file is too large or of a type not accepted
     */
    public function store(Model $owner, UploadedFile $file, string $kind, ?int $uploadedBy = null): Attachment
    {
        if (!$owner->exists) {
            throw new InvalidArgumentException('Save the record before attaching a file to it.');
        }

        Validator::make(['file' => $file, 'kind' => $kind], [
            'file' => ['required', 'file', 'max:' . self::MAX_KB, 'mimes:' . implode(',', self::EXTENSIONS)],
            'kind' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
        ], [
            'file.max' => 'The file is larger than ' . (self::MAX_KB / 1024) . ' MB.',
            'file.mimes' => 'This type of file is not accepted. Use PDF, a photo (JPG, PNG, WEBP), Word or Excel.',
        ])->validate();

        // The stored name is random: the original name is kept only in the database.
        $path = $file->storeAs(
            'attachments/' . $owner->getTable() . '/' . $owner->getKey(),
            Str::uuid() . '.' . ($file->extension() ?: 'bin'),
            self::DISK
        );

        if ($path === false) {
            throw new RuntimeException('The file could not be saved.');
        }

        $uploadedBy ??= Auth::id();

        try {
            $attachment = Attachment::create([
                'attachable_type' => $owner->getMorphClass(),
                'attachable_id' => $owner->getKey(),
                'kind' => $kind,
                'file_path' => $path,
                'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
                'mime' => (string) $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $uploadedBy,
            ]);

            AuditLog::createEntry($uploadedBy, 'attachment_add', 'attachment', $attachment->id, null, $this->auditState($attachment), request()?->ip());
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($path); // no file without its record

            throw $e;
        }

        return $attachment;
    }

    /**
     * The files of a record, newest first; of one kind when $kind is given.
     *
     * @return Collection<int, Attachment>
     */
    public function for(Model $owner, ?string $kind = null): Collection
    {
        return Attachment::where('attachable_type', $owner->getMorphClass())
            ->where('attachable_id', $owner->getKey())
            ->when($kind, fn ($query) => $query->where('kind', $kind))
            ->latest('id')
            ->get();
    }

    public function exists(Attachment $attachment): bool
    {
        return Storage::disk(self::DISK)->exists($attachment->file_path);
    }

    /** Sends the file to the browser under its original name. The caller checks permission first. */
    public function download(Attachment $attachment): StreamedResponse
    {
        abort_unless($this->exists($attachment), 404, 'This file is no longer available.');

        return Storage::disk(self::DISK)->download($attachment->file_path, $attachment->original_name);
    }

    /** Removes the record and its file. */
    public function delete(Attachment $attachment, ?int $deletedBy = null): void
    {
        $state = $this->auditState($attachment);

        $attachment->delete();
        Storage::disk(self::DISK)->delete($attachment->file_path);

        AuditLog::createEntry($deletedBy ?? Auth::id(), 'attachment_delete', 'attachment', $attachment->id, $state, null, request()?->ip());
    }

    private function auditState(Attachment $attachment): array
    {
        return $attachment->only(['attachable_type', 'attachable_id', 'kind', 'original_name', 'mime', 'size']);
    }
}
