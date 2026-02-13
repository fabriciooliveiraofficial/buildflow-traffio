# Buildflow Construction ERP - Infrastructure Setup (SSH)

Este repositório contém o ambiente de desenvolvimento local (Docker) e a automação de implantação para a Hostinger via SSH.

## 🚀 Como funciona a infraestrutura

1.  **Desenvolvimento Local**: Use o `docker-compose up -d` para rodar o sistema localmente em `http://localhost:3005`.
2.  **Versionamento**: GitHub atua como o repositório central.
3.  **Deploy Automático (SSH/Rsync)**: Cada `push` na branch `main` dispara um **GitHub Action** que sincroniza os arquivos com a sua hospedagem Hostinger usando SSH e Rsync. Isso é mais rápido e seguro que FTP.

## 🛠️ O que você precisa fazer (Passo a Passo)

### 1. Configurar o SSH na Hostinger
1.  Acesse o painel da Hostinger: **Avançado > Acesso SSH**.
2.  Clique em **Ativar** se estiver desativado.
3.  Anote o **IP do Servidor** e o **Usuário SSH** (ex: `u714643564`).

### 2. Gerar e Configurar Chaves SSH
1.  No seu computador, se não tiver uma chave, gere uma: `ssh-keygen -t ed25519`.
2.  Copie sua **chave pública** (`~/.ssh/id_ed25519.pub`) e cole no campo "Chaves SSH autorizadas" (ou similar) no painel da Hostinger.
3.  Teste a conexão: `ssh uXXXXX@IP-DO-SERVIDOR`.

### 3. Configurar as Secrets no GitHub
Vá em seu repositório no GitHub: **Settings > Secrets and Variables > Actions** e adicione:
- `SSH_PRIVATE_KEY`: O conteúdo do seu arquivo de **chave privada** (`~/.ssh/id_ed25519`).
- `REMOTE_HOST`: O **IP do Servidor** da Hostinger.
- `REMOTE_USER`: Seu **Usuário SSH**.

### 4. Configurar o PHP e Banco na Hostinger
1.  Certifique-se de que está usando **PHP 8.2+**.
2.  Crie o banco de dados e importe o `database/schema.sql`.
3.  **IMPORTANTE**: Crie manualmente o arquivo `.env` na pasta `public_html` da Hostinger com as credenciais de produção.

### 5. Primeiro Push
```bash
git add .
git commit -m "Configure SSH deployment with GitHub Actions"
git push -u origin main
```
