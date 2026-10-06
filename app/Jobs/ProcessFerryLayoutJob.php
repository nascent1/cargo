<?php

namespace App\Jobs;

use App\Models\Ferry;
use App\Services\SeatLayoutGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFerryLayoutJob implements ShouldQueue
{
    use Queueable;

    public $ferry;

    /**
     * Which deck index (0-based) to process.
     * null means legacy single-deck mode (v1).
     */
    public ?int $deckIndex;

    // Allow up to 3 minutes for AI generation
    public $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @param Ferry    $ferry     The ferry model.
     * @param int|null $deckIndex Deck index in the v2 decks array to process.
     *                            Pass null for legacy v1 single-deck ferries.
     */
    public function __construct(Ferry $ferry, ?int $deckIndex = null)
    {
        $this->ferry     = $ferry;
        $this->deckIndex = $deckIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(SeatLayoutGeneratorService $layoutService): void
    {
        try {
            $layoutJson = $this->ferry->seat_layout_json;

            // ── v2 multi-deck path ──────────────────────────────────────────────
            if ($this->deckIndex !== null && isset($layoutJson['decks'])) {
                $deck = $layoutJson['decks'][$this->deckIndex] ?? null;

                if (!$deck || empty($deck['backgroundImage'])) {
                    Log::warning("ProcessFerryLayoutJob: Deck {$this->deckIndex} has no backgroundImage for Ferry ID {$this->ferry->id}");
                    return;
                }

                // Derive local storage path from the public URL
                $imageUrl  = $deck['backgroundImage'];
                $imagePath = $this->urlToStoragePath($imageUrl);

                if (!$imagePath || !file_exists($imagePath)) {
                    Log::error("ProcessFerryLayoutJob: Cannot find image file at {$imagePath} for Ferry ID {$this->ferry->id}");
                    return;
                }

                $generatedLayout = $layoutService->generateFromJsonImage($imagePath);

                // Merge generated seats/zones back into this deck only
                $layoutJson['decks'][$this->deckIndex]['seats'] = $generatedLayout['seats'] ?? [];
                $layoutJson['decks'][$this->deckIndex]['zones'] = $generatedLayout['zones'] ?? [];

                $this->ferry->seat_layout_json = $layoutJson;
                $this->ferry->save();

                Log::info("ProcessFerryLayoutJob: Deck {$this->deckIndex} processed for Ferry ID {$this->ferry->id}");
                return;
            }

            // ── v1 legacy single-deck path ──────────────────────────────────────
            if (!$this->ferry->layout_image_path) {
                return;
            }

            $jsonLayout = $layoutService->generateFromJsonImage(
                storage_path('app/public/' . $this->ferry->layout_image_path)
            );

            // Save the newly generated layout JSON directly to the ferry
            $this->ferry->seat_layout_json = $jsonLayout;
            $this->ferry->save();

        } catch (\Exception $e) {
            Log::error("ProcessFerryLayoutJob failed for Ferry ID {$this->ferry->id}: " . $e->getMessage());

            // If it fails and we have no existing layout, revert to null
            if (empty($this->ferry->seat_layout_json)) {
                $this->ferry->seat_layout_json = null;
                $this->ferry->save();
            }
        }
    }

    /**
     * Convert a public asset URL to a local storage path.
     * e.g. https://example.com/storage/seat_layouts/abc.jpg
     *      → storage_path('app/public/seat_layouts/abc.jpg')
     */
    private function urlToStoragePath(string $url): ?string
    {
        // Extract the path segment after /storage/
        $pattern = '/\/storage\/(.+)$/';
        if (preg_match($pattern, $url, $matches)) {
            return storage_path('app/public/' . $matches[1]);
        }
        return null;
    }
}
