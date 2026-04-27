<?php
require_once 'config.php';

try {
    $db = getDB();

    // Estatísticas
    $totalMsgs = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $totalSent = $db->query("SELECT COUNT(*) FROM messages WHERE from_me = 1")->fetchColumn();
    $totalReceived = $db->query("SELECT COUNT(*) FROM messages WHERE from_me = 0")->fetchColumn();
    
    // Busca todas as mensagens (limitado às últimas 100 para performance)
    $stmt = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 100");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard W-API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-stat { transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .msg-content { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge-sent { background-color: #dcf8c6; color: #075e54; }
        .badge-received { background-color: #fff; border: 1px solid #dee2e6; color: #495057; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1">W-API Webhook Dashboard</span>
    </div>
</nav>

<div class="container">
    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-stat bg-white shadow-sm border-0 text-center p-3">
                <h6 class="text-muted">Total de Mensagens</h6>
                <h2 class="fw-bold text-success"><?= $totalMsgs ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-white shadow-sm border-0 text-center p-3">
                <h6 class="text-muted">Enviadas (Você)</h6>
                <h2 class="fw-bold text-primary"><?= $totalSent ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-white shadow-sm border-0 text-center p-3">
                <h6 class="text-muted">Recebidas</h6>
                <h2 class="fw-bold text-info"><?= $totalReceived ?></h2>
            </div>
        </div>
    </div>

    <!-- Tabela de Mensagens -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Últimas Mensagens</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Contato</th>
                            <th>Tipo</th>
                            <th>Conteúdo</th>
                            <th>Direção</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td class="small text-muted">
                                <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($msg['push_name']) ?></strong><br>
                                <span class="small text-muted"><?= htmlspecialchars($msg['phone']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary opacity-75 small text-uppercase">
                                    <?= $msg['message_type'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="msg-content" title="<?= htmlspecialchars($msg['content']) ?>">
                                    <?= htmlspecialchars($msg['content']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($msg['from_me']): ?>
                                    <span class="badge badge-sent">Enviada</span>
                                <?php else: ?>
                                    <span class="badge badge-received">Recebida</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Nenhuma mensagem encontrada no banco.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-4 text-muted small">
    W-API Webhook v1.0 - SQLite Database
</footer>

</body>
</html>
