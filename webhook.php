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

// Aceita tanto mensagens recebidas quanto enviadas (Delivery)
$allowedEvents = ['webhookReceived', 'webhookDelivery'];

if (isset($data['event']) && in_array($data['event'], $allowedEvents)) {
    try {
        $db = getDB();
        
        $instanceId = $data['instanceId'] ?? WAPI_INSTANCE_ID;
        $messageId  = $data['messageId'];
        $fromMe     = (isset($data['fromMe']) && $data['fromMe']) ? 1 : 0;
        $timestamp  = $data['moment'] ?? time();
        
        // Dados do Remetente/Chat
        // No delivery, o 'chat' id é o destino. No received, é quem enviou.
        $phone    = $data['chat']['id'] ?? ($data['sender']['id'] ?? 'desconhecido');
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
            } elseif (isset($msg['documentWithCaptionMessage'])) {
                $content = json_encode($msg['documentWithCaptionMessage']['message']['documentMessage'] ?? []);
                $type = 'document';
            } elseif (isset($msg['imageWithCaptionMessage'])) {
                $content = json_encode($msg['imageWithCaptionMessage']['message']['imageMessage'] ?? []);
                $type = 'image';
            } elseif (isset($msg['videoWithCaptionMessage'])) {
                $content = json_encode($msg['videoWithCaptionMessage']['message']['videoMessage'] ?? []);
                $type = 'video';
            }
        }

        // Insere ou ignora se já existir
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
        
        echo "Evento " . $data['event'] . " processado: " . $messageId;

    } catch (Exception $e) {
        file_put_contents('error_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo "Erro ao processar";
    }
} else {
    echo "Evento '" . ($data['event'] ?? 'desconhecido') . "' recebido e ignorado.";
}
