<?php
require_once 'config.php';

$messageId = $_GET['id'] ?? '';

if (!$messageId) die('ID não fornecido');

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM messages WHERE message_id = ?");
    $stmt->execute([$messageId]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$msg || !in_array($msg['message_type'], ['image', 'video', 'audio', 'document'])) {
        die('Mídia não encontrada');
    }

    $mediaData = json_decode($msg['content'], true);
    
    // Usa o instance_id gravado na mensagem, ou o padrão se não houver
    $instanceId = $msg['instance_id'] ?? WAPI_INSTANCE_ID;

    // Prepara os dados para a W-API conforme Postman
    $postData = [
        'mediaKey'   => $mediaData['mediaKey'] ?? '',
        'directPath' => $mediaData['directPath'] ?? '',
        'type'       => $msg['message_type'],
        'mimetype'   => $mediaData['mimetype'] ?? ''
    ];

    // Chama a API para pegar o link temporário
    $url = WAPI_BASE_URL . "/v1/message/download-media?instanceId=" . $instanceId;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WAPI_TOKEN,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['url'])) {
        // Proxy: Baixa o conteúdo da URL temporária e serve diretamente
        $ctx = stream_context_create([
            "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
        ]);
        
        $fileContent = @file_get_contents($data['url'], false, $ctx);
        
        if ($fileContent !== false) {
            $mimetype = $mediaData['mimetype'] ?? 'application/octet-stream';
            header("Content-Type: " . $mimetype);
            // Se for documento, força o download ou nome do arquivo
            if ($msg['message_type'] === 'document') {
                $filename = $mediaData['fileName'] ?? $mediaData['title'] ?? 'arquivo';
                header("Content-Disposition: inline; filename=\"" . $filename . "\"");
            }
            echo $fileContent;
            exit;
        } else {
            die('Não foi possível baixar o conteúdo da mídia da URL gerada.');
        }
    } else {
        die('Erro ao obter link da mídia na API: ' . ($data['message'] ?? 'Erro desconhecido'));
    }

} catch (Exception $e) {
    die("Erro interno: " . $e->getMessage());
}
