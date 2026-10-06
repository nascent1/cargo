<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PortalTranslation;
use Illuminate\Support\Facades\File;

class SyncLangToDbCommand extends Command
{
    protected $signature = 'lang:sync-to-db';
    protected $description = 'Sync all lang JSON files to the portal_translations table';

    public function handle()
    {
        $langPath = base_path('lang');
        $files = File::files($langPath);
        
        $flattened = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $locale = $file->getFilenameWithoutExtension();
                $content = json_decode(File::get($file->getPathname()), true);
                
                if ($content) {
                    $this->flattenKeys($content, '', $flattened, $locale);
                }
            }
        }

        // Now save to DB
        foreach ($flattened as $key => $translations) {
            $record = PortalTranslation::firstOrNew(['key' => $key]);
            
            $existingTranslations = $record->getTranslations('value') ?: [];
            
            foreach ($translations as $locale => $value) {
                $existingTranslations[$locale] = $value;
            }
            
            $record->setTranslations('value', $existingTranslations);
            $record->save();
            
            $this->info("Synced key: {$key}");
        }

        $this->info('Language synchronization complete!');
    }
    
    private function flattenKeys($array, $prefix, &$flattened, $locale)
    {
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            
            if (is_array($value)) {
                $this->flattenKeys($value, $newKey, $flattened, $locale);
            } else {
                if (!isset($flattened[$newKey])) {
                    $flattened[$newKey] = [];
                }
                $flattened[$newKey][$locale] = $value;
            }
        }
    }
}
