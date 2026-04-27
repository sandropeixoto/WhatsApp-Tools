<?php
require_once 'config.php';

/**
 * Script para configurar a URL do Webhook na W-API
 */

$url = WAPI_BASE_URL . "/v1/webhook/update-webhook-received?instanceId=" . WAPI_INSTANCE_ID;

$data = [
    'value' => WEBHOOK_URL
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . WAPI_TOKEN,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Erro no CURL: " . $error;
} else {
    echo "Status Code: " . $httpCode . PHP_EOL;
    echo "Resposta da API: " . $response . PHP_EOL;
    
    if ($httpCode == 200) {
        echo "Webhook configurado com sucesso para: " . WEBHOOK_URL . PHP_EOL;
    } else {
        echo "Falha ao configurar webhook. Verifique seu token e instanceId." . PHP_EOL;
    }
}
