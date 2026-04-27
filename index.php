<?php
require_once 'config.php';

try {
    $db = getDB();
    $instances = listInstances();
    $selectedInstance = $_GET['instance'] ?? WAPI_INSTANCE_ID;
    $selectedChat = $_GET['chat'] ?? null;

    // 1. Busca a lista de conversas únicas (agrupadas por telefone/grupo)
    // Mostra quem enviou a última mensagem e o timestamp
    $stmtChats = $db->prepare("
        SELECT m1.* 
        FROM messages m1
        JOIN (
            SELECT phone, MAX(created_at) as last_date 
            FROM messages 
            WHERE instance_id = ? 
            GROUP BY phone
        ) m2 ON m1.phone = m2.phone AND m1.created_at = m2.last_date
        WHERE m1.instance_id = ?
        ORDER BY m1.created_at DESC
    ");
    $stmtChats->execute([$selectedInstance, $selectedInstance]);
    $chatList = $stmtChats->fetchAll(PDO::FETCH_ASSOC);

    // 2. Se houver um chat selecionado, busca o histórico de mensagens
    $messages = [];
    if ($selectedChat) {
        $stmtMsgs = $db->prepare("SELECT * FROM messages WHERE instance_id = ? AND phone = ? ORDER BY created_at ASC LIMIT 200");
        $stmtMsgs->execute([$selectedInstance, $selectedChat]);
        $messages = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Dashboard - W-API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body, html { height: 100%; overflow: hidden; background-color: #f0f2f5; }
        .main-wrapper { height: calc(100vh - 60px); display: flex; }
        
        /* Lista de Conversas */
        .chat-list { width: 350px; background: white; border-right: 1px solid #ddd; overflow-y: auto; }
        .chat-item { padding: 12px 15px; border-bottom: 1px solid #f0f2f5; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; text-decoration: none; color: inherit; }
        .chat-item:hover { background: #f5f6f6; }
        .chat-item.active { background: #ebebeb; }
        .chat-avatar { width: 45px; height: 45px; border-radius: 50%; background: #dfe5e7; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 1.2rem; color: #54656f; }
        .chat-info { flex: 1; min-width: 0; }
        .chat-name { font-weight: 500; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-truncate: ellipsis; display: block; }
        .chat-last-msg { font-size: 0.85rem; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-time { font-size: 0.75rem; color: #667781; margin-left: 5px; }

        /* Janela de Mensagens */
        .chat-window { flex: 1; display: flex; flex-direction: column; background: #efeae2 url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-blend-mode: overlay; }
        .chat-header { padding: 10px 20px; background: #f0f2f5; border-bottom: 1px solid #ddd; display: flex; align-items: center; }
        .message-area { flex: 1; overflow-y: auto; padding: 20px 5%; display: flex; flex-direction: column; }
        
        /* Balões de Mensagem */
        .msg-bubble { max-width: 65%; padding: 8px 12px; border-radius: 8px; margin-bottom: 10px; position: relative; font-size: 0.95rem; box-shadow: 0 1px 0.5px rgba(0,0,0,0.13); }
        .msg-sent { align-self: flex-end; background-color: #dcf8c6; border-top-right-radius: 0; }
        .msg-received { align-self: flex-start; background-color: #ffffff; border-top-left-radius: 0; }
        .msg-time { font-size: 0.7rem; color: #667781; margin-top: 4px; text-align: right; }
        .msg-sender { font-size: 0.75rem; font-weight: bold; color: #e65100; margin-bottom: 3px; display: block; }

        .thumb-media { max-width: 100%; border-radius: 4px; cursor: pointer; margin-top: 5px; }
        .navbar-brand { font-weight: 800; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm" style="height: 60px;">
    <div class="container-fluid px-4">
        <a class="navbar-brand mb-0" href="index.php">
            <i class="bi bi-whatsapp me-2"></i>W-API Dashboard
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">Conversas</a></li>
                <li class="nav-item"><a class="nav-link" href="instancias.php">Instâncias</a></li>
            </ul>
            <form class="d-flex align-items-center" method="GET">
                <label class="text-white me-2 small fw-bold text-uppercase">Instância:</label>
                <select name="instance" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                    <?php foreach ($instances as $inst): ?>
                        <option value="<?= $inst['instanceId'] ?>" <?= $selectedInstance == $inst['instanceId'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inst['instanceName'] ?? $inst['instanceId']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</nav>

<div class="main-wrapper">
    <!-- Lista de Conversas -->
    <div class="chat-list">
        <?php foreach ($chatList as $chat): ?>
            <?php 
                $isGroup = strpos($chat['phone'], '@g.us') !== false || strpos($chat['phone'], '-') !== false;
                $activeClass = ($selectedChat === $chat['phone']) ? 'active' : '';
            ?>
            <a href="?instance=<?= $selectedInstance ?>&chat=<?= urlencode($chat['phone']) ?>" class="chat-item <?= $activeClass ?>">
                <div class="chat-avatar">
                    <i class="bi <?= $isGroup ? 'bi-people-fill' : 'bi-person-fill' ?>"></i>
                </div>
                <div class="chat-info">
                    <div class="d-flex justify-content-between">
                        <span class="chat-name"><?= htmlspecialchars($chat['push_name'] ?: $chat['phone']) ?></span>
                        <span class="chat-time"><?= date('H:i', $chat['timestamp']) ?></span>
                    </div>
                    <div class="chat-last-msg">
                        <?php 
                            if ($chat['from_me']) echo '<i class="bi bi-check2-all text-primary me-1"></i>';
                            if ($chat['message_type'] === 'text') echo htmlspecialchars($chat['content']);
                            else echo '📎 ' . ucfirst($chat['message_type']);
                        ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (empty($chatList)): ?>
            <div class="p-4 text-center text-muted small">Nenhuma conversa encontrada nesta instância.</div>
        <?php endif; ?>
    </div>

    <!-- Janela de Mensagens -->
    <div class="chat-window">
        <?php if ($selectedChat): ?>
            <div class="chat-header">
                <div class="chat-avatar" style="width: 35px; height: 35px; font-size: 1rem;">
                    <i class="bi <?= (strpos($selectedChat, '@g.us') !== false) ? 'bi-people-fill' : 'bi-person-fill' ?>"></i>
                </div>
                <div class="ms-2">
                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($selectedChat) ?></h6>
                    <small class="text-muted">Histórico de mensagens</small>
                </div>
            </div>

            <div class="message-area" id="messageArea">
                <?php foreach ($messages as $msg): ?>
                    <?php 
                        $isMe = $msg['from_me'];
                        $contentData = json_decode($msg['content'], true);
                    ?>
                    <div class="msg-bubble <?= $isMe ? 'msg-sent' : 'msg-received' ?>">
                        <?php if (!$isMe && strpos($msg['phone'], '@g.us') !== false): ?>
                            <span class="msg-sender"><?= htmlspecialchars($msg['push_name']) ?></span>
                        <?php endif; ?>

                        <?php if ($msg['message_type'] === 'text'): ?>
                            <?= nl2br(htmlspecialchars($msg['content'])) ?>
                        <?php elseif ($msg['message_type'] === 'image'): ?>
                            <?php 
                                $thumb = $contentData['jpegThumbnail'] ?? '';
                                $imgSrc = !empty($thumb) ? 'data:image/jpeg;base64,' . $thumb : 'get_media.php?id=' . $msg['message_id'];
                            ?>
                            <img src="<?= $imgSrc ?>" class="thumb-media" onclick="showMedia('image', 'get_media.php?id=<?= $msg['message_id'] ?>')">
                            <?php if (!empty($contentData['caption'])): ?>
                                <div class="mt-2"><?= nl2br(htmlspecialchars($contentData['caption'])) ?></div>
                            <?php endif; ?>
                        <?php elseif ($msg['message_type'] === 'video'): ?>
                            <div class="position-relative d-inline-block" onclick="showMedia('video', 'get_media.php?id=<?= $msg['message_id'] ?>')">
                                <div class="bg-dark rounded d-flex align-items-center justify-content-center text-white" style="width: 200px; height: 120px;">
                                    <i class="bi bi-play-circle-fill h1"></i>
                                </div>
                            </div>
                        <?php elseif ($msg['message_type'] === 'audio'): ?>
                            <audio controls class="w-100" style="min-width: 200px;">
                                <source src="get_media.php?id=<?= $msg['message_id'] ?>" type="audio/ogg">
                                <source src="get_media.php?id=<?= $msg['message_id'] ?>" type="audio/mpeg">
                            </audio>
                        <?php elseif ($msg['message_type'] === 'document'): ?>
                            <a href="get_media.php?id=<?= $msg['message_id'] ?>" target="_blank" class="btn btn-light btn-sm border d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf-fill text-danger me-2 h4 mb-0"></i>
                                <div class="text-start">
                                    <div class="small fw-bold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($contentData['fileName'] ?? 'Documento') ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Download</div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <div class="msg-time"><?= date('H:i', $msg['timestamp']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                <i class="bi bi-chat-left-text-fill display-1 opacity-25 mb-4"></i>
                <h5>Selecione uma conversa para começar</h5>
                <p class="small">Seus chats serão exibidos aqui.</p>
            </div>
        <?php endif; ?>
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
    const msgArea = document.getElementById('messageArea');

    // Auto-scroll para o fim da conversa
    if (msgArea) msgArea.scrollTop = msgArea.scrollHeight;

    function showMedia(type, url) {
        container.innerHTML = '';
        if (type === 'image') {
            container.innerHTML = `<img src="${url}" class="img-fluid rounded shadow">`;
        } else if (type === 'video') {
            container.innerHTML = `<video src="${url}" controls autoplay class="img-fluid rounded shadow w-100"></video>`;
        }
        modal.show();
    }
</script>

</body>
</html>
