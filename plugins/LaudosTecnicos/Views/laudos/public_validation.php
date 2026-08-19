<?php
$model_info = $model_info ?? (object) array();
$document_version = $document_version ?? null;
$share = $share ?? null;
$variant_title = $variant_title ?? 'Laudo completo';
$qr_svg_data_uri = trim((string) ($qr_svg_data_uri ?? ''));
$public_validation_url = trim((string) ($public_validation_url ?? ''));
$share_token = trim((string) ($share_token ?? ''));
$allow_download = !empty($share->allow_download);
$allow_comments = !empty($share->allow_comments);
$password_required = !empty($password_required);
$password_validated = isset($password_validated) ? (bool) $password_validated : true;
$password_error = trim((string) ($password_error ?? ''));
$client_name = trim((string) ($model_info->client_name ?? '')) ?: '-';
$type_name = trim((string) ($model_info->type_name ?? '')) ?: '-';
$status_name = trim((string) ($model_info->status_name ?? $model_info->status ?? '-'));
$responsible_name = trim((string) ($model_info->technical_responsible_name ?? $model_info->commercial_responsible_name ?? '-'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validacao - <?php echo esc($model_info->number ?? '-'); ?></title>
    <style>
        :root {
            --c: <?php echo esc(get_array_value($settings ?? array(), 'main_color') ?: '#0d6efd'); ?>;
            --border: #d7dde6;
            --muted: #6b7280;
        }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f6f8fb;
            color: #111827;
        }
        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 14px 50px rgba(15,23,42,.08);
            overflow: hidden;
        }
        .hero {
            padding: 24px;
            background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(13,110,253,0));
            border-bottom: 1px solid var(--border);
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .badge.ok { background: rgba(25,135,84,.12); color: #198754; }
        .badge.info { background: rgba(13,110,253,.12); color: #0d6efd; }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 24px;
        }
        .item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .label {
            display: block;
            font-size: 10px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .value {
            font-size: 14px;
            font-weight: 700;
        }
        .box {
            padding: 0 24px 24px;
        }
        .safe {
            border: 1px dashed rgba(17,24,39,.18);
            background: rgba(17,24,39,.02);
            color: var(--muted);
            border-radius: 12px;
            padding: 14px;
            font-size: 13px;
            line-height: 1.5;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .actions a, .actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px;
            text-decoration: none;
            background: #fff;
            color: #111827;
            cursor: pointer;
        }
        .actions .primary {
            background: var(--c);
            border-color: var(--c);
            color: #fff;
        }
        .qr {
            width: 112px;
            height: 112px;
            display: inline-block;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--border);
            padding: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .field input, .field textarea, .field select {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            background: #fff;
        }
        .field textarea {
            min-height: 104px;
        }
        .muted {
            color: var(--muted);
        }
        @media (max-width: 900px) {
            .grid, .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="panel">
            <div class="hero">
                <div class="badge info">Validacao publica</div>
                <h1 style="margin:12px 0 6px;"><?php echo esc($model_info->number ?? '-'); ?> - <?php echo esc($model_info->title ?? '-'); ?></h1>
                <div class="muted"><?php echo esc($variant_title); ?> | <?php echo esc($client_name); ?></div>
            </div>

            <?php if ($password_required && !$password_validated) { ?>
                <div class="box">
                    <div class="safe" style="margin-top:24px;">
                        <?php echo esc($password_error ?: 'Este link possui proteção por senha.'); ?>
                    </div>
                    <form method="post" action="<?php echo esc(get_uri('laudostecnicos/laudos/share/' . $share_token)); ?>" style="margin-top:16px;">
                        <div class="form-grid" style="grid-template-columns: 1fr auto;">
                            <div class="field">
                                <label class="label">Senha de acesso</label>
                                <input type="password" name="password" autocomplete="current-password" required>
                            </div>
                            <div class="field" style="align-self:end;">
                                <button type="submit" class="primary" style="padding:12px 16px; border-radius:10px; border:1px solid var(--c); background:var(--c); color:#fff;">Liberar acesso</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php } else { ?>
            <div class="grid">
                <div class="item"><span class="label">Codigo</span><div class="value"><?php echo esc($document_version->document_code ?? '-'); ?></div></div>
                <div class="item"><span class="label">Revisao</span><div class="value"><?php echo esc($model_info->revision ?? '00'); ?></div></div>
                <div class="item"><span class="label">Tipo</span><div class="value"><?php echo esc($type_name); ?></div></div>
                <div class="item"><span class="label">Status</span><div class="value"><?php echo esc($status_name); ?></div></div>
                <div class="item"><span class="label">Emissao</span><div class="value"><?php echo esc($document_version->issued_at ?? $model_info->issue_date ?? '-'); ?></div></div>
                <div class="item"><span class="label">Validade</span><div class="value"><?php echo esc($model_info->validity_date ?? '-'); ?></div></div>
                <div class="item"><span class="label">Responsavel tecnico</span><div class="value"><?php echo esc($responsible_name); ?></div></div>
                <div class="item"><span class="label">Autenticidade</span><div class="value">Confirmada via QR e hash</div></div>
            </div>

            <div class="box">
                <div class="safe">
                    Esta pagina publica mostra apenas dados de autenticidade e metadados essenciais. Informacoes tecnicas, fotografias, observacoes internas e anexos permanecem restritos ao acesso autorizado.
                </div>

                <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-top:18px;">
                    <?php if ($qr_svg_data_uri) { ?><img class="qr" src="<?php echo esc($qr_svg_data_uri); ?>" alt="QR Code"><?php } ?>
                    <div>
                        <div class="muted" style="font-size:12px;">Hash do documento</div>
                        <div style="font-weight:700; word-break:break-all;"><?php echo esc($document_version->document_hash ?? '-'); ?></div>
                        <div class="muted" style="font-size:12px; margin-top:6px;">Pagina publica de validacao: <?php echo esc($public_validation_url ?: '-'); ?></div>
                    </div>
                </div>

                <?php if ($share_token) { ?>
                    <div class="actions">
                        <?php if ($allow_download) { ?><a class="primary" href="<?php echo esc(get_uri('laudostecnicos/laudos/share/' . $share_token . '/download')); ?>">Baixar PDF</a><?php } ?>
                        <a href="<?php echo esc(get_uri('laudostecnicos/laudos/share/' . $share_token)); ?>">Abrir link compartilhado</a>
                    </div>
                <?php } ?>
            </div>

            <?php if ($allow_comments) { ?>
                <div class="box">
                    <h3 style="margin:0 0 12px;">Portal do cliente</h3>
                    <form id="laudo-public-feedback-form" class="general-form" method="post" action="<?php echo esc(get_uri('laudostecnicos/portal/feedback')); ?>">
                        <input type="hidden" name="share_token" value="<?php echo esc($share_token); ?>">
                        <input type="hidden" name="evidence_json" id="evidence_json" value="[]">
                        <div class="form-grid">
                            <div class="field">
                                <label class="label">Nome do visitante</label>
                                <input type="text" name="visitor_label" value="<?php echo esc($share->visitor_label ?? ''); ?>" <?php echo !empty($share->require_visitor_id) ? 'required' : ''; ?>>
                            </div>
                            <div class="field">
                                <label class="label">Email</label>
                                <input type="email" name="visitor_email">
                            </div>
                            <div class="field">
                                <label class="label">Acao</label>
                                <select name="action">
                                    <option value="comment">Comentario</option>
                                    <option value="accept">Aceitar</option>
                                    <option value="reject">Rejeitar</option>
                                    <option value="received">Confirmar recebimento</option>
                                </select>
                            </div>
                        </div>
                        <div class="field" style="margin-top:12px;">
                            <label class="label">Comentario ou justificativa</label>
                            <textarea name="comment"></textarea>
                        </div>
                        <div class="field" style="margin-top:12px;">
                            <label class="label">Evidencias</label>
                            <textarea id="evidence_text" placeholder="Descreva arquivos, fotos ou evidencias adicionais"></textarea>
                        </div>
                        <div class="actions" style="margin-top:14px;">
                            <button type="submit" class="primary">Enviar</button>
                            <?php if ($allow_download && $share_token) { ?>
                                <a href="<?php echo esc(get_uri('laudostecnicos/laudos/share/' . $share_token . '/download')); ?>">Baixar documento</a>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            <?php } ?>
            <?php } ?>
        </div>
    </div>

    <script type="text/javascript">
        $(function () {
            $("#laudo-public-feedback-form").appForm({
                beforeAjaxSubmit: function () {
                    $("#evidence_json").val(JSON.stringify($("#evidence_text").val() ? [$("#evidence_text").val()] : []));
                },
                onSuccess: function (result) {
                    if (result && result.success) {
                        appAlert.success(result.message || "Enviado com sucesso.");
                    } else {
                        appAlert.error((result && result.message) ? result.message : "Erro ao enviar.");
                    }
                }
            });
        });
    </script>
</body>
</html>
