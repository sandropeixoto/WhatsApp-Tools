<?php
require_once 'config.php';

$baseInstances = listInstances();
$instances = [];

// Busca detalhes para cada instância para ter dados completos (conexão, stats, etc)
foreach ($baseInstances as $base) {
    $details = fetchInstanceDetails($base['instanceId']);
    if ($details) {
        $instances[] = $details;
    } else {
        $instances[] = $base; // Fallback para dados básicos se falhar
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Instâncias - W-API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; }
        .instance-card { border-radius: 12px; border: none; transition: all 0.3s ease; border-top: 4px solid #198754; }
        .instance-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.7rem; padding: 4px 8px; border-radius: 50px; text-transform: uppercase; font-weight: bold; }
        .info-label { font-size: 0.7rem; color: #6c757d; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 0.85rem; color: #212529; margin-bottom: 10px; word-break: break-all; }
        .stat-box { background: #f8f9fa; border-radius: 8px; padding: 8px; text-align: center; border: 1px solid #eee; }
        .stat-value { font-weight: bold; font-size: 1rem; color: #198754; display: block; }
        .stat-label { font-size: 0.65rem; text-transform: uppercase; color: #6c757d; }
        .navbar-brand { font-weight: 800; }
        .conn-status { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

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
                <li class="nav-item"><a class="nav-link active" href="instancias.php">Minhas Instâncias</a></li>
                <li class="nav-item"><a class="nav-link" href="diagnostico.php">Diagnóstico</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Gerenciamento de Instâncias</h4>
            <p class="text-muted small">Status em tempo real e métricas da API</p>
        </div>
        <button class="btn btn-outline-success btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i> Atualizar Status
        </button>
    </div>

    <div class="row">
        <?php foreach ($instances as $inst): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card instance-card shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="conn-status <?= ($inst['connected'] ?? false) ? 'bg-success' : 'bg-danger' ?>"></span>
                            <span class="small fw-bold <?= ($inst['connected'] ?? false) ? 'text-success' : 'text-danger' ?>">
                                <?= ($inst['connected'] ?? false) ? 'CONECTADO' : 'DESCONECTADO' ?>
                            </span>
                        </div>
                        <span class="status-badge bg-primary-subtle text-primary">
                            Plano <?= $inst['planType'] ?? 'LITE' ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-1"><?= htmlspecialchars($inst['instanceName'] ?: 'Sem Nome') ?></h5>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-telephone me-1"></i> <?= $inst['connectedPhone'] ?: 'Nenhum número vinculado' ?>
                        </p>
                        
                        <!-- Mini Stats -->
                        <div class="row g-2 mb-3">
                            <div class="col-3">
                                <div class="stat-box">
                                    <span class="stat-value"><?= $inst['chats'] ?? 0 ?></span>
                                    <span class="stat-label">Chats</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-box">
                                    <span class="stat-value"><?= $inst['contacts'] ?? 0 ?></span>
                                    <span class="stat-label">Contatos</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-box">
                                    <span class="stat-value text-primary"><?= $inst['messagesSent'] ?? 0 ?></span>
                                    <span class="stat-label">Envios</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-box">
                                    <span class="stat-value text-info"><?= $inst['messagesReceived'] ?? 0 ?></span>
                                    <span class="stat-label">Recs.</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="info-label">ID da Instância</div>
                                <div class="info-value"><code><?= $inst['instanceId'] ?></code></div>
                            </div>
                            <div class="col-6">
                                <div class="info-label">Pagamento</div>
                                <div class="info-value">
                                    <span class="badge <?= ($inst['paymentStatus'] ?? '') === 'PAID' ? 'bg-success' : 'bg-danger' ?>" style="font-size: 0.65rem;">
                                        <?= ($inst['paymentStatus'] ?? '') === 'PAID' ? 'PAGO' : 'PENDENTE' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="info-label">Expira em</div>
                                <div class="info-value"><?= isset($inst['expires']) ? date('d/m/Y', $inst['expires'] / 1000) : '-' ?></div>
                            </div>
                        </div>

                        <hr class="my-2 opacity-10">

                        <h6 class="fw-bold small text-uppercase mb-2 text-muted">Webhooks Configurados</h6>
                        <div class="bg-light p-2 rounded mb-3" style="font-size: 0.75rem;">
                            <div class="text-truncate mb-1" title="<?= $inst['webhookReceivedUrl'] ?? '' ?>">
                                <strong>Recebimento:</strong> <?= $inst['webhookReceivedUrl'] ?: '---' ?>
                            </div>
                            <div class="text-truncate" title="<?= $inst['webhookDeliveryUrl'] ?? '' ?>">
                                <strong>Delivery:</strong> <?= $inst['webhookDeliveryUrl'] ?: '---' ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="index.php?instance=<?= $inst['instanceId'] ?>" class="btn btn-success btn-sm py-2">
                                <i class="bi bi-speedometer2 me-1"></i> Acessar Mensagens
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3 text-center">
                        <span class="text-muted" style="font-size: 0.7rem;">
                            Token: <code><?= substr($inst['token'] ?? '', 0, 8) ?>...</code> | 
                            Criada em: <?= isset($inst['created']) ? date('d/m/Y', strtotime($inst['created'])) : '-' ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
