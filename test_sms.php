<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IntegrationConfig;
use Illuminate\Support\Facades\Http;

$integration = IntegrationConfig::where('channel', 'sms')->where('is_active', 1)->first();

if (!$integration) {
    die("No active SMS integration found.\n");
}

$apiKey = $integration->api_key;
$apiSecret = $integration->api_secret;
$extra = $integration->extra_credentials ?? [];
$senderId = $extra['sender_id'] ?? 'Sokomet';
$baseUrl = $integration->production_url ?? $integration->base_url ?? 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send';

$phone = '+255658461247';
$message = 'Test SMS response dump';

$postData = [
    'senderId' => $senderId,
    'messageType' => 'text',
    'message' => $message,
    'contacts' => $phone
];

echo "Sending SMS to $phone via $baseUrl...\n";
echo "Payload: " . json_encode($postData) . "\n";
echo "API Key: " . substr($apiKey, 0, 5) . "...\n";

try {
    $response = Http::withoutVerifying()->withHeaders([
        'Content-Type' => 'application/json',
        'api_key' => $apiKey,
        'api_secret' => $apiSecret,
    ])->post($baseUrl, $postData);

    echo "\nHTTP Status: " . $response->status() . "\n";
    echo "Response Headers: \n";
    print_r($response->headers());
    echo "\nResponse Body: \n";
    echo $response->body() . "\n";

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
