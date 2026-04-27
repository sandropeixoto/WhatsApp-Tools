<?php
require_once 'config.php';

// Captura o payload bruto
$payloadRaw = file_get_contents('php://input');
$data = json_decode($payloadRaw, true);

// Log para depuração
file_put_contents('webhook_log.json', $payloadRaw . PHP_EOL, FILE_APPEND);

if (!$data) {
    http_response_code(400);
    exit('Payload inválido');
}

// Verifica se é o evento de mensagem recebida
if (isset($data['event']) && $data['event'] === 'webhookReceived') {
    try {
        $db = getDB();
        
        $instanceId = $data['instanceId'] ?? WAPI_INSTANCE_ID;
        $messageId  = $data['messageId'];
        $fromMe     = $data['fromMe'] ? 1 : 0;
        $timestamp  = $data['moment'];
        
        // Dados do Remetente/Chat
        $phone    = $data['chat']['id'] ?? $data['sender']['id'];
        $pushName = $data['sender']['pushName'] ?? 'Desconhecido';
        
        // Processamento do Conteúdo
        $content = '';
        $type    = 'unknown';
        
        if (isset($data['msgContent'])) {
            $msg = $data['msgContent'];
            
            if (isset($msg['conversation'])) {
                $content = $msg['conversation'];
                $type = 'text';
            } elseif (isset($msg['extendedTextMessage'])) {
                $content = $msg['extendedTextMessage']['text'] ?? '';
                $type = 'text';
            } elseif (isset($msg['imageMessage'])) {
                $content = '[Imagem]';
                $type = 'image';
            } elseif (isset($msg['audioMessage'])) {
                $content = '[Áudio]';
                $type = 'audio';
            } elseif (isset($msg['videoMessage'])) {
                $content = '[Vídeo]';
                $type = 'video';
            } elseif (isset($msg['documentMessage'])) {
                $content = '[Documento]';
                $type = 'document';
            }
        }

        // Insere no banco de dados SQLite
        $stmt = $db->prepare("INSERT OR IGNORE INTO messages 
            (instance_id, message_id, phone, from_me, push_name, content, message_type, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $instanceId,
            $messageId,
            $phone,
            $fromMe,
            $pushName,
            $content,
            $type,
            $timestamp
        ]);
        
        echo "Mensagem gravada: " . $messageId;

    } catch (Exception $e) {
        file_put_contents('error_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo "Erro ao processar";
    }
} else {
    echo "Evento '" . ($data['event'] ?? 'desconhecido') . "' recebido e ignorado.";
}
