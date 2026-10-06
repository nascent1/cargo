<?php
$req = [
    'passengers' => [
        [
            'outbound_route_id' => 1,
            'outbound_class_id' => 3, // VIP
            'outbound_fare' => 90000,
            'passenger_type_id' => 1,
            'currency' => 'TZS',
            'outbound_room_id' => null
        ]
    ]
];
$request = \Illuminate\Http\Request::create('/portal/api/fast-booking/preview', 'POST', $req);
$controller = new \App\Http\Controllers\Admin\FastBookingController();
$response = $controller->preview($request);
echo $response->getContent();
