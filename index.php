<?php
require_once 'config.php';

try {
    $db = getDB();
    $totalMsgs = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $totalSent = $db->query("SELECT COUNT(*) FROM messages WHERE from_me = 1")->fetchColumn();
    $totalReceived = $db->query("SELECT COUNT(*) FROM messages WHERE from_me = 0")->fetchColumn();
    
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
        .msg-content { max-width: 400px; white-space: normal; }
        .thumb-media { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .badge-sent { background-color: #dcf8c6; color: #075e54; }
        .badge-received { background-color: #fff; border: 1px solid #dee2e6; color: #495057; }
        .video-thumb { position: relative; display: inline-block; }
        .video-thumb::after { content: '▶'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; background: rgba(0,0,0,0.5); width: 30px; height: 30px; border-radius: 50%; text-align: center; line-height: 30px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand mb-0 h1">W-API Dashboard</span>
        <div class="navbar-nav ms-auto">
            <a class="nav-link btn btn-outline-light btn-sm px-3" href="diagnostico.php">Diagnóstico</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-stat bg-white shadow-sm border-0 text-center p-3">
                <h6 class="text-muted small text-uppercase fw-bold">Total</h6>
                <h2 class="fw-bold text-success"><?= $totalMsgs ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-white shadow-sm border-0 text-center p-3">
                <h6 class="text-muted small text-uppercase fw-bold">Enviadas</h6>
                <h2 class="fw-bold text-primary"><?= $totalSent ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-white shadow-sm border-0 text-center p-3">
                <h6 class="text-muted small text-uppercase fw-bold">Recebidas</h6>
                <h2 class="fw-bold text-info"><?= $totalReceived ?></h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Data</th>
                            <th>Contato</th>
                            <th>Conteúdo</th>
                            <th>Tipo</th>
                            <th class="pe-3 text-end">Direção</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td class="ps-3 small text-muted">
                                <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($msg['push_name']) ?></strong><br>
                                <span class="small text-muted"><?= htmlspecialchars($msg['phone']) ?></span>
                            </td>
                            <td class="msg-content">
                                <?php if ($msg['message_type'] === 'text'): ?>
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                <?php elseif ($msg['message_type'] === 'image'): ?>
                                    <img src="get_media.php?id=<?= $msg['message_id'] ?>" 
                                         class="thumb-media" 
                                         onclick="showMedia('image', this.src)"
                                         alt="Thumbnail">
                                <?php elseif ($msg['message_type'] === 'video'): ?>
                                    <div class="video-thumb" onclick="showMedia('video', 'get_media.php?id=<?= $msg['message_id'] ?>')">
                                        <div class="thumb-media bg-dark d-flex align-items-center justify-content-center text-white small">Video</div>
                                    </div>
                                <?php elseif ($msg['message_type'] === 'audio'): ?>
                                    <audio controls class="w-100" style="max-width: 250px;">
                                        <source src="get_media.php?id=<?= $msg['message_id'] ?>" type="audio/mpeg">
                                    </audio>
                                <?php else: ?>
                                    <a href="get_media.php?id=<?= $msg['message_id'] ?>" target="_blank" class="btn btn-sm btn-light">
                                        📎 Baixar Arquivo
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary opacity-75 small text-uppercase"><?= $msg['message_type'] ?></span>
                            </td>
                            <td class="pe-3 text-end">
                                <?php if ($msg['from_me']): ?>
                                    <span class="badge badge-sent">Enviada</span>
                                <?php else: ?>
                                    <span class="badge badge-received">Recebida</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Mídia -->
<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div id="mediaContainer"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
    const container = document.getElementById('mediaContainer');

    function showMedia(type, url) {
        container.innerHTML = '';
        if (type === 'image') {
            container.innerHTML = `<img src="${url}" class="img-fluid rounded shadow">`;
        } else if (type === 'video') {
            container.innerHTML = `<video src="${url}" controls autoplay class="img-fluid rounded shadow"></video>`;
        }
        modal.show();
    }
</script>

</body>
</html>
