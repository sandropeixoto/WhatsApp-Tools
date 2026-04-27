<?php
// Configurações do Banco de Dados (SQLite)
define('DB_FILE', __DIR__ . '/database.db');

// Configurações da W-API
define('WAPI_INSTANCE_ID', 'LITE-4DSULO-NZ4WIB');
define('WAPI_TOKEN', 'NSaqg8w82RnoEEcFQD9mfEAoxhZR7WI3L');
define('WAPI_BASE_URL', 'https://api.w-api.app');

// URL do seu webhook (Altere após publicar online)
define('WEBHOOK_URL', 'https://sspeixoto.com.br/wapi/webhook.php');

/**
 * Busca a lista de instâncias da W-API
 */
function listInstances()
{
    $ch = curl_init(WAPI_BASE_URL . '/v1/client/list-instances?instanceId=' . WAPI_INSTANCE_ID);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WAPI_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['instances'] ?? [];
    }
    
    return [];
}

/**
 * Busca dados detalhados de uma instância específica
 */
function fetchInstanceDetails($instanceId)
{
    $ch = curl_init(WAPI_BASE_URL . '/v1/instance/fetch-instance?instanceId=' . $instanceId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WAPI_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

/**
 * Função para conectar ao banco de dados SQLite
 */
function getDB()
{
    try {
        $db = new PDO("sqlite:" . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria a tabela automaticamente se não existir
        $sql = file_get_contents(__DIR__ . '/database.sql');
        $db->exec($sql);

        return $db;
    } catch (PDOException $e) {
        die("Erro na conexão SQLite: " . $e->getMessage());
    }
}
