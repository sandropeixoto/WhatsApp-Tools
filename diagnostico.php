<?php
require_once 'config.php';

function checkAPI($endpoint) {
    $url = WAPI_BASE_URL . $endpoint . "?instanceId=" . WAPI_INSTANCE_ID;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WAPI_TOKEN,
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

$results = [
    'env' => [
        'php_version' => PHP_VERSION,
        'pdo_sqlite' => extension_loaded('pdo_sqlite'),
        'curl' => extension_loaded('curl'),
    ],
    'files' => [
        'database_exists' => file_exists(DB_FILE),
        'database_writable' => is_writable(dirname(DB_FILE)),
    ],
    'api' => [],
    'webhooks' => [
        'received' => ['api_url' => 'N/A', 'matches' => false],
        'delivery' => ['api_url' => 'N/A', 'matches' => false]
    ]
];

$apiRes = checkAPI('/v1/instance/fetch-instance');
$results['api'] = $apiRes;

if ($apiRes['code'] == 200 && isset($apiRes['data'])) {
    $instance = $apiRes['data'];
    
    $results['webhooks']['received']['api_url'] = $instance['webhookReceivedUrl'] ?? 'N/A';
    $results['webhooks']['received']['matches'] = ($results['webhooks']['received']['api_url'] === WEBHOOK_URL);
    
    $results['webhooks']['delivery']['api_url'] = $instance['webhookDeliveryUrl'] ?? 'N/A';
    $results['webhooks']['delivery']['matches'] = ($results['webhooks']['delivery']['api_url'] === WEBHOOK_URL);
    
    $results['status_instance'] = $instance['status'] ?? 'N/A';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico W-API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-ok { color: #198754; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .webhook-card { background: #f1f3f5; border-radius: 8px; padding: 10px; font-family: monospace; font-size: 0.85rem; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Diagnóstico do Sistema</h2>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar ao Dashboard</a>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white fw-bold">Ambiente e Arquivos</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between small">
                        PHP / SQLite / CURL
                        <span>
                            <?= $results['env']['php_version'] ?> / 
                            <?= $results['env']['pdo_sqlite'] ? 'OK' : 'ERR' ?> / 
                            <?= $results['env']['curl'] ? 'OK' : 'ERR' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between small">
                        Banco de Dados / Permissão Escrita
                        <span>
                            <?= $results['files']['database_exists'] ? 'OK' : 'ERR' ?> / 
                            <?= $results['files']['database_writable'] ? 'OK' : 'ERR' ?>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white fw-bold">Status na W-API</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        Conexão API (Token)
                        <span class="<?= $results['api']['code'] == 200 ? 'status-ok' : 'status-error' ?>">
                            <?= $results['api']['code'] == 200 ? 'Conectado' : 'Erro ' . $results['api']['code'] ?>
                        </span>
                    </li>
                    <?php if ($results['api']['code'] == 200): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        Status WhatsApp
                        <span class="<?= $results['status_instance'] == 'connected' ? 'status-ok' : 'status-error' ?>">
                            <?= $results['status_instance'] ?>
                        </span>
                    </li>
                    
                    <li class="list-group-item">
                        <div class="fw-bold mb-2">Webhooks Configurados:</div>
                        
                        <!-- Webhook Received -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small">Mensagens Recebidas</span>
                                <span class="<?= $results['webhooks']['received']['matches'] ? 'status-ok' : 'status-error' ?> small">
                                    <?= $results['webhooks']['received']['matches'] ? 'Sincronizado' : 'Divergente' ?>
                                </span>
                            </div>
                            <div class="webhook-card">W-API: <?= $results['webhooks']['received']['api_url'] ?></div>
                        </div>

                        <!-- Webhook Delivery -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small">Mensagens Enviadas (Delivery)</span>
                                <span class="<?= $results['webhooks']['delivery']['matches'] ? 'status-ok' : 'status-error' ?> small">
                                    <?= $results['webhooks']['delivery']['matches'] ? 'Sincronizado' : 'Divergente' ?>
                                </span>
                            </div>
                            <div class="webhook-card">W-API: <?= $results['webhooks']['delivery']['api_url'] ?></div>
                        </div>

                        <?php if (!$results['webhooks']['received']['matches'] || !$results['webhooks']['delivery']['matches']): ?>
                            <a href="set_webhook.php" target="_blank" class="btn btn-warning btn-sm w-100 mt-2">Corrigir Webhooks na API</a>
                        <?php endif; ?>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="text-center">
                <button onclick="window.location.reload()" class="btn btn-primary px-4">Recarregar Diagnóstico</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
