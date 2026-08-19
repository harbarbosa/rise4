<?php
$model_info = $model_info ?? (object) array();
$settings = is_array($settings ?? null) ? $settings : array();
$variant = laudostecnicos_normalize_document_variant((string) ($variant ?? 'full'));
$variant_title = $variant_title ?? 'Laudo completo';
$public_mode = !empty($public_mode);
$is_print_mode = !empty($is_print_mode);
$document_version = $document_version ?? null;
$feedbacks = is_array($feedbacks ?? null) ? $feedbacks : array();

$main_color = trim((string) get_array_value($settings, 'main_color')) ?: '#0d6efd';
$logo_url = function_exists('get_logo_url') ? get_logo_url() : '';
$client_name = trim((string) ($model_info->client_name ?? '')) ?: 'Cliente';
$type_name = trim((string) ($model_info->type_name ?? '')) ?: '-';
$category_name = trim((string) ($model_info->category_name ?? '')) ?: '-';
$status_name = trim((string) ($model_info->status_name ?? $model_info->status ?? '-'));
$status_color = trim((string) ($model_info->status_color ?? '#6c757d'));
$responsible_name = trim((string) ($model_info->technical_responsible_name ?? $model_info->commercial_responsible_name ?? '-'));
$number = trim((string) ($model_info->number ?? '-'));
$revision = trim((string) ($model_info->revision ?? '00'));
$issue_date = trim((string) ($model_info->issue_date ?? '-'));
$validity_date = trim((string) ($model_info->validity_date ?? '-'));
$document_code = trim((string) ($document_code ?? ''));
$document_hash = trim((string) ($document_hash ?? ''));
$qr_svg_data_uri = trim((string) ($qr_svg_data_uri ?? ''));
$share_url = trim((string) ($share_url ?? ''));
$public_validation_url = trim((string) ($public_validation_url ?? ''));
$variant_titles = is_array($variant_titles ?? null) ? $variant_titles : array();

$section_values = array(
    'Objective' => $model_info->objective ?? '',
    'Scope' => $model_info->scope ?? '',
    'Methodology' => $model_info->methodology ?? '',
    'Premises' => $model_info->premises ?? '',
    'Limitations' => $model_info->limitations ?? '',
    'Installation description' => $model_info->installation_description ?? '',
    'Results' => $model_info->results ?? '',
    'Diagnosis' => $model_info->diagnosis ?? '',
    'Conclusion' => $model_info->conclusion ?? '',
    'Recommendations' => $model_info->recommendations ?? '',
    'Internal notes' => $model_info->internal_notes ?? '',
);

$include_sections = array(
    'full' => array('objective', 'scope', 'methodology', 'premises', 'limitations', 'installation', 'results', 'diagnosis', 'conclusion', 'recommendations', 'signatures', 'attachments'),
    'executive' => array('summary', 'conclusion', 'recommendations', 'signatures'),
    'photo' => array('summary', 'photographs', 'signatures'),
    'nc' => array('summary', 'nonconformities', 'signatures'),
    'action-plan' => array('summary', 'action-plan', 'signatures'),
    'acceptance' => array('summary', 'acceptance', 'signatures'),
    'certificate' => array('summary', 'certificate', 'signatures'),
);

$active_sections = get_array_value($include_sections, $variant) ?: $include_sections['full'];

$summary_rows = array(
    array('label' => 'Numero', 'value' => $number),
    array('label' => 'Revisao', 'value' => $revision),
    array('label' => 'Cliente', 'value' => $client_name),
    array('label' => 'Unidade', 'value' => trim((string) ($model_info->unit_name ?? '-')) ?: '-'),
    array('label' => 'Tipo', 'value' => $type_name),
    array('label' => 'Categoria', 'value' => $category_name),
    array('label' => 'Responsavel tecnico', 'value' => $responsible_name),
    array('label' => 'Status', 'value' => $status_name),
    array('label' => 'Emissao', 'value' => $issue_date),
    array('label' => 'Validade', 'value' => $validity_date),
);

if (!$document_code) {
    $document_code = laudostecnicos_build_document_code($number, $revision, (int) ($document_version->id ?? 1));
}

$toc_items = array(
    'Capa',
    'Identificacao',
    'Dados do cliente',
    'Conteudo tecnico',
    'Medições',
    'Fotografias',
    'Nao conformidades',
    'Plano de acao',
    'Assinaturas',
    'Anexos',
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc($document_code ?: $number); ?></title>
    <style>
        :root {
            --doc-color: <?php echo esc($main_color); ?>;
            --doc-muted: #6c757d;
            --doc-border: #d7dde6;
            --doc-bg: #f6f8fb;
            --doc-ink: #111827;
        }
        @page {
            size: A4;
            margin: 14mm 12mm 16mm 12mm;
        }
        html, body {
            font-family: Arial, Helvetica, sans-serif;
            color: var(--doc-ink);
            background: #fff;
            margin: 0;
            padding: 0;
        }
        body {
            font-size: 12px;
            line-height: 1.45;
        }
        .doc-shell {
            width: 100%;
        }
        .doc-page {
            page-break-after: always;
            padding-bottom: 8mm;
        }
        .doc-header, .doc-footer {
            width: 100%;
            border-color: var(--doc-border);
        }
        .doc-header {
            border-bottom: 2px solid var(--doc-color);
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .doc-footer {
            border-top: 1px solid var(--doc-border);
            padding-top: 8px;
            margin-top: 14px;
            color: var(--doc-muted);
            font-size: 10px;
        }
        .doc-cover {
            min-height: 245mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(180deg, rgba(13,110,253,.07), rgba(13,110,253,0));
            border: 1px solid var(--doc-border);
            border-radius: 14px;
            padding: 20px;
            box-sizing: border-box;
        }
        .cover-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
        }
        .brand-block {
            max-width: 56%;
        }
        .brand-logo {
            max-height: 62px;
            max-width: 170px;
            object-fit: contain;
            margin-bottom: 14px;
        }
        .client-badge {
            border: 1px solid var(--doc-border);
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            text-align: center;
            min-width: 200px;
        }
        .client-badge .fake-logo {
            width: 96px;
            height: 96px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--doc-color), #111827);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            margin: 0 auto 12px;
            letter-spacing: .08em;
        }
        .eyebrow {
            text-transform: uppercase;
            letter-spacing: .18em;
            color: var(--doc-muted);
            font-size: 10px;
            margin-bottom: 8px;
        }
        h1, h2, h3, h4, h5 {
            margin: 0 0 10px 0;
            line-height: 1.2;
        }
        h1 {
            font-size: 30px;
        }
        h2 {
            font-size: 20px;
        }
        h3 {
            font-size: 16px;
        }
        .muted {
            color: var(--doc-muted);
        }
        .cover-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 22px;
        }
        .meta-card, .content-card, .toc-card, .stats-card, .note-card {
            border: 1px solid var(--doc-border);
            border-radius: 12px;
            background: #fff;
            padding: 14px;
            box-sizing: border-box;
        }
        .meta-card strong, .section-title {
            color: var(--doc-color);
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .summary-item {
            border: 1px solid var(--doc-border);
            border-radius: 12px;
            padding: 10px 12px;
            background: #fff;
        }
        .summary-item .label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--doc-muted);
            margin-bottom: 4px;
        }
        .summary-item .value {
            font-size: 13px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid var(--doc-border);
            padding: 8px 9px;
            vertical-align: top;
        }
        th {
            background: #f3f6fb;
            color: #1f2937;
            text-align: left;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
        .page-break {
            page-break-before: always;
        }
        .section {
            margin-bottom: 16px;
        }
        .section-block {
            margin-bottom: 14px;
        }
        .section-heading {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: baseline;
            margin-bottom: 8px;
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .photo-box {
            border: 1px dashed var(--doc-border);
            border-radius: 12px;
            min-height: 140px;
            background: linear-gradient(180deg, #fff, #f9fbff);
            padding: 12px;
        }
        .timeline-item {
            border-left: 3px solid var(--doc-color);
            padding-left: 12px;
            margin-bottom: 12px;
        }
        .tag {
            display: inline-block;
            background: rgba(13,110,253,.08);
            color: var(--doc-color);
            border-radius: 999px;
            padding: 4px 10px;
            margin-right: 6px;
            margin-bottom: 6px;
            font-size: 10px;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .signature-box {
            min-height: 120px;
            border: 1px solid var(--doc-border);
            border-radius: 12px;
            padding: 12px;
        }
        .signature-line {
            margin-top: 48px;
            border-top: 1px solid #222;
            padding-top: 6px;
            font-size: 11px;
        }
        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--doc-border);
            border-radius: 12px;
            padding: 10px;
            background: #fff;
        }
        .qr-box img {
            width: 110px;
            height: 110px;
        }
        .toolbar {
            <?php if ($public_mode) { ?>display: none;<?php } else { ?>display: flex;<?php } ?>
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .toolbar a, .toolbar button {
            border: 1px solid var(--doc-border);
            background: #fff;
            color: var(--doc-ink);
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
        }
        .toolbar a.primary, .toolbar button.primary {
            background: var(--doc-color);
            border-color: var(--doc-color);
            color: #fff;
        }
        .banner {
            border: 1px solid rgba(13,110,253,.24);
            background: rgba(13,110,253,.06);
            color: #123c7a;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .confidence-banner {
            border: 1px dashed rgba(17,24,39,.2);
            background: rgba(17,24,39,.03);
            color: var(--doc-muted);
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 11px;
        }
        .footer-note {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 10px;
            color: var(--doc-muted);
        }
        .toc-list {
            margin: 0;
            padding-left: 18px;
        }
        .toc-list li {
            margin-bottom: 4px;
        }
        .action-pill {
            display: inline-block;
            border: 1px solid var(--doc-border);
            border-radius: 999px;
            padding: 4px 10px;
            margin-right: 6px;
            margin-bottom: 6px;
            background: #fff;
        }
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: <?php echo esc($status_color ?: '#6c757d'); ?>;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
        }
        .watermark {
            position: fixed;
            top: 42%;
            left: 18%;
            right: 18%;
            text-align: center;
            font-size: 52px;
            font-weight: 700;
            color: rgba(17, 24, 39, .05);
            transform: rotate(-20deg);
            pointer-events: none;
            z-index: 0;
        }
        .content-layer {
            position: relative;
            z-index: 1;
        }
        .pdf-only {
            display: block;
        }
        .screen-only {
            <?php if ($is_print_mode) { ?>display: none;<?php } ?>
        }
        .confidence-seal {
            border: 1px solid var(--doc-border);
            border-radius: 12px;
            padding: 14px;
            background: linear-gradient(135deg, #fff, #f7faff);
        }
        .small {
            font-size: 11px;
        }
        .page-footer-spacer {
            height: 12px;
        }
        @media print {
            .toolbar, .screen-only {
                display: none !important;
            }
            .doc-page {
                page-break-after: always;
            }
            .avoid-break, .meta-card, .content-card, .summary-item, .photo-box, .signature-box, .toc-card, .stats-card, .note-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="watermark"><?php echo esc(strtoupper($variant_title)); ?></div>
    <div class="doc-shell content-layer">
        <div class="toolbar screen-only">
            <a class="primary" href="<?php echo esc($print_url); ?>" target="_blank">Abrir PDF</a>
            <a href="<?php echo esc($document_url); ?>" target="_blank">HTML</a>
            <?php if ($public_validation_url) { ?><a href="<?php echo esc($public_validation_url); ?>" target="_blank">Validacao publica</a><?php } ?>
            <?php if ($share_url) { ?><a href="<?php echo esc($share_url); ?>" target="_blank">Link compartilhado</a><?php } ?>
        </div>

        <section class="doc-page doc-cover avoid-break">
            <div class="cover-top">
                <div class="brand-block">
                    <div class="eyebrow">Laudos Tecnicos</div>
                    <?php if ($logo_url) { ?>
                        <img class="brand-logo" src="<?php echo esc($logo_url); ?>" alt="Logo">
                    <?php } ?>
                    <h1><?php echo esc($safe_title ?? $number); ?></h1>
                    <h3 class="muted"><?php echo esc($variant_title); ?></h3>
                    <p class="muted mb-0">Documento emitido em formato A4, com trilha de autenticidade, controle de versao e validacao publica.</p>
                    <div class="cover-meta">
                        <div class="meta-card"><strong>Documento</strong><br><?php echo esc($document_code); ?></div>
                        <div class="meta-card"><strong>Numero</strong><br><?php echo esc($number); ?></div>
                        <div class="meta-card"><strong>Revisao</strong><br><?php echo esc($revision); ?></div>
                        <div class="meta-card"><strong>Status</strong><br><span class="status-chip"><?php echo esc($status_name); ?></span></div>
                    </div>
                </div>
                <div class="client-badge">
                    <div class="fake-logo"><?php echo esc(strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $client_name) ?: 'CL', 0, 2))); ?></div>
                    <strong><?php echo esc($client_name); ?></strong>
                    <div class="muted small mt-2"><?php echo esc(trim((string) ($model_info->unit_name ?? '-')) ?: '-'); ?></div>
                </div>
            </div>

            <div class="cover-meta">
                <div class="meta-card"><strong>Responsavel tecnico</strong><br><?php echo esc($responsible_name); ?></div>
                <div class="meta-card"><strong>Categoria</strong><br><?php echo esc($category_name); ?></div>
                <div class="meta-card"><strong>Emissao</strong><br><?php echo esc($issue_date); ?></div>
                <div class="meta-card"><strong>Validade</strong><br><?php echo esc($validity_date); ?></div>
            </div>

            <div class="cover-meta">
                <div class="qr-box">
                    <?php if ($qr_svg_data_uri) { ?>
                        <img src="<?php echo esc($qr_svg_data_uri); ?>" alt="QR Code">
                    <?php } ?>
                    <div class="small text-center">Validacao por QR Code</div>
                </div>
                <div class="confidence-seal">
                    <div class="section-title">Autenticidade</div>
                    <p class="mb-2">Este documento pode ser validado publicamente por meio do codigo unico e hash de emissao.</p>
                    <div class="small"><strong>Hash:</strong> <?php echo esc($document_hash ?: '-'); ?></div>
                    <div class="small"><strong>Publicacao:</strong> <?php echo esc($document_version->issued_at ?? $issue_date ?? '-'); ?></div>
                    <div class="small"><strong>Confidencialidade:</strong> <?php echo esc(trim((string) ($model_info->confidentiality ?? '')) ?: 'Conforme configuracao do laudo'); ?></div>
                </div>
            </div>
        </section>

        <section class="doc-page">
            <div class="doc-header">
                <div class="section-title">Identificacao</div>
                <div class="footer-note">
                    <span><?php echo esc($document_code); ?></span>
                    <span><?php echo esc($variant_title); ?></span>
                </div>
            </div>

            <div class="summary-grid avoid-break">
                <?php foreach ($summary_rows as $row) { ?>
                    <div class="summary-item">
                        <span class="label"><?php echo esc($row['label']); ?></span>
                        <span class="value"><?php echo esc($row['value']); ?></span>
                    </div>
                <?php } ?>
            </div>

            <div class="section-block page-break">
                <div class="section-heading">
                    <h2>Sumario</h2>
                    <span class="muted">Pagina inicial</span>
                </div>
                <div class="toc-card">
                    <ol class="toc-list">
                        <?php foreach ($toc_items as $item) { ?>
                            <li><?php echo esc($item); ?></li>
                        <?php } ?>
                    </ol>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Dados do cliente</h2></div>
                <div class="content-card">
                    <table class="avoid-break">
                        <tr><th>Cliente</th><td><?php echo esc($client_name); ?></td><th>Projeto</th><td><?php echo esc($model_info->project_name ?? '-'); ?></td></tr>
                        <tr><th>Contato</th><td><?php echo esc($model_info->contact_name ?? '-'); ?></td><th>Unidade</th><td><?php echo esc($model_info->unit_name ?? '-'); ?></td></tr>
                        <tr><th>Endereco</th><td colspan="3"><?php echo laudostecnicos_safe_html($model_info->address ?? '-'); ?></td></tr>
                        <tr><th>Local da inspecao</th><td colspan="3"><?php echo laudostecnicos_safe_html($model_info->inspection_location ?? '-'); ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Conteudo tecnico</h2></div>
                <div class="content-card">
                    <?php foreach ($section_values as $label => $value) { ?>
                        <?php if ($variant !== 'executive' || in_array($label, array('Conclusion', 'Recommendations', 'Results', 'Diagnosis'), true)) { ?>
                            <div class="timeline-item">
                                <div class="section-title"><?php echo esc($label); ?></div>
                                <div class="muted"><?php echo $value !== '' ? laudostecnicos_safe_html($value) : '<span class="muted">Nao informado</span>'; ?></div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Checklists e medições</h2></div>
                <div class="content-card">
                    <div class="stats-card avoid-break">
                        <strong>Resumo operacional</strong>
                        <div class="small muted">Itens obrigatorios, medições classificadas e evidencias ficam vinculadas ao laudo quando integrados aos modulos operacionais.</div>
                    </div>
                    <table class="mt-2">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Item</th>
                                <th>Resposta</th>
                                <th>Classificacao</th>
                                <th>Evidencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Inspecao visual</td>
                                <td>Captor e condutores estao fixados?</td>
                                <td>Conforme</td>
                                <td>OK</td>
                                <td>Foto vinculada</td>
                            </tr>
                            <tr>
                                <td>Medicoes</td>
                                <td>Resistencia de aterramento</td>
                                <td>2.4 Ohm</td>
                                <td>Conforme</td>
                                <td>Registro automatico</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Fotografias</h2></div>
                <div class="photo-grid">
                    <div class="photo-box">
                        <strong>Foto 01</strong><br>
                        <span class="muted">Legenda, GPS, setor e observacoes da evidencia.</span>
                    </div>
                    <div class="photo-box">
                        <strong>Foto 02</strong><br>
                        <span class="muted">Antes e depois, anotacoes ou destaque tecnico.</span>
                    </div>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Nao conformidades</h2></div>
                <div class="content-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Titulo</th>
                                <th>Classificacao</th>
                                <th>Risco</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>NC-001</td>
                                <td>Protecao avariada</td>
                                <td>Alta</td>
                                <td>Critico</td>
                                <td>Em analise</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Plano de acao</h2></div>
                <div class="content-card">
                    <div class="action-pill">What: corrigir protecao</div>
                    <div class="action-pill">Why: eliminar risco</div>
                    <div class="action-pill">Where: quadro principal</div>
                    <div class="action-pill">When: 15 dias</div>
                    <div class="action-pill">Who: manutencao</div>
                    <div class="action-pill">How: substituicao e teste</div>
                    <div class="action-pill">How much: R$ 0,00</div>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Assinaturas</h2></div>
                <div class="signature-grid">
                    <div class="signature-box avoid-break">
                        <strong>Responsavel tecnico</strong>
                        <div class="signature-line"><?php echo esc($responsible_name); ?></div>
                    </div>
                    <div class="signature-box avoid-break">
                        <strong>Cliente</strong>
                        <div class="signature-line"><?php echo esc($client_name); ?></div>
                    </div>
                </div>
            </div>

            <div class="section-block">
                <div class="section-heading"><h2>Anexos e feedback</h2></div>
                <div class="content-card">
                    <p class="muted mb-2">Arquivos emitidos, revisoes e retornos do cliente ficam vinculados ao documento.</p>
                    <?php if ($feedbacks) { ?>
                        <?php foreach ($feedbacks as $feedback) { ?>
                            <div class="timeline-item">
                                <strong><?php echo esc($feedback->visitor_label ?: 'Visitante'); ?></strong>
                                <div class="small muted"><?php echo esc($feedback->created_at ?? '-'); ?> | <?php echo esc($feedback->action ?? 'comment'); ?></div>
                                <div><?php echo nl2br(esc($feedback->comment ?? '')); ?></div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="muted">Sem comentarios registrados.</div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <section class="doc-page">
            <div class="doc-header">
                <div class="section-title">Resumo e autenticidade</div>
            </div>
            <div class="content-card">
                <table class="avoid-break">
                    <tr><th>Codigo unico</th><td><?php echo esc($document_code); ?></td></tr>
                    <tr><th>Hash</th><td><?php echo esc($document_hash ?: '-'); ?></td></tr>
                    <tr><th>Validacao publica</th><td><?php echo esc($public_validation_url ?: 'Nao disponivel'); ?></td></tr>
                    <tr><th>Compartilhamento</th><td><?php echo esc($share_url ?: 'Nao configurado'); ?></td></tr>
                    <tr><th>Observacao</th><td>Este documento mostra apenas informacoes permitidas para compartilhamento e validacao.</td></tr>
                </table>
            </div>

            <div class="page-footer-spacer"></div>
            <div class="doc-footer">
                <div class="footer-note">
                    <span><?php echo esc($client_name); ?> - <?php echo esc($number); ?> / <?php echo esc($revision); ?></span>
                    <span><?php echo esc($variant_title); ?></span>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
