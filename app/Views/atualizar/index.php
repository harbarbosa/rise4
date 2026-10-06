<?php
$result = isset($result) && is_array($result) ? $result : array();
$success = !empty($result['success']);
$steps = isset($result['steps']) && is_array($result['steps']) ? $result['steps'] : array();
$duration = isset($result['duration']) ? number_format((float)$result['duration'], 2, ',', '.') : '0,00';
$previous_commit = $result['previous_commit'] ?? '';
$current_commit = $result['current_commit'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atualização do sistema</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f6fb;
            --card: #ffffff;
            --text: #172033;
            --muted: #667085;
            --line: #e4e9f2;
            --primary: #3157d5;
            --success: #16835b;
            --success-bg: #eaf8f2;
            --danger: #c0394b;
            --danger-bg: #fff0f2;
            --shadow: 0 16px 45px rgba(25, 42, 77, .10);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(145deg, #edf3ff 0%, var(--bg) 48%, #f8fafc 100%);
            color: var(--text);
            font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .page { width: min(920px, calc(100% - 32px)); margin: 48px auto; }
        .header, .step, .summary { background: var(--card); border: 1px solid var(--line); box-shadow: var(--shadow); }
        .header { border-radius: 18px; padding: 28px; display: flex; align-items: center; gap: 20px; }
        .status-icon {
            width: 58px; height: 58px; flex: 0 0 58px; border-radius: 50%;
            display: grid; place-items: center; color: white; font-size: 28px; font-weight: 700;
            background: <?= $success ? 'var(--success)' : 'var(--danger)' ?>;
        }
        h1 { margin: 0 0 4px; font-size: clamp(24px, 4vw, 34px); line-height: 1.15; }
        .subtitle { color: var(--muted); margin: 0; }
        .summary {
            margin-top: 18px; padding: 18px 22px; border-radius: 14px;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
        }
        .summary-label { display: block; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
        .summary-value { display: block; margin-top: 3px; font-weight: 650; overflow-wrap: anywhere; }
        .section-title { margin: 28px 0 12px; font-size: 17px; }
        .steps { display: grid; gap: 12px; }
        .step { border-radius: 14px; overflow: hidden; box-shadow: 0 8px 24px rgba(25, 42, 77, .06); }
        .step-head { display: flex; align-items: center; gap: 12px; padding: 16px 18px; }
        .step-badge {
            width: 29px; height: 29px; flex: 0 0 29px; border-radius: 50%;
            display: grid; place-items: center; color: white; font-weight: 700;
            background: var(--success);
        }
        .step.failed .step-badge { background: var(--danger); }
        .step-name { flex: 1; font-weight: 650; }
        .step-state { color: var(--success); font-weight: 650; font-size: 13px; }
        .step.failed .step-state { color: var(--danger); }
        details { border-top: 1px solid var(--line); }
        summary { cursor: pointer; padding: 11px 18px; color: var(--primary); font-size: 13px; user-select: none; }
        pre {
            margin: 0; padding: 16px 18px; max-height: 330px; overflow: auto;
            background: #111827; color: #dbe7ff; font: 12px/1.55 Consolas, Monaco, monospace;
            white-space: pre-wrap; overflow-wrap: anywhere;
        }
        .empty { color: var(--muted); font-style: italic; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .button {
            display: inline-flex; align-items: center; justify-content: center; min-height: 42px;
            padding: 0 18px; border-radius: 10px; text-decoration: none; font-weight: 650;
            color: white; background: var(--primary);
        }
        .note { margin-top: 18px; color: var(--muted); text-align: center; font-size: 12px; }
        @media (max-width: 650px) {
            .page { margin: 22px auto; }
            .header { align-items: flex-start; padding: 22px; }
            .summary { grid-template-columns: 1fr; }
            .step-state { display: none; }
        }
    </style>
</head>
<body>
<main class="page">
    <section class="header">
        <div class="status-icon" aria-hidden="true"><?= $success ? '&#10003;' : '!' ?></div>
        <div>
            <h1><?= esc($result['message'] ?? 'Atualização concluída') ?></h1>
            <p class="subtitle">
                <?= $success
                    ? 'Os arquivos e módulos do sistema foram processados.'
                    : 'Uma ou mais etapas não foram concluídas. Consulte os detalhes abaixo.' ?>
            </p>
        </div>
    </section>

    <section class="summary">
        <div>
            <span class="summary-label">Resultado</span>
            <span class="summary-value"><?= $success ? 'Concluído com sucesso' : 'Concluído com falhas' ?></span>
        </div>
        <div>
            <span class="summary-label">Versão instalada</span>
            <span class="summary-value"><?= esc($current_commit ? substr($current_commit, 0, 12) : 'Não identificada') ?></span>
        </div>
        <div>
            <span class="summary-label">Tempo total</span>
            <span class="summary-value"><?= esc($duration) ?> segundos</span>
        </div>
    </section>

    <?php if ($previous_commit && $current_commit && $previous_commit !== $current_commit): ?>
        <p class="note">Versão anterior <?= esc(substr($previous_commit, 0, 12)) ?> &rarr; nova versão <?= esc(substr($current_commit, 0, 12)) ?></p>
    <?php elseif ($current_commit): ?>
        <p class="note">O sistema já estava ou permaneceu na versão <?= esc(substr($current_commit, 0, 12)) ?>.</p>
    <?php endif; ?>

    <h2 class="section-title">Etapas executadas</h2>
    <section class="steps">
        <?php foreach ($steps as $index => $step): ?>
            <?php
            $step_success = !empty($step['success']);
            $output = trim((string)($step['output'] ?? ''));
            $decoded = json_decode($output, true);
            if (is_array($decoded)) {
                $output = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            ?>
            <article class="step <?= $step_success ? '' : 'failed' ?>">
                <div class="step-head">
                    <div class="step-badge"><?= $step_success ? '&#10003;' : '!' ?></div>
                    <div class="step-name"><?= esc($step['step'] ?? ('Etapa ' . ($index + 1))) ?></div>
                    <div class="step-state"><?= $step_success ? 'Sucesso' : 'Falhou' ?></div>
                </div>
                <details <?= $step_success ? '' : 'open' ?>>
                    <summary>Ver detalhes técnicos</summary>
                    <pre><?= $output !== '' ? esc($output) : '<span class="empty">Nenhuma mensagem retornada.</span>' ?></pre>
                </details>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="actions">
        <a class="button" href="<?= esc(current_url(true)) ?>">Executar novamente</a>
    </div>
    <p class="note">Mantenha esta página restrita a usuários autorizados.</p>
</main>
</body>
</html>
