# Segurança — Análise, Correções e Recomendações

Este documento descreve as vulnerabilidades encontradas nos dois projetos, o que
foi corrigido na unificação e o que ainda deve ser feito para manter os dados
protegidos.

---

## ⚠️ Ação urgente: rotacionar segredos expostos

A **URL do Power Automate** estava escrita diretamente no código-fonte do
gerador (`core/enviarParaDynamics.php`), **incluindo a assinatura de acesso
(`sig=...`)**. Essa URL foi distribuída em arquivo e deve ser considerada
**comprometida**. Removê-la do código **não a invalida**.

> **Gere uma nova URL do gatilho HTTP no Power Automate** (o que troca a
> assinatura `sig`) e coloque a nova em `POWER_AUTOMATE_URL` no `.env`.
> Pelo mesmo motivo, se o `CLIENT_SECRET` do Dynamics já tiver sido compartilhado
> em algum momento, **gere um novo segredo** no Azure AD.

---

## Vulnerabilidades encontradas e correções

### Gerador de E-mails

| # | Gravidade | Problema | Correção aplicada |
|---|-----------|----------|-------------------|
| 1 | 🔴 Crítica | URL do Power Automate (com assinatura) **hardcoded** no código | Movida para `.env` (`POWER_AUTOMATE_URL`); código lê via `env()` |
| 2 | 🟠 Alta | `CURLOPT_SSL_VERIFYPEER = false` (aceitava qualquer certificado → MITM) | Verificação TLS **habilitada** (`VERIFYPEER=true`, `VERIFYHOST=2`) |
| 3 | 🔴 Crítica | **Sem autenticação** — qualquer um gerava/enviava e-mails | Exige login + permissão `gerador.acesso` em todas as páginas/endpoints |
| 4 | 🟠 Alta | **Sem proteção CSRF** nos formulários e no envio | Token CSRF no formulário e no cabeçalho `X-CSRF-Token` do `fetch` |
| 5 | 🟡 Média | Caminhos absolutos (`/gerador-de-emails-master/...`) | Trocados por caminhos relativos (funciona em qualquer mount) |

> A função `slugify()` já neutralizava *path traversal* nos nomes de
> arquivo/pasta, e `visualizar.php` já restringia a leitura à pasta `emails/`
> via `realpath()`. Esses pontos foram mantidos.

### Relatórios (Dynamics)

| # | Gravidade | Problema | Correção aplicada |
|---|-----------|----------|-------------------|
| 1 | 🔴 Crítica | Endpoints de teste públicos (`teste-token.php` etc.) **imprimiam token de acesso** e credenciais | **Removidos** do projeto |
| 2 | 🟠 Alta | `display_errors = 1` fixo → vazava *stack traces* e caminhos | Controlado por `APP_DEBUG` no `.env` (desligado em produção) |
| 3 | 🟠 Alta | **Sem autenticação** — qualquer um consultava/exportava dados do Dynamics | Exige login + permissão `relatorios.acesso`; CSRF no POST |
| 4 | 🟡 Média | Log gravava **todo o corpo de POST/GET** (dados sensíveis) | Passou a registrar só método e URI |
| 5 | 🟡 Média | `logs/app.log`, `storage/sessions/*` e `storage/cache/*` **versionados** no Git | Removidos do versionamento + `.gitignore` criado |
| 6 | 🟡 Média | Ausência de `.gitignore` (risco de subir `.env`) | `.gitignore` cobre `.env`, `vendor/`, logs, sessões e cache |
| 7 | 🟢 Baixa | `verify_ssl` só ativo em `production` | Ativo por padrão; desligado apenas em `development` explícito |
| 8 | 🟢 Baixa | Arquivo de log criado com permissão `0666` (gravável por todos) | Reduzido para `0664` |

---

## Medidas de segurança implementadas (consolidado)

**Segredos e configuração**
- Todos os dados sensíveis em `.env` (fora do Git); `.env.example` versionado.
- `.htaccess` (raiz + diretórios internos) bloqueia acesso direto a `.env`,
  `.sql`, `.md`, `vendor/`, `src/`, `auth/`, etc.

**Autenticação e sessão**
- Senhas com **bcrypt (cost 12)**; `password_verify` + `password_needs_rehash`.
- Proteção contra **força bruta** (bloqueio por tentativas/tempo) e contra
  **enumeração de usuários** (verificação de senha em tempo constante).
- Sessão endurecida: `httponly`, `samesite=Lax`, `secure` sob HTTPS,
  `use_strict_mode`, regeneração de ID no login, expiração por inatividade.

**Controle de acesso (RBAC)**
- Perfis (admin/criador/leitor) e permissões no banco; verificação no servidor
  em **todas** as páginas e endpoints, não apenas na interface.

**Proteção da aplicação**
- **CSRF** em todos os formulários e endpoints AJAX (`hash_equals`).
- **Prepared statements** (PDO, `EMULATE_PREPARES=false`) contra SQL Injection.
- **Escape de saída** (`htmlspecialchars`) contra XSS.
- **TLS verificado** em todas as chamadas externas.
- Erros nunca exibidos em produção; apenas registrados em log (sem corpo de
  requisição).
- Cabeçalhos de segurança (`X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`).

---

## Recomendações para manter os dados protegidos

1. **Rotacionar os segredos expostos** (Power Automate e, se aplicável,
   `CLIENT_SECRET`) — ver o topo deste documento.
2. **Usar HTTPS** em produção e habilitar o HSTS.
3. **Usuário de banco dedicado** (sem privilégios administrativos) — nunca `root`.
4. **Restringir o phpMyAdmin** a rede interna/VPN e protegê-lo com senha forte.
5. **Backups** automáticos do banco e teste de restauração.
6. **Manter dependências atualizadas**: `composer update` periódico e
   acompanhamento de avisos de segurança (`composer audit`).
7. **Arquivos estáticos não passam pela autenticação PHP.** Os e-mails gerados
   em `gerador-de-emails-master/emails/` são HTML servidos diretamente pelo
   Apache/Nginx. Se o conteúdo for sensível, sirva-os por um proxy PHP
   autenticado, mova-os para fora da raiz web, ou proteja a pasta com
   autenticação no servidor.
8. **Política de senhas e (futuro) 2FA**: avaliar verificação em dois fatores
   para o perfil administrador.
9. **Revisar `login_attempts` periodicamente** para detectar tentativas de
   invasão; considerar alertas.
10. **Princípio do menor privilégio**: conceda a cada usuário o perfil mínimo
    necessário (a maioria deve ser *criador* ou *leitor*, não *admin*).
