<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\File;

class AutoTranslateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:auto-translate {--source=en} {--targets=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically translate the base language JSON file into target languages using Google Translate.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = $this->option('source');
        $targetsOption = $this->option('targets');
        
        if ($targetsOption === 'all') {
            $targets = \App\Models\Language::where('code', '!=', 'en')->where('is_active', true)->pluck('code')->toArray();
        } else {
            $targets = explode(',', $targetsOption);
        }

        $sourceFile = base_path("lang/{$source}.json");
        if (!File::exists($sourceFile)) {
            $this->error("Source language file [{$source}.json] not found.");
            return;
        }

        $sourceData = json_decode(File::get($sourceFile), true);
        if (!$sourceData) {
            $this->error("Failed to parse source JSON.");
            return;
        }

        $tr = new GoogleTranslate();
        $tr->setSource($source);

        foreach ($targets as $target) {
            $this->info("Translating to [{$target}]...");
            $tr->setTarget($target);
            
            $targetFile = base_path("lang/{$target}.json");
            $targetData = [];
            
            if (File::exists($targetFile)) {
                $targetData = json_decode(File::get($targetFile), true) ?? [];
            }

            $translatedData = $this->translateArray($sourceData, $targetData, $tr);

            File::put($targetFile, json_encode($translatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Translation to [{$target}] complete.");
        }

        $this->info("All translations generated successfully!");
    }

    private function translateArray(array $source, array $target, GoogleTranslate $tr)
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $target[$key] = $this->translateArray($value, $target[$key] ?? [], $tr);
            } else {
                // If it's not already translated or empty
                if (!isset($target[$key]) || empty($target[$key])) {
                    try {
                        $target[$key] = $tr->translate($value);
                        // Sleep slightly to avoid Google Translate rate limits
                        usleep(100000); 
                    } catch (\Exception $e) {
                        $this->error("Failed to translate: {$value}. Error: " . $e->getMessage());
                        $target[$key] = $value; // Fallback to original
                    }
                }
            }
        }
        return $target;
    }
}
