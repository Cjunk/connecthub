<?php
header('Content-Type: application/json');

$email = $_GET['email'] ?? '';
$dataFile = 'vaults/' . md5($email) . '.json';

if (!file_exists($dataFile)) {
  echo json_encode(['error' => 'Vault not found']);
  exit;
}

$vaultData = json_decode(file_get_contents($dataFile), true);
echo json_encode([
  'vault' => $vaultData['vault'],
  'salt' => $vaultData['salt']
], JSON_UNESCAPED_SLASHES); // <- IMPORTANT!

