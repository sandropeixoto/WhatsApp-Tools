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
        'config_exists' => file_exists('config.php'),
    ],
    'api' => [],
    'webhook' => [
        'configured_url' => WEBHOOK_URL,
        'matches' => false,
        'api_url' => 'Não identificado'
    ]
];

// Teste de conexão com a API
$apiRes = checkAPI('/v1/instance/fetch-instance');
$results['api'] = $apiRes;

if ($apiRes['code'] == 200 && isset($apiRes['data'])) {
    $instance = $apiRes['data'];
    $results['webhook']['api_url'] = $instance['webhookReceivedUrl'] ?? 'N/A';
    $results['webhook']['matches'] = ($results['webhook']['api_url'] === WEBHOOK_URL);
    $results['webhook']['status_instance'] = $instance['status'] ?? 'N/A';
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

            <!-- Ambiente Server -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white fw-bold">Ambiente do Servidor</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        PHP Version <span><?= $results['env']['php_version'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Extensão PDO SQLite 
                        <span class="<?= $results['env']['pdo_sqlite'] ? 'status-ok' : 'status-error' ?>">
                            <?= $results['env']['pdo_sqlite'] ? 'Instalada' : 'Não encontrada' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Extensão CURL 
                        <span class="<?= $results['env']['curl'] ? 'status-ok' : 'status-error' ?>">
                            <?= $results['env']['curl'] ? 'Instalada' : 'Não encontrada' ?>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Arquivos Locais -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white fw-bold">Arquivos e Permissões</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        Banco de Dados (SQLite)
                        <span class="<?= $results['files']['database_exists'] ? 'status-ok' : 'status-error' ?>">
                            <?= $results['files']['database_exists'] ? 'Encontrado' : 'Aguardando criação' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Permissão de Escrita na Pasta
                        <span class="<?= $results['files']['database_writable'] ? 'status-ok' : 'status-error' ?>">
                            <?= $results['files']['database_writable'] ? 'OK' : 'Sem permissão' ?>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Conexão API -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white fw-bold">Status na W-API</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        Conexão com a API (Token)
                        <span class="<?= $results['api']['code'] == 200 ? 'status-ok' : 'status-error' ?>">
                            <?= $results['api']['code'] == 200 ? 'Sucesso (200 OK)' : 'Erro (' . $results['api']['code'] . ')' ?>
                        </span>
                    </li>
                    <?php if ($results['api']['code'] == 200): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        Status do WhatsApp na Instância
                        <span class="<?= $results['webhook']['status_instance'] == 'connected' ? 'status-ok' : 'status-error' ?>">
                            <?= $results['webhook']['status_instance'] ?>
                        </span>
                    </li>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between mb-2">
                            Webhook Configurado na API
                            <span class="<?= $results['webhook']['matches'] ? 'status-ok' : 'status-error' ?>">
                                <?= $results['webhook']['matches'] ? 'Sincronizado' : 'Divergente' ?>
                            </span>
                        </div>
                        <div class="small p-2 bg-dark text-light rounded font-monospace">
                            Local: <?= WEBHOOK_URL ?><br>
                            W-API: <?= $results['webhook']['api_url'] ?>
                        </div>
                        <?php if (!$results['webhook']['matches']): ?>
                            <div class="mt-2">
                                <a href="set_webhook.php" target="_blank" class="btn btn-warning btn-sm w-100">Atualizar Webhook na API</a>
                            </div>
                        <?php endif; ?>
                    </li>
                    <?php else: ?>
                    <li class="list-group-item text-danger small">
                        Não foi possível recuperar dados da API. Verifique seu WAPI_TOKEN e WAPI_INSTANCE_ID no arquivo config.php.
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="text-center">
                <button onclick="window.location.reload()" class="btn btn-primary">Recarregar Testes</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
