# Webhook W-API (PHP + SQLite)

Este projeto implementa um Webhook para capturar conversas do WhatsApp via W-API e salvá-las em um banco de dados local SQLite.

## Estrutura de Arquivos

- `database.sql`: Estrutura da tabela.
- `database.db`: Arquivo do banco de dados (criado automaticamente).
- `config.php`: Configurações da API e Webhook.
- `webhook.php`: Script que processa os dados recebidos.
- `set_webhook.php`: Script para configurar a URL na W-API.

## Passo a Passo para Instalação

### 1. Configuração
- Abra o arquivo `config.php`.
- A constante `WEBHOOK_URL` deve conter a URL pública onde o arquivo `webhook.php` estará acessível.
- **Importante**: Certifique-se de que a pasta do projeto tenha permissão de escrita para que o PHP possa criar o arquivo `database.db`.

### 2. Ativar o Webhook
- Após publicar seu script online, execute o arquivo `set_webhook.php` para avisar a W-API onde enviar as mensagens:
  ```bash
  php set_webhook.php
  ```

## Vantagens do SQLite
- Não precisa de servidor de banco de dados (MySQL).
- O banco é um arquivo único dentro da pasta do projeto.
- Ideal para testes e pequenos volumes de mensagens.
