<?php
require_once 'config.php';

// Captura o payload bruto
$payloadRaw = file_get_contents('php://input');
$data = json_decode($payloadRaw, true);

// Log para depuração (opcional - remova em produção se houver muito volume)
file_put_contents('webhook_log.json', $payloadRaw . PHP_EOL, FILE_APPEND);

if (!$data) {
    http_response_code(400);
    exit('Payload inválido');
}

/**
 * A estrutura exata pode variar dependendo da versão da API,
 * mas geralmente segue o padrão abaixo para mensagens recebidas.
 */

try {
    $db = getDB();
    
    // Supondo que o evento seja uma mensagem recebida
    // Ajuste estas chaves conforme o log gerado no webhook_log.json
    
    $instanceId = $data['instanceId'] ?? WAPI_INSTANCE_ID;
    
    // Muitas APIs do tipo Baileys enviam no formato data -> message
    $msgData = $data['data'] ?? $data;
    
    if (isset($msgData['key'])) {
        $messageId = $msgData['key']['id'];
        $remoteJid = $msgData['key']['remoteJid'];
        $fromMe = $msgData['key']['fromMe'] ? 1 : 0;
        $pushName = $msgData['pushName'] ?? 'Desconhecido';
        $timestamp = $msgData['messageTimestamp'] ?? time();
        
        $content = '';
        $type = 'unknown';
        
        if (isset($msgData['message'])) {
            $m = $msgData['message'];
            if (isset($m['conversation'])) {
                $content = $m['conversation'];
                $type = 'text';
            } elseif (isset($m['extendedTextMessage'])) {
                $content = $m['extendedTextMessage']['text'];
                $type = 'text';
            } elseif (isset($m['imageMessage'])) {
                $content = '[Imagem]';
                $type = 'image';
            } elseif (isset($m['audioMessage'])) {
                $content = '[Áudio]';
                $type = 'audio';
            } elseif (isset($m['videoMessage'])) {
                $content = '[Vídeo]';
                $type = 'video';
            }
            // Adicione outros tipos conforme necessário
        }

        // Insere no banco de dados (Sintaxe SQLite)
        $stmt = $db->prepare("INSERT OR IGNORE INTO messages 
            (instance_id, message_id, phone, from_me, push_name, content, message_type, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $instanceId,
            $messageId,
            $remoteJid,
            $fromMe,
            $pushName,
            $content,
            $type,
            $timestamp
        ]);
        
        echo "Mensagem processada com sucesso";
    } else {
        echo "Evento ignorado (não é uma mensagem)";
    }

} catch (Exception $e) {
    file_put_contents('error_log.txt', $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo "Erro interno";
}
