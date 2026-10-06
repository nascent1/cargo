<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\AutoTranslates;

class AutoTranslateMissingContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AutoTranslates;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 0;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        set_time_limit(0);

        $models = [
            \App\Models\PortalTranslation::class,
        ];

        foreach ($models as $modelClass) {
            $records = $modelClass::all();

            foreach ($records as $record) {
                if ($this->needsTranslation($record)) {
                    $this->autoTranslateModel($record);
                    $record->save();
                }
            }
        }
    }

    protected function needsTranslation($model): bool
    {
        if (!method_exists($model, 'getTranslatableAttributes')) {
            return false;
        }

        $fields = $model->getTranslatableAttributes();
        $targetLangs = \App\Models\Language::where('code', '!=', 'en')->where('is_active', true)->pluck('code')->toArray();

        foreach ($fields as $field) {
            $translations = $model->getTranslations($field);
            foreach ($targetLangs as $lang) {
                if (!array_key_exists($lang, $translations) || empty($translations[$lang])) {
                    if (!empty($model->getTranslation($field, 'en', false))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
