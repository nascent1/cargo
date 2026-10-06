<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'event', 'channel', 'locale', 'subject', 'body', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Render the template body with given data.
     */
    public function render(array $data): string
    {
        $body = $this->body;
        foreach ($data as $key => $value) {
            $body = str_replace("{{" . $key . "}}", (string) $value, $body);
        }
        return $body;
    }

    /**
     * Find the best template for an event/channel/locale combination.
     * Falls back to 'en' locale if the requested locale is not found.
     */
    public static function findTemplate(string $event, string $channel, string $locale = 'en'): ?self
    {
        return static::where('event', $event)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first()
            ?? static::where('event', $event)
                ->where('channel', $channel)
                ->where('locale', 'en')
                ->where('is_active', true)
                ->first();
    }
}
