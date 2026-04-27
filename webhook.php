<?php
require_once 'config.php';

$payloadRaw = file_get_contents('php://input');
$data = json_decode($payloadRaw, true);

file_put_contents('webhook_log.json', $payloadRaw . PHP_EOL, FILE_APPEND);

if (!$data) {
    http_response_code(400);
    exit('Payload inválido');
}

if (isset($data['event']) && $data['event'] === 'webhookReceived') {
    try {
        $db = getDB();
        
        $instanceId = $data['instanceId'] ?? WAPI_INSTANCE_ID;
        $messageId  = $data['messageId'];
        $fromMe     = $data['fromMe'] ? 1 : 0;
        $timestamp  = $data['moment'];
        $phone      = $data['chat']['id'] ?? $data['sender']['id'];
        $pushName   = $data['sender']['pushName'] ?? 'Desconhecido';
        
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
                // Armazena o JSON completo da mídia para download posterior
                $content = json_encode($msg['imageMessage']);
                $type = 'image';
            } elseif (isset($msg['videoMessage'])) {
                $content = json_encode($msg['videoMessage']);
                $type = 'video';
            } elseif (isset($msg['audioMessage'])) {
                $content = json_encode($msg['audioMessage']);
                $type = 'audio';
            } elseif (isset($msg['documentMessage'])) {
                $content = json_encode($msg['documentMessage']);
                $type = 'document';
            }
        }

        $stmt = $db->prepare("INSERT OR IGNORE INTO messages 
            (instance_id, message_id, phone, from_me, push_name, content, message_type, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([$instanceId, $messageId, $phone, $fromMe, $pushName, $content, $type, $timestamp]);
        
        echo "OK: " . $messageId;

    } catch (Exception $e) {
        file_put_contents('error_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
    }
}
