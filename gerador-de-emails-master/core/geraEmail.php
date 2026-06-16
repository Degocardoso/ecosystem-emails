<?php

// --- SEGURANÇA: exige login com permissão de gerador + valida CSRF ---
require_once __DIR__ . '/../../auth/guard.php';
require_permission('gerador.acesso');
\App\Auth\Csrf::requireValidToken();

// --- FUNÇÃO PARA SANITIZAR NOMES DE ARQUIVOS MANTENDO ACENTOS ---
function slugify($text) {
    // 1. Converte para minúsculas
    $text = mb_strtolower($text, 'UTF-8');
    // 2. Substitui espaços e underscores por hifens
    $text = preg_replace('/[\s_]+/', '-', $text);
    // 3. Remove todos os caracteres que NÃO são:
    //    - letras (incluindo acentuadas como ç, ã, é via \p{L})
    //    - números (0-9)
    //    - hifens (-)
    //    O 'u' no final é crucial para o suporte a UTF-8.
    $text = preg_replace('/[^\\p{L}0-9-]+/u', '', $text);
    // 4. Remove hifens do início e do fim
    $text = trim($text, '-');
    // 5. Se o texto ficar vazio, retorna um padrão
    if (empty($text)) {
        return 'arquivo-sem-nome';
    }
    return $text;
}


// --- ETAPA 0: VERIFICAR O TIPO DE MODELO ---
$tipoModelo = $_POST['tipoModelo'] ?? 'comum';


if ($tipoModelo === 'comum') {
    // --- LÓGICA PARA O "EMAIL COMUM" ---
    $nomeEmailInput = $_POST['nomeEmail'];
    $diretorioEmailInput = $_POST['diretorioEmail'];
    $topoEmail = $_POST['topoEmail'];
    $alturaTopoEmail = $_POST['alturaTopoEmail'];
    $rodapeEmail = $_POST['rodapeEmail'];
    $alturaRodapeEmail = $_POST['alturaRodapeEmail'];
    $conteudoEmail = $_POST['conteudoEmail'];
    $botoes = $_POST['botoes'] ?? [];
    $tabelas = $_POST['tabelas'] ?? [];

    // USA A NOVA FUNÇÃO SLUGIFY
    $nomeEmail = slugify($nomeEmailInput);
    $diretorioEmail = slugify($diretorioEmailInput);

    // Lógica de Placeholders e Parser (completa e sem alterações)
    $placeholders = [];
    if (!empty($botoes)) { foreach ($botoes as $index => $botao) { $textoBotao = $botao['texto'] ?? ''; $linkBotao = $botao['link'] ?? ''; if (!empty($textoBotao) && !empty($linkBotao)) { $placeholder = '{{botao' . ($index + 1) . '}}'; $placeholders[$placeholder] = '<p style="color: rgb(44, 38, 38); padding: 0 40px; font-family: Calibri, sans-serif; font-size: 16px; text-align: center; margin: 0px;"><strong><a style="background-color: #023c2c; border: 1px solid #023c2c; border-radius: 20px; color: white; display: inline-block; font-family: arial, helvetica, sans-serif; font-size: 14px; font-weight: 700; letter-spacing: 0px; line-height: 16px; padding: 12px 18px; text-align: center; text-decoration: none; margin-right: 5px;" href="' . htmlspecialchars($linkBotao) . '" target="_blank">' . htmlspecialchars($textoBotao) . '</a></strong></p>'; }}}
    if (!empty($tabelas)) { foreach ($tabelas as $index => $tabela) { $placeholder = '{{tabela' . ($index + 1) . '}}'; $titulo = $tabela['titulo'] ?? ''; $cabecalhos = $tabela['cabecalhos'] ?? []; $linhas = $tabela['linhas'] ?? []; $numColunas = count($cabecalhos); if ($numColunas > 0) { $tabelaHtml = '<center><table width="560" border="1" cellspacing="0" cellpadding="6" style="max-width: 560px; border-collapse: collapse; font-family: Calibri, Arial, sans-serif; font-size: 14px; color: #2c2626; margin: 12px auto; border: 1px solid #ddd; text-align: center;">'; if (!empty($titulo)) { $tabelaHtml .= '<thead><tr style="background: #023c2c; color: #ffffff; font-weight: bold;"><th colspan="' . $numColunas . '" style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($titulo) . '</th></tr></thead>'; } $tabelaHtml .= '<thead><tr style="background: #023c2c; color: #ffffff">'; foreach ($cabecalhos as $th) { $tabelaHtml .= '<th style="padding: 8px; border: 1px solid #ddd">' . htmlspecialchars($th) . '</th>'; } $tabelaHtml .= '</tr></thead>'; $tabelaHtml .= '<tbody>'; if (!empty($linhas)) { foreach ($linhas as $linha) { $tabelaHtml .= '<tr>'; foreach ($linha as $td) { $tabelaHtml .= '<td style="padding: 8px; border: 1px solid #ddd">' . htmlspecialchars($td) . '</td>'; } $celulasFaltantes = $numColunas - count($linha); for ($i = 0; $i < $celulasFaltantes; $i++) { $tabelaHtml .= '<td style="padding: 8px; border: 1px solid #ddd"></td>'; } $tabelaHtml .= '</tr>'; } } $tabelaHtml .= '</tbody></table></center>'; $placeholders[$placeholder] = $tabelaHtml; }}}
    $conteudoProcessado = $conteudoEmail;
    if (!empty($placeholders)) { foreach ($placeholders as $placeholder => $html) { $pattern = '/(<p.*?>)(.*?)(?<!\w)' . preg_quote($placeholder) . '(?!\w)(.*?)<\/p>/s'; $conteudoProcessado = preg_replace_callback($pattern, function ($matches) use ($html) { $tagAbertura = $matches[1]; $textoAntes = trim($matches[2]); $textoDepois = trim($matches[3]); $htmlReconstruido = ''; if (!empty($textoAntes)) { $htmlReconstruido .= $tagAbertura . $textoAntes . '</p>'; } $htmlReconstruido .= $html; if (!empty($textoDepois)) { $htmlReconstruido .= $tagAbertura . $textoDepois . '</p>'; } return $htmlReconstruido; }, $conteudoProcessado); }}
    $estiloBaseP = "color:rgb(44, 38, 38); padding:0 40px 0 40px; font-family: Calibri, sans-serif; font-size:16px; margin: 0px;";
    $estiloJustify = 'style="' . $estiloBaseP . ' text-align: justify;"'; $estiloCenter  = 'style="' . $estiloBaseP . ' text-align: center;"'; $estiloRight   = 'style="' . $estiloBaseP . ' text-align: right;"';
    $estiloLi = 'style="color: rgb(44, 38, 38); font-family: Calibri, sans-serif; font-size: 16px; text-align: justify; margin: 0px 40px 0px 20px;"';
    $conteudoProcessado = str_replace('<p style="text-align: center;">', "<p $estiloCenter>", $conteudoProcessado); $conteudoProcessado = str_replace('<p style="text-align: right;">', "<p $estiloRight>", $conteudoProcessado); $conteudoProcessado = str_replace('<p>', "<p $estiloJustify>", $conteudoProcessado); $conteudoProcessado = str_replace('<li>', "<li $estiloLi>", $conteudoProcessado);
    $arquivo = $nomeEmail . '.html';
    $conteudoFinal = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>' . htmlspecialchars($nomeEmail) . '</title></head><body style="background: #efefef;"><center><table width="600" border="0" cellspacing="0" style="background: #fff;"><tr><td height="' . htmlspecialchars($alturaTopoEmail) . '" colspan="5" style="padding: 0px 0px 0px 0px; background: rgb(243, 243, 243);"><img src="' . htmlspecialchars($topoEmail) . '" alt="Cabeçalho" width="600" height="' . htmlspecialchars($alturaTopoEmail) . '" border="0" style="display: block;" /></td></tr><tr><td colspan="5" style="background: rgb(243, 243, 243);" height="100%"><br />' . $conteudoProcessado . '<br /></td></tr><tr><td height="' . htmlspecialchars($alturaRodapeEmail) . '" colspan="5" style="padding: 0px 0px 0px 0px; background: rgb(243, 243, 243);"><img alt="Rodapé" width="600" height="' . htmlspecialchars($alturaRodapeEmail) . '" border="0" src="' . htmlspecialchars($rodapeEmail) . '" style="display: block;" /></td></tr></table></center></body></html>';

} else {
    // --- LÓGICA PARA O "SUCESSO NEWS" ---
    $nomeEmailInput = $_POST['sn_nomeEmail'];
    $diretorioEmailInput = $_POST['sn_diretorioEmail'];
    $edicao = $_POST['sn_edicao'];
    $imagens = $_POST['sn_imagens'] ?? [];

    // USA A NOVA FUNÇÃO SLUGIFY
    $nomeEmail = slugify($nomeEmailInput);
    $diretorioEmail = slugify($diretorioEmailInput);

    $imagensHtml = '';
    foreach ($imagens as $img) {
        $url = $img['url'] ?? ''; $link = $img['link'] ?? '';
        if (!empty($url)) {
            $imagemTag = '<img src="' . htmlspecialchars($url) . '" alt="" width="600" border="0" style="display:block;" />';
            if (!empty($link)) { $imagensHtml .= '<tr><td><a href="' . htmlspecialchars($link) . '" target="_blank">' . $imagemTag . '</a></td></tr>'; } 
            else { $imagensHtml .= '<tr><td>' . $imagemTag . '</td></tr>'; }
        }
    }
    $arquivo = $nomeEmail . '.html';
    $conteudoFinal = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>' . htmlspecialchars($nomeEmail) . '</title></head><body bgcolor="#efefef" style="margin:0; padding:0;"><table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#efefef" align="center"><tr><td colspan="5" style="background-color: #282828; padding: 5px 10px; font-size: 12px; font-family: Arial, Helvetica, sans-serif; color: #fff; text-align: left;">' . htmlspecialchars($edicao) . '</td></tr><tr><td align="center"><table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#f3f3f3" style="width:600px; max-width:600px;">' . $imagensHtml . '</table></td></tr></table></body></html>';
}


// --- 5. LÓGICA PARA CRIAR PASTA E SALVAR O ARQUIVO (COMUM A AMBOS) ---
$anoAtual = date('Y');
$mesAtual = date('n');
$semestre = ($mesAtual <= 6) ? '1' : '2';
$pastaSemestre = $anoAtual . '-' . $semestre;
$caminhoBase = dirname(dirname(__FILE__)) . '/emails/';
$caminhoCompleto = $caminhoBase . $pastaSemestre . '/' . $diretorioEmail;
if (!file_exists($caminhoCompleto)) {
    mkdir($caminhoCompleto, 0755, true);
}
file_put_contents($caminhoCompleto . '/' . $arquivo, $conteudoFinal);


// --- 6. REDIRECIONAMENTO PARA A TELA DE SUCESSO (caminho relativo) ---
$urlPublica = 'emails/' . $pastaSemestre . '/' . $diretorioEmail . '/' . $arquivo;
header('Location: ../sucesso.html?url=' . urlencode($urlPublica));
exit();

?>