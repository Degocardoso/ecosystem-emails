# Arquitetura da Unificação

## Visão geral

Antes, existiam dois projetos independentes:

- **gerador-de-emails-master** — PHP "puro" (sem framework), com a URL do Power
  Automate e demais configurações escritas direto no código.
- **dynamics-email-report** — projeto com Composer (Guzzle, Monolog, phpdotenv,
  PhpSpreadsheet, TCPDF) que consulta a API do Dynamics 365.

Eles foram unificados em **um único repositório** com uma **camada de
autenticação compartilhada** colocada "à frente" dos dois módulos. Nenhum dos
dois sistemas foi reescrito: eles continuam funcionando como antes, mas agora
exigem login e respeitam o perfil do usuário.

```
┌──────────────────────────────────────────────────────────────┐
│                      NAVEGADOR DO USUÁRIO                       │
└───────────────┬───────────────────────────────┬───────────────┘
                │  cookie de sessão único (mesmo host/path)       │
        ┌───────▼────────┐                ┌───────▼───────────────┐
        │  Portal (raiz) │                │  Camada compartilhada  │
        │  login.php     │  usa  ──────►  │  auth/ (PHP puro)      │
        │  dashboard.php │                │  - env.php (.env)      │
        │  admin/*       │                │  - Database (PDO MySQL)│
        └───────┬────────┘                │  - Auth / User / Csrf  │
                │ require                  │  - guard.php (RBAC)    │
   ┌────────────┼────────────┐            └───────────┬───────────┘
   │            │            │                        │
┌──▼─────────┐  │   ┌────────▼─────────────┐          │ require guard
│ Gerador    │  │   │ Relatórios (Dynamics) │ ─────────┘
│ (criador)  │  │   │ (leitor)              │
└────────────┘  │   └───────────────────────┘
                ▼
        Banco MySQL (users, roles, permissions)
```

## Estrutura de pastas

```
ecosystem-emails/
├── index.php                 # entrada: redireciona p/ login ou painel
├── login.php  logout.php     # autenticação
├── dashboard.php             # painel inicial (cards por permissão)
├── admin/                    # gerenciamento de usuários (perfil admin)
│   ├── usuarios.php
│   ├── usuario-form.php
│   └── usuario-excluir.php
├── auth/                     # CAMADA COMPARTILHADA (PHP puro, sem Composer)
│   ├── env.php               # carrega o .env da raiz
│   ├── Database.php          # conexão PDO MySQL (singleton)
│   ├── User.php              # modelo de usuário + RBAC
│   ├── Auth.php              # login, sessão, força bruta, permissões
│   ├── Csrf.php              # proteção CSRF
│   ├── helpers.php           # e(), redirect(), base_url(), flash
│   ├── bootstrap.php         # inicia sessão segura + carrega tudo
│   └── guard.php             # require_login / require_permission / require_role
├── partials/                 # topo e rodapé do portal
├── assets/                   # CSS e logos FECAP/ASA
├── database/                 # schema.sql + seed.sql
├── scripts/                  # utilitários CLI (gerar-hash.php)
├── gerador-de-emails-master/ # MÓDULO 1 (e-mails) — protegido por guard
└── dynamics-email-report/    # MÓDULO 2 (relatórios) — protegido por guard
```

## Por que essa abordagem?

1. **Login único e sessão única.** Os três conjuntos de páginas rodam no mesmo
   host e compartilham o mesmo cookie de sessão (`SESSION_NAME` no `.env`).
   Ao logar no portal, o usuário já está autenticado nos dois módulos.

2. **Baixo risco de regressão.** O código interno do gerador e dos relatórios
   foi preservado. As mudanças foram cirúrgicas: incluir o `guard.php` no topo
   das páginas, mover segredos para o `.env` e ajustar cores.

3. **Camada de autenticação sem dependências.** As classes em `auth/` usam
   apenas recursos nativos do PHP (`PDO`, `password_hash`, `random_bytes`).
   Assim, o portal e o gerador funcionam **mesmo sem `composer install`**.
   O Composer só é necessário para o módulo de relatórios (Guzzle, TCPDF, etc.).

## Como o controle de acesso é aplicado em cada módulo

| Arquivo | Proteção adicionada |
|---------|---------------------|
| `gerador-de-emails-master/index.php` | `require_permission('gerador.acesso')` |
| `gerador-de-emails-master/visualizar.php` | `require_permission('gerador.acesso')` |
| `gerador-de-emails-master/core/geraEmail.php` | `require_permission('gerador.acesso')` + CSRF |
| `gerador-de-emails-master/core/enviarParaDynamics.php` | Auth + permissão + CSRF (responde JSON) |
| `dynamics-email-report/public/index.php` | `require_permission('relatorios.acesso')` + CSRF no POST |
| `dynamics-email-report/public/buscar-emails-ajax.php` | Auth + permissão + CSRF (responde JSON) |

Cada módulo inclui o guard por **caminho relativo**
(`require_once __DIR__ . '/../auth/guard.php'`), portanto a unificação não
depende de variável de ambiente para "encontrar" o login.

## Compartilhamento do `.env`

Existe **um único `.env` na raiz**. Ele é lido por:

- `auth/env.php` (parser próprio, sem dependências) — usado pelo portal e gerador;
- `vlucas/phpdotenv` dentro de `dynamics-email-report/src/Bootstrap.php`, que foi
  ajustado para apontar para a raiz (`dirname(dirname(__DIR__))`).

Ambos populam `$_ENV`/`getenv()` de forma "imutável" (não sobrescrevem valores já
definidos), então convivem sem conflito.
