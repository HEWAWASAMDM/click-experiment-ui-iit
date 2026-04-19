<?php
// 1. Allow the frontend to talk to this file
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2. The Pre-flight Handshake (NEW CODE)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(); // Stop here and tell Chrome we are ready!
}

// ... the rest of your code stays exactly the same ...
$jsonString = file_get_contents('php://input');
$data = json_decode($jsonString, true);

if ($data) {
    // 3. Extract the data (falling back to defaults if something is missing)
    $sequence  = $data['sequence_number'] ?? 0;
    $startTime = $data['start_time'] ?? 0;
    $endTime   = $data['end_time'] ?? 0;
    $interface = $data['interface_type'] ?? 'unknown';
    $session   = $data['session_id'] ?? 'unknown';
    $platform  = $data['device_platform'] ?? 'unknown';

    // =========================================================================
    // 4. PREPARE TO SEND TO FIRESTORE (REST API METHOD)
    // =========================================================================
    
    // TODO: Replace with your actual Firebase Project ID!
    $projectId = "iit-database-task"; 
    $collectionName = "tap_logs";

    $firestoreUrl = "https://firestore.googleapis.com/v1/projects/" . $projectId . "/databases/(default)/documents/" . $collectionName;

    // Firestore requires data to be formatted in a very specific way
    $firestoreData = [
        "fields" => [
            "sequence_number" => ["integerValue" => $sequence],
            "start_time"      => ["integerValue" => $startTime],
            "end_time"        => ["integerValue" => $endTime],
            "interface_type"  => ["stringValue"  => $interface],
            "session_id"      => ["stringValue"  => $session],
            "device_platform" => ["stringValue"  => $platform]
        ]
    ];

    // 5. Send the data to Firestore using cURL (a PHP tool for sending web requests)
    $ch = curl_init($firestoreUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firestoreData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    // 6. Tell the frontend app that everything worked!
    echo json_encode(["status" => "success", "message" => "Tap safely stored in Firestore!"]);

} else {
    // Error handling if no data was sent
    echo json_encode(["status" => "error", "message" => "No data received from the app."]);
}
?>