<?php
// --- SEGURANÇA: somente usuários com permissão de gerador (criador/admin) ---
require_once __DIR__ . '/../auth/guard.php';
require_permission('gerador.acesso');
use App\Auth\Csrf;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Emails v3.0</title>

    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/tinymce/tinymce.min.js"></script>

    <style>
        :root {
            --cor-principal: #023c2c; --cor-principal-hover: #035a41;
            --cor-fundo: #f4f7f6; --cor-texto: #333;
            --cor-amarelo: #00e387; --cor-roxo: #60bf84;
        }
        body { background-color: var(--cor-fundo); color: var(--cor-texto); }
        .header-gradient { background: linear-gradient(90deg, var(--cor-principal), #00e387); color: white; padding: 2.5rem 2rem; border-radius: 0.5rem; margin-bottom: 2rem; }
        .section-card { background-color: white; border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .section-title { color: var(--cor-principal); font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; }
        .section-title i { font-size: 1.2rem; margin-right: 0.75rem; }
        .form-control:focus { border-color: var(--cor-principal); box-shadow: 0 0 0 0.25rem rgba(0, 227, 135, 0.25); }
        .botao-dinamico-item, .tabela-dinamica-item, .imagem-dinamica-item { border: 1px solid #ddd; border-radius: 0.25rem; padding: 1rem; margin-top: 1rem; background-color: #fafafa; position: relative; }
        .btn-remover-bloco { position: absolute; top: 0.5rem; right: 0.5rem; }
        .btn-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn-gerar, .btn-ver-todos { font-weight: bold; padding: 0.75rem 1.5rem; font-size: 1.1rem; border-radius: 0.5rem; border: none; transition: all 0.2s ease-in-out; flex-grow: 1; }
        .btn-gerar { background-color: var(--cor-amarelo); color: var(--cor-texto); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-gerar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        .btn-ver-todos { background-color: transparent; color: white; border: 2px solid white; }
        .btn-ver-todos:hover { background-color: white; color: var(--cor-principal); }
        .colunas-container, .linha-dados-container { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;}
        .coluna-item, .dado-item { flex: 1; min-width: 0; }
    </style>
</head>
<body>
    <nav style="background:#023c2c;color:#fff;padding:.6rem 1.25rem;display:flex;justify-content:space-between;align-items:center">
        <a href="../dashboard.php" style="color:#fff;text-decoration:none;font-weight:600">&larr; Painel</a>
        <a href="../logout.php" style="color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.5);padding:.3rem .8rem;border-radius:.4rem">Sair</a>
    </nav>
    <div class="container my-5">
        <form id="criaemail" method="post" action="core/geraEmail.php">
            <?= Csrf::field() ?>

            <header class="header-gradient">
                <h1 class="display-6">Gerador de Emails v3.0</h1>
                <p class="lead mb-4">Crie e configure seus e-mails de forma rápida e profissional.</p>
                <div class="btn-actions">
                    <button type="submit" class="btn btn-gerar"><i class="bi bi-envelope-check-fill"></i> Gerar Email Agora!</button>
                    <a href="emails/" target="_blank" class="btn btn-ver-todos"><i class="bi bi-folder2-open"></i> Ver Todos os E-mails</a>
                </div>
            </header>

            <div class="section-card modelo-selector">
                <h3 class="section-title"><i class="bi bi-journal-richtext"></i>Qual o modelo de e-mail?</h3>
                <div class="d-flex gap-4">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipoModelo" id="tipoComum" value="comum" checked>
                        <label class="form-check-label" for="tipoComum">Email Comum (Texto, botões e tabelas)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipoModelo" id="tipoSucessoNews" value="sucessoNews">
                        <label class="form-check-label" for="tipoSucessoNews">Sucesso News (Apenas imagens)</label>
                    </div>
                </div>
            </div>

            <div id="container-modelo-comum">
                <div class="row">
                    <div class="col-lg-7 d-flex">
                        <div class="section-card flex-grow-1 d-flex flex-column">
                            <h3 class="section-title"><i class="bi bi-pencil-square"></i>Conteúdo Principal</h3>
                            <div class="flex-grow-1"><textarea name="conteudoEmail"></textarea></div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-file-earmark-text"></i>Informações Gerais (Comum)</h3>
                            <div class="mb-3"><label class="form-label">Nome do arquivo</label><input type="text" name="nomeEmail" class="form-control" placeholder="Ex: promocao-setembro"></div>
                            <div class="mb-3"><label class="form-label">Pasta de destino</label><input type="text" name="diretorioEmail" class="form-control" placeholder="Ex: campanhas-internas"></div>
                        </div>
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-card-image"></i>Cabeçalho e Rodapé</h3>
                            <div class="mb-3"><label class="form-label">URL da imagem do Topo</label><input type="text" name="topoEmail" class="form-control" placeholder="https://exemplo.com/topo.jpg"></div>
                            <div class="mb-3"><label class="form-label">Altura do Topo (px)</label><input type="number" name="alturaTopoEmail" class="form-control" value="150"></div>
                            <hr class="my-4">
                            <div class="mb-3"><label class="form-label">URL da imagem do Rodapé</label><input type="text" name="rodapeEmail" class="form-control" placeholder="https://exemplo.com/rodape.png"></div>
                            <div class="mb-3"><label class="form-label">Altura do Rodapé (px)</label><input type="number" name="alturaRodapeEmail" class="form-control" value="130"></div>
                        </div>
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-hand-index-thumb"></i>Botões Personalizados</h3>
                            <div id="botoes-container"></div>
                            <button type="button" id="add-botao-btn" class="btn btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Adicionar Botão</button>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-table"></i>Construtor de Tabelas</h3>
                            <div id="tabelas-container"></div>
                            <button type="button" id="add-tabela-btn" class="btn btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Adicionar Nova Tabela</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="container-modelo-sucesso-news" style="display: none;">
                <div class="section-card">
                    <h3 class="section-title"><i class="bi bi-file-earmark-text"></i>Informações Gerais (Sucesso News)</h3>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Nome do arquivo</label><input type="text" name="sn_nomeEmail" class="form-control" placeholder="Ex: sucesso-news-edicao-12"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Pasta de destino</label><input type="text" name="sn_diretorioEmail" class="form-control" placeholder="Ex: sucesso-news"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Edição | Mês | Ano</label><input type="text" name="sn_edicao" class="form-control" placeholder="Edição 12 | Setembro | 2025"></div>
                    </div>
                </div>
                <div class="section-card">
                    <h3 class="section-title"><i class="bi bi-images"></i>Sequência de Imagens</h3>
                    <div id="imagens-container"></div>
                    <button type="button" id="add-imagem-btn" class="btn btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Adicionar Imagem</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // SEU CÓDIGO JAVASCRIPT ORIGINAL E COMPLETO
        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: 'textarea',
                height: '698px',
                resize: false,
                plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste code textcolor',
                toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright justify | bullist numlist outdent indent | link image | forecolor backcolor',
                content_css: 'css/bootstrap.min.css'
            });

            const modeloComum = document.getElementById('container-modelo-comum');
            const modeloSucessoNews = document.getElementById('container-modelo-sucesso-news');
            document.querySelectorAll('input[name="tipoModelo"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'comum') {
                        modeloComum.style.display = 'block';
                        modeloSucessoNews.style.display = 'none';
                    } else {
                        modeloComum.style.display = 'none';
                        modeloSucessoNews.style.display = 'block';
                    }
                });
            });

            const botoesContainer = document.getElementById('botoes-container');
            const addBotaoBtn = document.getElementById('add-botao-btn');
            let botaoIndex = 0;
            addBotaoBtn.addEventListener('click', function() {
                botaoIndex++;
                const newButtonDiv = document.createElement('div');
                newButtonDiv.classList.add('botao-dinamico-item');
                newButtonDiv.innerHTML = `<button type="button" class="btn btn-danger btn-sm p-1 lh-1 btn-remover-bloco">X</button><h5>Botão ${botaoIndex}</h5><p class="form-text mb-2">Use o placeholder <strong>{{botao${botaoIndex}}}</strong> no editor.</p><div class="mb-2"><label class="form-label">Texto do Botão</label><input type="text" name="botoes[${botaoIndex-1}][texto]" class="form-control" placeholder="Ex: Saiba Mais"></div><div><label class="form-label">Link de destino</label><input type="text" name="botoes[${botaoIndex-1}][link]" class="form-control" placeholder="https://exemplo.com"></div>`;
                botoesContainer.appendChild(newButtonDiv);
            });
            botoesContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remover-bloco')) {
                    e.target.closest('.botao-dinamico-item').remove();
                }
            });

            const tabelasContainer = document.getElementById('tabelas-container');
            const addTabelaBtn = document.getElementById('add-tabela-btn');
            let tabelaIndex = 0;
            addTabelaBtn.addEventListener('click', function() {
                tabelaIndex++;
                const newTabelaDiv = document.createElement('div');
                newTabelaDiv.classList.add('tabela-dinamica-item');
                newTabelaDiv.dataset.tabelaId = tabelaIndex;
                newTabelaDiv.innerHTML = `<button type="button" class="btn btn-danger btn-sm p-1 lh-1 btn-remover-bloco">X</button><h5>Tabela ${tabelaIndex}</h5><p class="form-text mb-2">Use o placeholder <strong>{{tabela${tabelaIndex}}}</strong> no editor.</p><div class="mb-3"><label class="form-label">Título Principal (Opcional)</label><input type="text" name="tabelas[${tabelaIndex-1}][titulo]" class="form-control" placeholder="Ex: Empresas Confirmadas"></div><hr><h6><strong>Defina os Títulos das Colunas</strong></h6><div class="colunas-container"></div><button type="button" class="btn btn-sm btn-secondary add-coluna-btn mt-1">Adicionar Coluna</button><hr><h6><strong>Preencha as Linhas de Dados</strong></h6><div class="linhas-container"></div><button type="button" class="btn btn-sm btn-secondary add-linha-btn mt-1">Adicionar Linha</button>`;
                tabelasContainer.appendChild(newTabelaDiv);
            });
            tabelasContainer.addEventListener('click', function(e) { const tabelaItem = e.target.closest('.tabela-dinamica-item'); if (!tabelaItem) return; const tabelaId = tabelaItem.dataset.tabelaId; const index = tabelaId - 1; if (e.target.classList.contains('btn-remover-bloco')) { tabelaItem.remove(); } if (e.target.classList.contains('add-coluna-btn')) { const colunasContainer = tabelaItem.querySelector('.colunas-container'); const colIndex = colunasContainer.children.length; const newColunaDiv = document.createElement('div'); newColunaDiv.classList.add('coluna-item', 'd-flex', 'align-items-center', 'mb-1'); newColunaDiv.innerHTML = `<input type="text" name="tabelas[${index}][cabecalhos][]" class="form-control form-control-sm me-2" placeholder="Coluna ${colIndex+1}"><button type="button" class="btn btn-danger btn-sm p-1 lh-1 btn-remover-coluna">X</button>`; colunasContainer.appendChild(newColunaDiv); } if (e.target.classList.contains('add-linha-btn')) { const linhasContainer = tabelaItem.querySelector('.linhas-container'); const colunasContainer = tabelaItem.querySelector('.colunas-container'); const colunasCount = colunasContainer.children.length; if (colunasCount === 0) { alert('Adicione pelo menos uma coluna antes de adicionar uma linha.'); return; } const rowIndex = linhasContainer.children.length; const newLinhaDiv = document.createElement('div'); newLinhaDiv.classList.add('linha-dados-container'); let inputsHTML = ''; for (let i = 0; i < colunasCount; i++) { inputsHTML += `<div class="dado-item"><input type="text" name="tabelas[${index}][linhas][${rowIndex}][]" class="form-control form-control-sm" placeholder="Dado ${i+1}"></div>`; } inputsHTML += `<div class="ms-2"><button type="button" class="btn btn-danger btn-sm p-1 lh-1 btn-remover-linha">X</button></div>`; newLinhaDiv.innerHTML = inputsHTML; linhasContainer.appendChild(newLinhaDiv); } if (e.target.classList.contains('btn-remover-linha')) { e.target.closest('.linha-dados-container').remove(); } if (e.target.classList.contains('btn-remover-coluna')) { const colunaItem = e.target.closest('.coluna-item'); const container = colunaItem.parentElement; const columnIndex = Array.from(container.children).indexOf(colunaItem); colunaItem.remove(); const linhas = tabelaItem.querySelectorAll('.linha-dados-container'); linhas.forEach(linha => { const celulaParaRemover = linha.children[columnIndex]; if (celulaParaRemover) { celulaParaRemover.remove(); } }); }});
            
            const imagensContainer = document.getElementById('imagens-container');
            const addImagemBtn = document.getElementById('add-imagem-btn');
            let imagemIndex = 0;
            addImagemBtn.addEventListener('click', function() {
                imagemIndex++;
                const newImagemDiv = document.createElement('div');
                newImagemDiv.classList.add('imagem-dinamica-item');
                newImagemDiv.innerHTML = `<button type="button" class="btn btn-danger btn-sm p-1 lh-1 btn-remover-bloco">X</button><h5>Imagem ${imagemIndex}</h5><div class="mb-2"><label class="form-label">URL da Imagem</label><input type="text" name="sn_imagens[${imagemIndex-1}][url]" class="form-control" placeholder="https://exemplo.com/imagem.jpg"></div><div><label class="form-label">Link de Destino (Opcional)</label><input type="text" name="sn_imagens[${imagemIndex-1}][link]" class="form-control" placeholder="https://destino.com"></div>`;
                imagensContainer.appendChild(newImagemDiv);
            });
            imagensContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remover-bloco')) {
                    e.target.closest('.imagem-dinamica-item').remove();
                }
            });
        });
    </script>
</body>
</html>