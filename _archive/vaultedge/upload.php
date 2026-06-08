<?php
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['vault']) || !isset($input['salt'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing fields"]);
    exit;
}

$email = preg_replace("/[^a-zA-Z0-9_@.-]/", "", $input['email']);
$filename = "vaults/" . md5($email) . ".json";

if (!file_exists("vaults")) {
    mkdir("vaults", 0775, true);
}

file_put_contents($filename, json_encode([
    "vault" => $input['vault'],
    "salt" => $input['salt']
], JSON_UNESCAPED_SLASHES));




echo json_encode(["success" => true]);
?>

