-- ===========================================================================
--  Ecossistema de E-mails FECAP — Dados iniciais (perfis, permissões e admin)
--  -------------------------------------------------------------------------
--  Importe DEPOIS de database/schema.sql.
--
--  >>> ATENCAO DE SEGURANCA <<<
--  O usuário administrador abaixo nasce com a senha TEMPORARIA:
--        admin@fecap.br  /  Admin@Fecap2026
--  ALTERE ESSA SENHA NO PRIMEIRO ACESSO (tela de Usuários > Editar).
-- ===========================================================================

USE `ecosystem_emails`;

-- ----- Perfis -----
INSERT INTO `roles` (`id`, `slug`, `nome`, `descricao`) VALUES
  (1, 'admin',   'Administrador', 'Acesso total ao sistema e gerenciamento de usuarios.'),
  (2, 'criador', 'Criador',       'Acesso apenas ao gerador de e-mails.'),
  (3, 'leitor',  'Leitor',        'Acesso apenas aos relatorios e consultas de e-mails.')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `descricao` = VALUES(`descricao`);

-- ----- Permissões -----
INSERT INTO `permissions` (`id`, `slug`, `descricao`) VALUES
  (1, 'gerador.acesso',    'Acessar e utilizar o gerador de e-mails.'),
  (2, 'relatorios.acesso', 'Acessar relatorios e consultas de e-mails do Dynamics.'),
  (3, 'usuarios.gerenciar','Criar, editar e remover usuarios e perfis.')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- ----- Vínculo perfil x permissão -----
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (1, 1), (1, 2), (1, 3),   -- admin: tudo
  (2, 1),                   -- criador: gerador
  (3, 2)                    -- leitor: relatorios
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- ----- Usuário administrador inicial -----
-- Hash bcrypt (cost 12) da senha temporaria "Admin@Fecap2026".
INSERT INTO `users` (`nome`, `email`, `senha_hash`, `role_id`, `ativo`) VALUES
  ('Administrador', 'admin@fecap.br',
   '$2y$12$annltvnAfnqb1ji4BTkzrOb1l3FQ9dS6Z4qzVNHMZgFK8y/QB8FnS',
   1, 1)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);
