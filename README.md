# Ecossistema de E-mails FECAP

Plataforma unificada que reúne, atrás de um **login único com controle de acesso
por perfil**, os dois sistemas de e-mail da FECAP:

| Módulo | Pasta | Para que serve | Perfis com acesso |
|--------|-------|----------------|-------------------|
| **Portal / Autenticação** | `/` , `auth/`, `admin/` | Login, painel inicial e gerenciamento de usuários | Todos (admin gerencia usuários) |
| **Gerador de E-mails** | `gerador-de-emails-master/` | Cria e-mails institucionais e dispara via Power Automate | Administrador, Criador |
| **Relatórios (Dynamics)** | `dynamics-email-report/` | Consulta engajamento e exporta relatórios do Dynamics 365 | Administrador, Leitor |

> A identidade visual foi atualizada para a paleta oficial da FECAP
> (verde escuro `#023c2c`, verde claro `#00e387`, verde médio `#60bf84`).
> O **layout dos e-mails gerados não foi alterado** — apenas as cores de marca.

## Perfis de acesso

- **Administrador** — acesso total ao sistema + gerenciamento de usuários.
- **Criador (Gerador)** — acesso apenas ao gerador de e-mails.
- **Leitor (Reports)** — acesso apenas aos relatórios e consultas.

## Começando rápido

```bash
# 1. Configure o ambiente
cp .env.example .env        # edite com seus dados (banco, Power Automate, Dynamics)

# 2. Crie o banco (MySQL/phpMyAdmin)
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql

# 3. Instale as dependências do módulo de relatórios
cd dynamics-email-report && composer install && cd ..

# 4. Aponte o servidor (Apache/Nginx) para a RAIZ do projeto e acesse /login.php
```

**Login inicial:** `admin@fecap.br` / `Admin@Fecap2026` — **altere no primeiro acesso.**

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| [docs/ARQUITETURA.md](docs/ARQUITETURA.md) | Como os sistemas foram unificados e como o código está organizado |
| [docs/INSTALACAO.md](docs/INSTALACAO.md) | Passo a passo de instalação (XAMPP e Linux), banco e `.env` |
| [docs/BANCO-DE-DADOS.md](docs/BANCO-DE-DADOS.md) | Estrutura das tabelas de usuários e permissões |
| [docs/AUTENTICACAO.md](docs/AUTENTICACAO.md) | Como funcionam a autenticação e o controle de acesso |
| [docs/DEPLOY.md](docs/DEPLOY.md) | Deploy e configuração de ambiente (Apache, Nginx, permissões) |
| [docs/SEGURANCA.md](docs/SEGURANCA.md) | Vulnerabilidades encontradas, correções aplicadas e recomendações |

## Requisitos

- PHP 7.4+ (recomendado 8.1+)
- MySQL 5.7+ / MariaDB 10.3+ (com phpMyAdmin)
- Composer (apenas para o módulo de relatórios)
- Extensões PHP: `pdo_mysql`, `curl`, `mbstring`, `json`, `openssl`
