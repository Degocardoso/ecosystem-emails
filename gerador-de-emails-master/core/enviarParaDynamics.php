<?php

/**
 * Endpoint AJAX: envia o e-mail montado para o fluxo do Power Automate.
 *
 * Segurança:
 *  - Exige sessão autenticada com permissão "gerador.acesso".
 *  - Valida token CSRF (cabeçalho X-CSRF-Token).
 *  - A URL do Power Automate vem EXCLUSIVAMENTE do .env (POWER_AUTOMATE_URL),
 *    nunca do código-fonte.
 *  - Verificação de certificado TLS habilitada.
 */

require_once __DIR__ . '/../../auth/bootstrap.php';

use App\Auth\Auth;
use App\Auth\Csrf;

header('Content-Type: application/json');

// --- Autenticação / autorização (endpoint AJAX → responde JSON) ---
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit();
}
if (!Auth::can('gerador.acesso')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para esta ação.']);
    exit();
}

// --- CSRF (token enviado no cabeçalho pela requisição fetch) ---
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!Csrf::validate($csrfHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança (CSRF) inválido. Recarregue a página.']);
    exit();
}

// --- 0. CONFIGURAÇÃO (segredo lido do ambiente, nunca do código) ---
$powerAutomateUrl = (string) env('POWER_AUTOMATE_URL', '');

// --- 1. COLETA DOS DADOS ---
$input = json_decode(file_get_contents('php://input'), true);

$campaignName     = $input['nomeCampanha'] ?? '';
$subjectPlainText = $input['assunto'] ?? '';
$sendDate         = $input['dataEnvio'] ?? '';
$htmlBodyCompleto = $input['corpoHTML'] ?? '';

// --- 2. VALIDAÇÃO ---
if (empty($powerAutomateUrl) || strpos($powerAutomateUrl, 'http') !== 0) {
    echo json_encode(['success' => false, 'message' => 'A URL do Power Automate não foi configurada no arquivo .env (POWER_AUTOMATE_URL).']);
    exit();
}
if (empty($campaignName) || empty($subjectPlainText) || empty($sendDate) || empty($htmlBodyCompleto)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit();
}

// --- 3. EXTRAÇÃO DO CONTEÚDO DO E-MAIL ---
$corpoFinalParaEnvio = $htmlBodyCompleto;
preg_match('/<body[^>]*>([\s\S]*)<\/body>/i', $htmlBodyCompleto, $matches);
if (isset($matches[1])) {
    $corpoFinalParaEnvio = trim($matches[1]);
}

// --- 4. PREPARAÇÃO DOS DADOS PARA ENVIO ---
$subjectHtml = '<span>' . htmlspecialchars($subjectPlainText) . '</span>';

$postData = json_encode([
    'nomeCampanha' => $campaignName,
    'assunto'      => $subjectHtml,
    'dataEnvio'    => $sendDate, // Data já está no formato ISO 8601
    'corpoHTML'    => $corpoFinalParaEnvio
]);

// --- 5. ENVIO E RESPOSTA ---
$ch = curl_init($powerAutomateUrl);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);
// Verificação de certificado TLS habilitada (evita ataques man-in-the-middle).
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $errorMessage = 'Erro de conexão cURL: ' . curl_error($ch);
    curl_close($ch);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit();
}
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'message' => 'Dados enviados para o fluxo com sucesso!']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'O Power Automate respondeu com um erro. Status: ' . $httpCode
    ]);
}
