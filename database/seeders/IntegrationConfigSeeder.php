<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IntegrationConfig;

class IntegrationConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        IntegrationConfig::updateOrCreate(
            ['code' => 'KILAKONA_SMS'],
            [
                'name'              => 'Kilakona SMS Gateway',
                'provider'          => 'kilakona',
                'channel'           => 'sms',
                'description'       => 'Default SMS gateway for user notifications.',
                'environment'       => 'production',
                'base_url'          => 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send',
                'production_url'    => 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send',
                // Credentials come from .env; when unset, the stored values are left untouched.
                ...array_filter([
                    'api_key'    => env('KILAKONA_SMS_API_KEY'),
                    'api_secret' => env('KILAKONA_SMS_API_SECRET'),
                ]),
                'callback_url'      => 'https://your-server.com/delivery-callback',
                'extra_credentials' => ['sender_id' => 'Sokomet'],
                'is_active'         => 1,
                'is_default'        => 1,
            ]
        );

        IntegrationConfig::updateOrCreate(
            ['code' => 'GOOGLE_GEMINI_AI'],
            [
                'name'              => 'Google Gemini AI',
                'provider'          => 'google',
                'channel'           => 'ai',
                'description'       => 'Google Gemini AI for image analysis and layout generation.',
                'environment'       => 'production',
                ...array_filter(['api_key' => env('GEMINI_API_KEY')]),
                'is_active'         => 1,
                'is_default'        => 1,
            ]
        );
    }
}
