<?php
require_once __DIR__ . "/includes/functions.php";

header("Content-Type: application/json; charset=utf-8");

$uid = preg_replace("/[^0-9]/", "", $_GET["uid"] ?? "");

if ($uid === "") {
    echo json_encode([
        "success" => false,
        "message" => "UID required."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$api_url = "https://orbittopup.com/api/checkuid?uid=" . urlencode($uid);
$response = false;

if (function_exists("curl_init")) {
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "90N.GameShop UID Checker"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
}

if ($response === false || $response === "") {
    $context = stream_context_create([
        "http" => [
            "timeout" => 12,
            "header" => "User-Agent: 90N.GameShop UID Checker\r\n"
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ]);

    $response = @file_get_contents($api_url, false, $context);
}

$data = json_decode((string)$response, true);
$player_name = trim((string)($data["message"] ?? ""));

if ($player_name !== "") {
    echo json_encode([
        "success" => true,
        "name" => $player_name
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Player name পাওয়া যায়নি."
], JSON_UNESCAPED_UNICODE);
