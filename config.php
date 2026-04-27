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
