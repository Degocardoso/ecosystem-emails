# Instalação e Configuração

## Requisitos

- PHP 7.4+ (recomendado 8.1+) com extensões `pdo_mysql`, `curl`, `mbstring`,
  `json`, `openssl`
- MySQL 5.7+/MariaDB 10.3+ com phpMyAdmin
- Composer (apenas para o módulo de relatórios)

---

## 1. Obter o código

```bash
git clone https://github.com/Degocardoso/ecosystem-emails.git
cd ecosystem-emails
```

No **XAMPP/Windows**, coloque a pasta dentro de `C:\xampp\htdocs\` (ou aponte um
*virtual host* para a raiz — ver [DEPLOY.md](DEPLOY.md)).

---

## 2. Criar o banco de dados

Pelo phpMyAdmin (aba **Importar**) ou via CLI:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

Detalhes e criação de um usuário de banco dedicado em
[BANCO-DE-DADOS.md](BANCO-DE-DADOS.md).

---

## 3. Configurar o arquivo `.env`

```bash
cp .env.example .env
```

Edite o `.env` (ele **nunca** é versionado). Principais variáveis:

```ini
# Ambiente
APP_ENV=production          # use "development" só na sua máquina
APP_DEBUG=false             # NUNCA true em produção
APP_URL=http://localhost/ecosystem-emails   # ajuste ao seu endereço

# Banco (MySQL/phpMyAdmin)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ecosystem_emails
DB_USER=ecosystem_app
DB_PASS=sua-senha-do-banco

# Gerador de e-mails (Power Automate)
POWER_AUTOMATE_URL=         # cole aqui a URL do gatilho HTTP do fluxo

# Relatórios (Dynamics 365)
TENANT_ID=
CLIENT_ID=
CLIENT_SECRET=
RESOURCE=https://suaorg.crm.dynamics.com
```

> **`APP_URL` precisa bater com o endereço real de acesso.** Os redirecionamentos
> (login, painel, logout) usam essa base. Ex.: se acessa em
> `http://localhost/ecosystem-emails`, mantenha esse valor; se usar um vhost na
> raiz, use `http://seu-dominio`.

A explicação completa de cada variável está comentada no próprio
[`.env.example`](../.env.example).

---

## 4. Instalar dependências do módulo de relatórios

```bash
cd dynamics-email-report
composer install
cd ..
```

> O portal e o gerador **não** precisam de Composer. Apenas o módulo de
> relatórios usa bibliotecas (Guzzle, Monolog, PhpSpreadsheet, TCPDF, phpdotenv).

---

## 5. Permissões de escrita (Linux)

O módulo de relatórios grava logs, cache e sessões:

```bash
chmod -R 775 dynamics-email-report/logs dynamics-email-report/storage
# e dê a posse ao usuário do servidor web, ex.:
chown -R www-data:www-data dynamics-email-report/logs dynamics-email-report/storage
```

No XAMPP/Windows isso normalmente não é necessário.

---

## 6. Acessar

Abra `APP_URL` no navegador. Você será redirecionado para a tela de login.

**Login inicial:** `admin@fecap.br` / `Admin@Fecap2026`
→ **troque a senha imediatamente** em *Gerenciar Usuários*.

---

## Resolução de problemas

| Sintoma | Causa provável |
|--------|-----------------|
| "Erro de conexão com o banco" | `DB_*` incorretos no `.env` ou MySQL parado |
| Loop/redireciona sempre p/ login | `APP_URL` não corresponde ao endereço real |
| Relatórios mostram erro de classe não encontrada | faltou `composer install` |
| "A URL do Power Automate não foi configurada" | `POWER_AUTOMATE_URL` vazio no `.env` |
| Página em branco com `APP_DEBUG=false` | veja `dynamics-email-report/logs/app.log` |
