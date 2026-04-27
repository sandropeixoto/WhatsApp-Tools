# Documentação Técnica: Integração W-API

Este documento detalha a implementação e o consumo da [W-API](https://w-api.app) utilizada neste projeto, servindo como guia para replicação em outras aplicações.

## 1. Configurações Base

A API utiliza uma URL base e autenticação via Bearer Token.

- **Base URL:** `https://api.w-api.app`
- **Autenticação:** Header `Authorization: Bearer {TOKEN}` ou Query Param `apiKey={TOKEN}` (apenas para listagem de instâncias).

## 2. Endpoints Utilizados

### 2.1. Gerenciamento de Instâncias

#### Listar Instâncias
Retorna todas as instâncias vinculadas à sua chave de API.
- **Método:** `GET`
- **Endpoint:** `/v1/client/list-instances`
- **Parâmetros:** `apiKey` (Query String)

#### Detalhes da Instância
Retorna o status atual, nome e configurações de uma instância específica.
- **Método:** `GET`
- **Endpoint:** `/v1/instance/fetch-instance`
- **Parâmetros:** `instanceId` (Query String)

---

### 2.2. Configuração de Webhooks

Para receber mensagens em tempo real, é necessário registrar as URLs de callback.

#### Registrar Webhook de Mensagens Recebidas
- **Método:** `PUT`
- **Endpoint:** `/v1/webhook/update-webhook-received`
- **Query Params:** `instanceId={ID}`
- **Body (JSON):**
  ```json
  {
    "value": "https://seu-dominio.com/webhook.php"
  }
  ```

#### Registrar Webhook de Mensagens Enviadas (Delivery)
- **Método:** `PUT`
- **Endpoint:** `/v1/webhook/update-webhook-delivery`
- **Query Params:** `instanceId={ID}`
- **Body (JSON):**
  ```json
  {
    "value": "https://seu-dominio.com/webhook.php"
  }
  ```

---

### 2.3. Mídia e Downloads

A W-API não envia o arquivo binário diretamente no webhook por questões de performance. Ela envia os metadados necessários para gerar um link temporário de download.

#### Gerar Link de Download
- **Método:** `POST`
- **Endpoint:** `/v1/message/download-media`
- **Query Params:** `instanceId={ID}`
- **Body (JSON):**
  ```json
  {
    "mediaKey": "string",
    "directPath": "string",
    "type": "image|video|audio|document",
    "mimetype": "string"
  }
  ```
- **Resposta:** Retorna um JSON contendo uma `url` temporária para o arquivo.

---

## 3. Estrutura do Webhook (Payload)

Quando o webhook é disparado, o payload recebido segue este padrão simplificado:

| Campo | Descrição |
| :--- | :--- |
| `event` | Tipo do evento (`webhookReceived` ou `webhookDelivery`) |
| `instanceId` | ID da instância que recebeu/enviou a mensagem |
| `messageId` | ID único da mensagem no WhatsApp |
| `fromMe` | Booleano que indica se a mensagem foi enviada por você |
| `moment` | Timestamp da mensagem |
| `chat.id` | ID do chat (número do telefone ou ID do grupo) |
| `sender.pushName` | Nome do contato (se disponível) |
| `msgContent` | Objeto contendo o corpo da mensagem (texto ou metadados de mídia) |

### Tipos de Conteúdo em `msgContent`:
- `conversation`: Texto simples.
- `extendedTextMessage`: Texto com links/formatação.
- `imageMessage` / `videoMessage` / `audioMessage` / `documentMessage`: Contém `mediaKey`, `directPath` e `mimetype`.

---

## 4. Fluxo de Implementação Sugerido

1. **Configuração Inicial:** Armazene o `Token` e o `InstanceID` em variáveis de ambiente ou arquivo de config.
2. **Setup:** Execute um script de "Set Webhook" (como o `set_webhook.php` deste projeto) para apontar a API para sua URL.
3. **Recepção:** Crie um endpoint (ex: `webhook.php`) que:
    - Capture o `php://input`.
    - Valide o evento.
    - Persista no banco de dados (SQLite/MySQL).
4. **Mídia:** Caso precise exibir imagens/vídeos, utilize os metadados salvos para chamar o endpoint de `download-media` sob demanda.

```php
// Exemplo rápido de chamada cURL para download
$ch = curl_init("https://api.w-api.app/v1/message/download-media?instanceId=" . $instanceId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mediaData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
$response = json_decode(curl_exec($ch), true);
$urlTemporaria = $response['url'];
```
