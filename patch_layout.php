<?php

$content = file_get_contents('app/Services/MobileBookingService.php');

$startMarker = '    public function getSeatLayout(int $voyageId, int $classId): array';
$endMarker = "    }\n}\n";

$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);

if ($startPos !== false && $endPos !== false) {
    $newMethod = <<<'METHOD'
    public function getSeatLayout(int $voyageId, int $classId): array
    {
        $voyage = Voyage::with('ferry')->findOrFail($voyageId);
        $ferry = $voyage->ferry;

        $layoutData = null;
        if ($ferry && $ferry->has_layout && !empty($ferry->seat_layout_json)) {
            $layoutData = $ferry->seat_layout_json;
        }

        // Find sold and held seats for this voyage/class
        $unavailableTickets = Ticket::where('voyage_id', $voyageId)
            ->whereIn('status', ['confirmed', 'boarded'])
            ->pluck('seat_number')
            ->filter()
            ->toArray();

        // Query held seats from booking locks
        $heldSeats = \App\Models\BookingLock::where('voyage_id', $voyageId)
            ->where('expires_at', '>', now())
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->filter()
            ->toArray();

        if ($layoutData && isset($layoutData['version']) && $layoutData['version'] === 2) {
            return [
                'layout' => [
                    'version' => 2,
                    'decks' => $layoutData['decks'] ?? [],
                    'sold' => array_values($unavailableTickets),
                    'held' => array_values($heldSeats),
                    'blocked' => []
                ]
            ];
        }

        // Legacy v1 layout parsing
        $grid = [];
        if ($layoutData) {
            $classLayout = $layoutData[$classId] ?? $layoutData[(string) $classId] ?? null;
            if ($classLayout && isset($classLayout['grid'])) {
                $grid = $classLayout['grid'];
            }
        }

        // Fallback: generate a simple grid from the class capacity if no layout configured
        if (empty($grid)) {
            $ferryClass = \App\Models\FerryClass::find($classId);
            $capacity = $ferryClass ? (int) $ferryClass->capacity : 20;
            $seatsPerRow = 4;
            $rows = (int) ceil($capacity / $seatsPerRow);
            $rowLabels = range('A', 'Z');

            for ($r = 0; $r < min($rows, 26); $r++) {
                $row = [];
                for ($s = 1; $s <= $seatsPerRow; $s++) {
                    $seatIndex = $r * $seatsPerRow + $s;
                    if ($seatIndex <= $capacity) {
                        $row[] = $rowLabels[$r] . $s;
                    } else {
                        $row[] = null;
                    }
                    if ($s === 2) {
                        $row[] = null;
                    }
                }
                $grid[] = $row;
            }
        }

        return [
            'layout' => [
                'version' => 1,
                'rows' => count($grid),
                'columns' => !empty($grid) ? count($grid[0]) : 0,
                'grid' => $grid,
                'sold' => array_values($unavailableTickets),
                'held' => array_values($heldSeats),
                'blocked' => []
            ]
        ];
METHOD;

    $newContent = substr($content, 0, $startPos) . $newMethod . substr($content, $endPos);
    file_put_contents('app/Services/MobileBookingService.php', $newContent);
    echo "Patched layout successfully\n";
} else {
    echo "Markers not found\n";
}
