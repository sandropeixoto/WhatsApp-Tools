<?php
require_once 'config.php';

function checkAPI($endpoint) {
    $url = WAPI_BASE_URL . $endpoint . "?instanceId=" . WAPI_INSTANCE_ID;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
        'database_writable' => is_writable(dirname(DB_FILE) ?: '.'),
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
    
    $results['status_instance'] = $instance['connected'] ? 'connected' : 'disconnected';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico W-API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .status-ok { color: #198754; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .webhook-card { background: #f1f3f5; border-radius: 8px; padding: 10px; font-family: monospace; font-size: 0.85rem; border: 1px dashed #ced4da; }
        .navbar-brand { font-weight: 800; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand h1 mb-0" href="index.php">
            <i class="bi bi-whatsapp me-2"></i>W-API Dashboard
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Mensagens</a></li>
                <li class="nav-item"><a class="nav-link" href="instancias.php">Minhas Instâncias</a></li>
                <li class="nav-item"><a class="nav-link active" href="diagnostico.php">Diagnóstico</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Diagnóstico do Sistema</h4>
                <div class="text-muted small">ID: <code><?= WAPI_INSTANCE_ID ?></code></div>
            </div>

            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Ambiente e Arquivos</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between small">
                        <span>PHP / SQLite / CURL</span>
                        <span class="fw-bold">
                            <?= $results['env']['php_version'] ?> / 
                            <span class="<?= $results['env']['pdo_sqlite'] ? 'text-success' : 'text-danger' ?>"><?= $results['env']['pdo_sqlite'] ? 'OK' : 'ERR' ?></span> / 
                            <span class="<?= $results['env']['curl'] ? 'text-success' : 'text-danger' ?>"><?= $results['env']['curl'] ? 'OK' : 'ERR' ?></span>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between small">
                        <span>Banco de Dados / Permissão Escrita</span>
                        <span class="fw-bold">
                            <span class="<?= $results['files']['database_exists'] ? 'text-success' : 'text-danger' ?>"><?= $results['files']['database_exists'] ? 'OK' : 'ERR' ?></span> / 
                            <span class="<?= $results['files']['database_writable'] ? 'text-success' : 'text-danger' ?>"><?= $results['files']['database_writable'] ? 'OK' : 'ERR' ?></span>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="card mb-4 shadow-sm border-0">
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
                            <?= strtoupper($results['status_instance']) ?>
                        </span>
                    </li>
                    
                    <li class="list-group-item">
                        <div class="fw-bold mb-3 small text-uppercase text-muted">Webhooks Configurados na API:</div>
                        
                        <!-- Webhook Received -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold">Mensagens Recebidas</span>
                                <span class="<?= $results['webhooks']['received']['matches'] ? 'status-ok' : 'status-error' ?> small">
                                    <?= $results['webhooks']['received']['matches'] ? '<i class="bi bi-check-circle-fill"></i> Sincronizado' : '<i class="bi bi-exclamation-triangle-fill"></i> Divergente' ?>
                                </span>
                            </div>
                            <div class="webhook-card"><?= $results['webhooks']['received']['api_url'] ?></div>
                        </div>

                        <!-- Webhook Delivery -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold">Mensagens Enviadas (Delivery)</span>
                                <span class="<?= $results['webhooks']['delivery']['matches'] ? 'status-ok' : 'status-error' ?> small">
                                    <?= $results['webhooks']['delivery']['matches'] ? '<i class="bi bi-check-circle-fill"></i> Sincronizado' : '<i class="bi bi-exclamation-triangle-fill"></i> Divergente' ?>
                                </span>
                            </div>
                            <div class="webhook-card"><?= $results['webhooks']['delivery']['api_url'] ?></div>
                        </div>

                        <?php if (!$results['webhooks']['received']['matches'] || !$results['webhooks']['delivery']['matches']): ?>
                            <div class="alert alert-warning small mt-3 border-0">
                                <strong>Atenção:</strong> A URL configurada na W-API é diferente da URL definida no seu arquivo <code>config.php</code>.
                                Clique no botão abaixo para atualizar automaticamente.
                            </div>
                            <a href="set_webhook.php" class="btn btn-warning btn-sm w-100 py-2">
                                <i class="bi bi-gear-fill me-1"></i> Corrigir Webhooks na API
                            </a>
                        <?php else: ?>
                            <div class="alert alert-success small mt-3 border-0">
                                <i class="bi bi-check-lg me-1"></i> Todas as URLs de Webhook estão corretamente configuradas na API.
                            </div>
                        <?php endif; ?>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="text-center">
                <button onclick="window.location.reload()" class="btn btn-primary px-4">
                    <i class="bi bi-arrow-clockwise me-1"></i> Recarregar Diagnóstico
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
