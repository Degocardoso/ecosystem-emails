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
    <title>Visualizador de E-mail</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --cor-principal: #023c2c;
            --cor-principal-hover: #035a41;
        }

        body {
            background-color: #f4f7f6;
        }

        .control-panel {
            background-color: #fff;
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .email-frame {
            width: 100%;
            height: calc(100vh - 80px);
            border: none;
        }

        .btn-enviar-dynamics {
            background-color: var(--cor-principal);
            border-color: var(--cor-principal);
            color: white;
        }

        .btn-enviar-dynamics:hover {
            background-color: var(--cor-principal-hover);
            border-color: var(--cor-principal-hover);
            color: white;
        }

        #confirm-view {
            max-width: 700px;
            width: 100%;
        }
    </style>
</head>

<body>

    <?php
    $filePath = urldecode($_GET['arquivo'] ?? '');
    $baseDir = realpath(dirname(__FILE__) . '/emails');
    $fullPath = realpath(dirname(__FILE__) . '/' . $filePath);
    if ($fullPath && file_exists($fullPath) && strpos($fullPath, $baseDir) === 0) {
        $emailContent = file_get_contents($fullPath);
    } else {
        die('<div style="text-align: center; padding: 50px; font-family: sans-serif;"><h1>Erro 404</h1><p>O arquivo de e-mail não foi encontrado no servidor.</p></div>');
    }
    ?>

    <div class="control-panel">
        <div id="initial-view">
            <button type="button" class="btn btn-lg btn-enviar-dynamics" id="start-send-btn">
                <i class="bi bi-send"></i> Enviar para o Dynamics 365
            </button>
        </div>

        <div id="confirm-view" style="display: none;" class="w-100">
            <div class="row g-2 align-items-center">
                <div class="col-md">
                    <label for="campaignName" class="form-label">Nome da Campanha</label>
                    <input type="text" class="form-control" id="campaignName" placeholder="Ex: Lançamento Setembro" required>
                </div>
                <div class="col-md">
                    <label for="emailSubject" class="form-label">Assunto do E-mail</label>
                    <input type="text" class="form-control" id="emailSubject" placeholder="Digite o assunto aqui..." required>
                </div>
                <div class="col-md-auto">
                    <label for="sendDateTime" class="form-label">Data de Envio</label>
                    <input type="datetime-local" class="form-control" id="sendDateTime" required>
                </div>
            </div>
            <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                <div id="status-message" class="me-auto"></div>
                <button type="button" class="btn btn-secondary" id="cancel-send-btn">Voltar</button>
                <button type="button" class="btn btn-success" id="confirm-send-btn">
                    <i class="bi bi-check-lg"></i> Confirmar Envio
                </button>
            </div>
        </div>
    </div>

    <iframe srcdoc="<?= htmlspecialchars($emailContent) ?>" class="email-frame"></iframe>

    <textarea id="email-content-source" style="display: none;">
    <?= htmlspecialchars($emailContent) ?>
</textarea>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const initialView = document.getElementById('initial-view');
            const confirmView = document.getElementById('confirm-view');
            const startButton = document.getElementById('start-send-btn');
            const subjectInput = document.getElementById('emailSubject');
            const campaignInput = document.getElementById('campaignName');
            const dateTimeInput = document.getElementById('sendDateTime');
            const confirmButton = document.getElementById('confirm-send-btn');
            const cancelButton = document.getElementById('cancel-send-btn');
            const statusDiv = document.getElementById('status-message');
            const originalConfirmHTML = confirmButton.innerHTML;

            // Função para preencher a data e hora atuais no formato correto
            const setNow = () => {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                dateTimeInput.value = now.toISOString().slice(0, 16);
            };

            startButton.addEventListener('click', function() {
                initialView.style.display = 'none';
                confirmView.style.display = 'block';
                setNow(); // Define a data/hora atual ao abrir
                campaignInput.focus();
            });

            cancelButton.addEventListener('click', function() {
                confirmView.style.display = 'none';
                initialView.style.display = 'block';
                statusDiv.innerHTML = '';
                subjectInput.value = '';
                campaignInput.value = '';
                confirmButton.disabled = false;
                subjectInput.disabled = false;
                campaignInput.disabled = false;
                dateTimeInput.disabled = false;
                confirmButton.innerHTML = originalConfirmHTML;
            });

            confirmButton.addEventListener('click', function() {
                const subject = subjectInput.value;
                const campaign = campaignInput.value;
                const dateTime = dateTimeInput.value;
                const htmlBody = document.getElementById('email-content-source').value;

                if (!subject || !campaign || !dateTime) {
                    statusDiv.innerHTML = '<div class="alert alert-warning py-2">Todos os campos são obrigatórios.</div>';
                    return;
                }

                // Formata a data para o padrão ISO 8601 (requerido pelo Dynamics)
                const dateTimeISO = new Date(dateTime).toISOString();

                statusDiv.innerHTML = '<div class="alert alert-info py-2">Enviando...</div>';
                confirmButton.disabled = true;
                cancelButton.disabled = true;
                subjectInput.disabled = true;
                campaignInput.disabled = true;
                dateTimeInput.disabled = true;
                confirmButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

                fetch('core/enviarParaDynamics.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= Csrf::token() ?>'
                        },
                        body: JSON.stringify({
                            nomeCampanha: campaign,
                            assunto: subject,
                            dataEnvio: dateTimeISO,
                            corpoHTML: htmlBody
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = `<div class="alert alert-success py-2">${data.message}</div>`;
                            confirmButton.innerHTML = `<i class="bi bi-check-circle-fill"></i>`;
                            cancelButton.style.display = 'none';
                        } else {
                            statusDiv.innerHTML = `<div class="alert alert-danger py-2"><strong>Erro:</strong> ${data.message}</div>`;
                            confirmButton.disabled = false;
                            cancelButton.disabled = false;
                            subjectInput.disabled = false;
                            campaignInput.disabled = false;
                            dateTimeInput.disabled = false;
                            confirmButton.innerHTML = originalConfirmHTML;
                        }
                    })
                    .catch(error => {
                        statusDiv.innerHTML = `<div class="alert alert-danger py-2"><strong>Falha na Conexão.</strong></div>`;
                        confirmButton.disabled = false;
                        cancelButton.disabled = false;
                        subjectInput.disabled = false;
                        campaignInput.disabled = false;
                        dateTimeInput.disabled = false;
                        confirmButton.innerHTML = originalConfirmHTML;
                        console.error('Erro de Fetch:', error);
                    });
            });
        });
    </script>

</body>

</html>