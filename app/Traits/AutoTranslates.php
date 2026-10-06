<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Stichoza\GoogleTranslate\GoogleTranslate;

trait AutoTranslates
{
    /**
     * Automatically translates translatable attributes of a model using Google Translate.
     * Only fields listed in the model's $translatable property are translated.
     *
     * @param Model $model The model instance
     * @param string $sourceLang The source language (e.g. 'en')
     * @param array $targetLangs Target languages (e.g. ['sw', 'fr', 'ar'])
     */
    protected function autoTranslateModel(Model $model, $sourceLang = 'en', $targetLangs = null)
    {
        if ($targetLangs === null) {
            $targetLangs = \App\Models\Language::where('code', '!=', 'en')->where('is_active', true)->pluck('code')->toArray();
        }
        if (!method_exists($model, 'getTranslatableAttributes')) {
            return;
        }

        $tr = new GoogleTranslate();
        $tr->setSource($sourceLang);
        
        $fields = $model->getTranslatableAttributes();

        foreach ($targetLangs as $target) {
            $tr->setTarget($target);
            foreach ($fields as $field) {
                $enValue = $model->getTranslation($field, $sourceLang, false);
                if (empty($enValue)) continue;

                if ($field === 'slug') {
                    $translatedName = $model->getTranslation('name', $target, false);
                    if (!empty($translatedName)) {
                        $model->setTranslation('slug', $target, \Illuminate\Support\Str::slug($translatedName));
                    } else {
                        $translatedValue = $this->attemptTranslation($tr, $enValue);
                        $model->setTranslation('slug', $target, \Illuminate\Support\Str::slug($translatedValue));
                    }
                    continue;
                }

                if (is_array($enValue)) {
                    $translatedArr = [];
                    foreach ($enValue as $val) {
                        $translatedArr[] = $this->attemptTranslation($tr, $val);
                    }
                    $model->setTranslation($field, $target, $translatedArr);
                } else {
                    $translatedValue = $this->attemptTranslation($tr, $enValue);
                    $model->setTranslation($field, $target, $translatedValue);
                }
            }
        }
    }

    /**
     * Attempt to translate text with polite retries on failure.
     *
     * @param GoogleTranslate $tr
     * @param string $text
     * @param int $retries
     * @return string|null
     * @throws \Exception
     */
    private function attemptTranslation(GoogleTranslate $tr, string $text, int $retries = 3): ?string
    {
        for ($i = 0; $i < $retries; $i++) {
            try {
                $result = $tr->translate($text);
                usleep(400000); // 400ms polite delay
                return $result;
            } catch (\Exception $e) {
                if ($i === $retries - 1) {
                    throw $e; // Throw if we run out of retries
                }
                sleep(2); // Wait 2 seconds before retrying
            }
        }
        return $text;
    }
}
