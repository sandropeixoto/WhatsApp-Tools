<?php
require_once 'config.php';

/**
 * Script para configurar as URLs do Webhook na W-API
 * Configura tanto mensagens Recebidas quanto Enviadas (Delivery)
 */

$endpoints = [
    'Recebidas' => '/v1/webhook/update-webhook-received',
    'Enviadas (Delivery)' => '/v1/webhook/update-webhook-delivery'
];

echo "Iniciando configuração de Webhooks..." . PHP_EOL . PHP_EOL;

foreach ($endpoints as $label => $path) {
    $url = WAPI_BASE_URL . $path . "?instanceId=" . WAPI_INSTANCE_ID;

    $data = [
        'value' => WEBHOOK_URL
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WAPI_TOKEN,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "[$label]: ";
    if ($httpCode == 200) {
        echo "Sucesso (200 OK)" . PHP_EOL;
    } else {
        echo "Falha (Erro $httpCode) - Resposta: $response" . PHP_EOL;
    }
}

echo PHP_EOL . "Configuração finalizada para: " . WEBHOOK_URL . PHP_EOL;
