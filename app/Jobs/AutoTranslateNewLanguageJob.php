<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\AutoTranslates;
use App\Models\Language;

class AutoTranslateNewLanguageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AutoTranslates;

    public $timeout = 0;
    protected $language;
    protected $force;

    public function __construct(Language $language, bool $force = false)
    {
        $this->language = $language;
        $this->force = $force;
    }

    public function handle(): void
    {
        set_time_limit(0);

        $models = [
            \App\Models\PortalTranslation::class,
        ];

        foreach ($models as $modelClass) {
            $records = $modelClass::all();

            foreach ($records as $record) {
                if ($this->force || $this->needsTranslation($record)) {
                    $this->autoTranslateModel($record, 'en', [$this->language->code]);
                    $record->save();
                }
            }
        }

        $this->language->update(['is_active' => true]);
    }

    protected function needsTranslation($model): bool
    {
        if (!method_exists($model, 'getTranslatableAttributes')) {
            return false;
        }

        $fields = $model->getTranslatableAttributes();
        $targetLang = $this->language->code;

        foreach ($fields as $field) {
            $translations = $model->getTranslations($field);
            if (!array_key_exists($targetLang, $translations) || empty($translations[$targetLang])) {
                if (!empty($model->getTranslation($field, 'en', false))) {
                    return true;
                }
            }
        }

        return false;
    }
}
