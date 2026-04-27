<?php
require_once 'config.php';

header('Content-Type: text/plain');

echo "--- TESTE DE CONEXÃO W-API ---\n\n";

// 1. Testando List Instances
echo "1. Chamando: " . WAPI_BASE_URL . "/v1/client/list-instances?apiKey=" . WAPI_TOKEN . "\n";
$ch = curl_init(WAPI_BASE_URL . '/v1/client/list-instances?apiKey=' . WAPI_TOKEN);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . WAPI_TOKEN
]);
$res1 = curl_exec($ch);
$info1 = curl_getinfo($ch);
$err1 = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $info1['http_code'] . "\n";
echo "Curl Error: " . $err1 . "\n";
echo "Resposta Bruta:\n" . $res1 . "\n\n";

// 2. Testando Fetch Instance (usando ID da config)
echo "2. Chamando: " . WAPI_BASE_URL . "/v1/instance/fetch-instance?instanceId=" . WAPI_INSTANCE_ID . "\n";
$ch = curl_init(WAPI_BASE_URL . '/v1/instance/fetch-instance?instanceId=' . WAPI_INSTANCE_ID);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . WAPI_TOKEN
]);
$res2 = curl_exec($ch);
$info2 = curl_getinfo($ch);
$err2 = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $info2['http_code'] . "\n";
echo "Curl Error: " . $err2 . "\n";
echo "Resposta Bruta:\n" . $res2 . "\n\n";

echo "--- FIM DO TESTE ---";
