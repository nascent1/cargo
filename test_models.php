<?php
require 'vendor/autoload.php';
$config = \App\Models\IntegrationConfig::where('code', 'GOOGLE_GEMINI_AI')->first();
config(['gemini.api_key' => $config->api_key]);
$client = \Gemini::client(config('gemini.api_key'));
$response = $client->models()->list();
foreach ($response->models as $model) {
    echo $model->name . "\n";
}
