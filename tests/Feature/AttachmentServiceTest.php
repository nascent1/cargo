<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Station;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AttachmentService $attachments;
    private Station $record;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentService::DISK); // files go to a throwaway disk, never to storage/app/private

        $this->attachments = app(AttachmentService::class);
        $this->record = Station::first() ?? $this->markTestSkipped('Needs a station to attach files to.');
        $this->user = User::first() ?? $this->markTestSkipped('Needs a user.');
    }

    #[Test]
    public function a_file_is_stored_privately_with_its_record_and_an_audit_entry(): void
    {
        $file = UploadedFile::fake()->create('Signed quotation Q-17.pdf', 245, 'application/pdf');

        $attachment = $this->attachments->store($this->record, $file, 'signed_quotation', $this->user->id);

        $this->assertSame($this->record->getMorphClass(), $attachment->attachable_type);
        $this->assertSame($this->record->id, $attachment->attachable_id);
        $this->assertSame('signed_quotation', $attachment->kind);
        $this->assertSame('Signed quotation Q-17.pdf', $attachment->original_name);
        $this->assertSame(245 * 1024, $attachment->size);
        $this->assertSame('245 KB', $attachment->readable_size);
        $this->assertSame($this->user->id, $attachment->uploaded_by);
        $this->assertNotNull($attachment->created_at);
        $this->assertTrue($attachment->attachable->is($this->record));
        $this->assertTrue($attachment->uploader->is($this->user));

        $this->assertStringStartsWith("attachments/stations/{$this->record->id}/", $attachment->file_path);
        $this->assertStringEndsWith('.pdf', $attachment->file_path);
        $this->assertStringNotContainsString('Signed', $attachment->file_path, 'the stored name is random');
        Storage::disk(AttachmentService::DISK)->assertExists($attachment->file_path);
        $this->assertTrue($this->attachments->exists($attachment));

        $audit = AuditLog::where('action', 'attachment_add')->where('entity_id', $attachment->id)->sole();
        $this->assertSame('Signed quotation Q-17.pdf', $audit->after_state['original_name']);
    }

    #[Test]
    public function files_are_listed_per_record_and_per_kind_newest_first(): void
    {
        $other = Station::where('id', '!=', $this->record->id)->first() ?? $this->markTestSkipped('Needs two stations.');

        $first = $this->attachments->store($this->record, UploadedFile::fake()->image('damage-1.jpg'), 'damage_photo', $this->user->id);
        $second = $this->attachments->store($this->record, UploadedFile::fake()->image('damage-2.png'), 'damage_photo', $this->user->id);
        $pod = $this->attachments->store($this->record, UploadedFile::fake()->create('pod.pdf', 10, 'application/pdf'), 'proof_of_delivery', $this->user->id);
        $this->attachments->store($other, UploadedFile::fake()->create('other.pdf', 10, 'application/pdf'), 'damage_photo', $this->user->id);

        $this->assertSame([$pod->id, $second->id, $first->id], $this->attachments->for($this->record)->pluck('id')->all());
        $this->assertSame([$second->id, $first->id], $this->attachments->for($this->record, 'damage_photo')->pluck('id')->all());
        $this->assertCount(1, $this->attachments->for($other));
    }

    #[Test]
    public function a_file_of_a_type_not_accepted_is_refused_and_nothing_is_kept(): void
    {
        $before = Attachment::count();

        foreach ([
            UploadedFile::fake()->create('run-me.exe', 20, 'application/x-msdownload'),
            UploadedFile::fake()->create('script.php', 2, 'text/x-php'),
            UploadedFile::fake()->create('page.html', 2, 'text/html'),
        ] as $file) {
            try {
                $this->attachments->store($this->record, $file, 'damage_photo', $this->user->id);
                $this->fail($file->getClientOriginalName() . ' should have been refused.');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('not accepted', $e->errors()['file'][0]);
            }
        }

        $this->assertSame($before, Attachment::count());
        $this->assertSame([], Storage::disk(AttachmentService::DISK)->allFiles());
    }

    #[Test]
    public function a_file_over_ten_megabytes_is_refused(): void
    {
        $file = UploadedFile::fake()->create('huge.pdf', AttachmentService::MAX_KB + 1, 'application/pdf');

        try {
            $this->attachments->store($this->record, $file, 'signed_quotation', $this->user->id);
            $this->fail('The file should have been refused.');
        } catch (ValidationException $e) {
            $this->assertSame('The file is larger than 10 MB.', $e->errors()['file'][0]);
        }

        $ok = $this->attachments->store($this->record, UploadedFile::fake()->create('limit.pdf', AttachmentService::MAX_KB, 'application/pdf'), 'signed_quotation', $this->user->id);
        $this->assertSame('10.0 MB', $ok->readable_size, 'exactly 10 MB is accepted');
    }

    #[Test]
    public function the_kind_must_be_a_plain_lower_case_word(): void
    {
        $file = UploadedFile::fake()->create('a.pdf', 5, 'application/pdf');

        foreach (['', 'Damage Photo', '../etc', '9th'] as $kind) {
            try {
                $this->attachments->store($this->record, $file, $kind, $this->user->id);
                $this->fail("\"{$kind}\" should have been refused.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('kind', $e->errors());
            }
        }
    }

    #[Test]
    public function a_record_must_be_saved_before_a_file_is_attached(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->attachments->store(new Station(['name' => 'Not saved']), UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'), 'note');
    }

    #[Test]
    public function a_path_in_the_uploaded_name_is_dropped(): void
    {
        $file = UploadedFile::fake()->create('../../etc/passwd.pdf', 5, 'application/pdf');

        $attachment = $this->attachments->store($this->record, $file, 'note', $this->user->id);

        $this->assertSame('passwd.pdf', $attachment->original_name);
        $this->assertStringStartsWith("attachments/stations/{$this->record->id}/", $attachment->file_path);
    }

    #[Test]
    public function a_file_is_downloaded_under_its_original_name(): void
    {
        $attachment = $this->attachments->store($this->record, UploadedFile::fake()->create('Proof of delivery.pdf', 12, 'application/pdf'), 'proof_of_delivery', $this->user->id);

        $response = $this->attachments->download($attachment);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Proof of delivery.pdf', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function deleting_removes_the_record_and_the_file_and_is_audited(): void
    {
        $attachment = $this->attachments->store($this->record, UploadedFile::fake()->image('photo.jpg'), 'damage_photo', $this->user->id);
        $path = $attachment->file_path;

        $this->attachments->delete($attachment, $this->user->id);

        $this->assertNull(Attachment::find($attachment->id));
        Storage::disk(AttachmentService::DISK)->assertMissing($path);

        $audit = AuditLog::where('action', 'attachment_delete')->where('entity_id', $attachment->id)->sole();
        $this->assertSame('photo.jpg', $audit->before_state['original_name']);
        $this->assertSame($this->user->id, $audit->user_id);
    }
}
