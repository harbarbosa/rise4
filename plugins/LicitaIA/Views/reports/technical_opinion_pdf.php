<?php
$opportunity = $opportunity ?? null;
if (!$opportunity) {
    return;
}

$documents = is_array($documents ?? null) ? $documents : array();
$checklist_items = is_array($checklist_items ?? null) ? $checklist_items : array();
$checklist_progress = is_array($checklist_progress ?? null) ? $checklist_progress : array();
$ai_requirements = is_array($ai_requirements ?? null) ? $ai_requirements : array();
$ai_risks = is_array($ai_risks ?? null) ? $ai_risks : array();
$decision_options = is_array($decision_options ?? null) ? $decision_options : array();

$ai_summary = trim((string) ($ai_summary ?? ''));
$ai_recommendation = trim((string) ($ai_recommendation ?? ''));
$technical_score = (float) ($technical_score ?? 0);
$risk_level = trim((string) ($risk_level ?? ''));
$recommendation = trim((string) ($recommendation ?? ''));

$normalized_recommendation = $recommendation;
if ($normalized_recommendation === 'analisar_melhor') {
    $normalized_recommendation = 'analyze_better';
}
if ($normalized_recommendation === 'nao_participar') {
    $normalized_recommendation = 'not_participate';
}
if ($normalized_recommendation === 'participar') {
    $normalized_recommendation = 'participate';
}

$location = trim((string) ($opportunity->city ?? ''));
if ($location !== '' && !empty($opportunity->state)) {
    $location .= ' / ' . $opportunity->state;
} elseif ($location === '' && !empty($opportunity->state)) {
    $location = $opportunity->state;
} elseif ($location === '') {
    $location = '-';
}

$render_list = function ($items, $empty_label = '-') {
    $items = is_array($items) ? $items : array();
    if (empty($items)) {
        return '<div class="text-muted">' . esc($empty_label) . '</div>';
    }

    $html = '<ul class="mb-0 ps-3">';
    foreach ($items as $item) {
        if (is_array($item)) {
            $item = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $item = trim((string) $item);
        if ($item === '') {
            continue;
        }
        $html .= '<li>' . esc($item) . '</li>';
    }
    $html .= '</ul>';

    return $html;
};

$render_requirement_section = function ($label, $items) use ($render_list) {
    $items = is_array($items) ? $items : array();
    if (empty($items)) {
        return '';
    }

    return '<div class="licitaia-report-subsection">'
        . '<div class="licitaia-report-subtitle">' . esc($label) . '</div>'
        . $render_list($items)
        . '</div>';
};
?>

<style>
    .licitaia-report {
        font-size: 13px;
        line-height: 1.5;
        color: #1f2937;
    }
    .licitaia-report .licitaia-report-section {
        border: 1px solid #dbe1e8;
        border-radius: 8px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .licitaia-report .licitaia-report-header {
        background: #f8fafc;
        padding: 12px 16px;
        font-weight: 700;
        border-bottom: 1px solid #dbe1e8;
    }
    .licitaia-report .licitaia-report-body {
        padding: 14px 16px;
    }
    .licitaia-report .licitaia-report-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .licitaia-report .licitaia-report-muted {
        color: #6b7280;
    }
    .licitaia-report .licitaia-report-grid {
        width: 100%;
        border-collapse: collapse;
    }
    .licitaia-report .licitaia-report-grid td,
    .licitaia-report .licitaia-report-grid th {
        border: 1px solid #dbe1e8;
        padding: 8px 10px;
        vertical-align: top;
    }
    .licitaia-report .licitaia-report-grid th {
        background: #f8fafc;
        width: 24%;
        text-align: left;
        font-weight: 600;
    }
    .licitaia-report .licitaia-report-subsection {
        margin-bottom: 12px;
        padding: 10px 12px;
        border: 1px solid #eef2f7;
        border-radius: 6px;
        background: #fff;
    }
    .licitaia-report .licitaia-report-subtitle {
        font-weight: 600;
        margin-bottom: 6px;
    }
    .licitaia-report .licitaia-report-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        margin-right: 6px;
        margin-bottom: 4px;
        border: 1px solid transparent;
    }
    .licitaia-report .pill-success { background: #e8f7ee; color: #166534; }
    .licitaia-report .pill-warning { background: #fff7e6; color: #a16207; }
    .licitaia-report .pill-danger { background: #fdecec; color: #991b1b; }
    .licitaia-report .pill-secondary { background: #eef2f7; color: #334155; }
    .licitaia-report .decision-box {
        border: 1px solid #dbe1e8;
        border-radius: 6px;
        padding: 10px 12px;
        margin-bottom: 8px;
    }
    .licitaia-report .decision-box.selected {
        border-color: #2563eb;
        background: #eff6ff;
    }
    .licitaia-report .signature-box {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #dbe1e8;
    }
    .licitaia-report .signature-line {
        margin-top: 42px;
        border-top: 1px solid #111827;
        text-align: center;
        padding-top: 6px;
        width: 55%;
    }
</style>

<div class="licitaia-report">
    <div class="licitaia-report-section">
        <div class="licitaia-report-header">1. Dados da oportunidade</div>
        <div class="licitaia-report-body">
            <div class="licitaia-report-title"><?php echo esc($opportunity->title ?: '-'); ?></div>
            <div class="licitaia-report-muted"><?php echo esc($opportunity->public_body ?: '-'); ?></div>
            <table class="licitaia-report-grid" style="margin-top: 12px;">
                <tr>
                    <th>Orgão público</th>
                    <td><?php echo esc($opportunity->public_body ?: $opportunity->public_agency ?: '-'); ?></td>
                    <th>Número do edital / processo</th>
                    <td><?php echo esc(trim((string) ($opportunity->edital_number ?? '')) ?: '-'); ?><?php echo !empty($opportunity->process_number) ? ' / ' . esc($opportunity->process_number) : ''; ?></td>
                </tr>
                <tr>
                    <th>Modalidade</th>
                    <td><?php echo esc($opportunity->modality ?: '-'); ?></td>
                    <th>Valor estimado</th>
                    <td><?php echo $opportunity->estimated_value !== null && $opportunity->estimated_value !== '' ? 'R$ ' . number_format((float) $opportunity->estimated_value, 2, ',', '.') : '-'; ?></td>
                </tr>
                <tr>
                    <th>Data de publicação</th>
                    <td><?php echo esc(!empty($opportunity->publication_date) ? format_to_date($opportunity->publication_date, false) : '-'); ?></td>
                    <th>Data de abertura</th>
                    <td><?php echo esc(!empty($opportunity->opening_date) ? format_to_date($opportunity->opening_date, false) : '-'); ?></td>
                </tr>
                <tr>
                    <th>Prazo de envio</th>
                    <td><?php echo esc(!empty($opportunity->submission_deadline) ? format_to_date($opportunity->submission_deadline, false) : '-'); ?></td>
                    <th>Cidade / UF</th>
                    <td><?php echo esc($location); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">2. Resumo executivo</div>
        <div class="licitaia-report-body">
            <?php echo nl2br(esc($ai_summary !== '' ? $ai_summary : ($opportunity->ai_summary ?: '-'))); ?>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">3. Exigencias e pontos analisados</div>
        <div class="licitaia-report-body">
            <?php
            echo $render_requirement_section('Exigencias tecnicas', get_array_value($ai_requirements, 'technical_requirements'));
            echo $render_requirement_section('Exigencias de habilitacao', get_array_value($ai_requirements, 'habilitation_requirements'));
            echo $render_requirement_section('Documentos necessarios', get_array_value($ai_requirements, 'documents_required'));
            echo $render_requirement_section('Prazos relevantes', get_array_value($ai_requirements, 'deadlines'));
            echo $render_requirement_section('Pontos financeiros', get_array_value($ai_requirements, 'financial_points'));
            echo $render_requirement_section('Pontos operacionais', get_array_value($ai_requirements, 'operational_points'));
            echo $render_requirement_section('Clausulas restritivas', get_array_value($ai_requirements, 'restrictive_clauses'));
            ?>
            <?php if (empty($ai_requirements)) { ?>
                <div class="text-muted">Nao ha detalhes estruturados de IA disponiveis para esta oportunidade.</div>
            <?php } ?>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">4. Riscos identificados</div>
        <div class="licitaia-report-body">
            <?php echo $render_list($ai_risks, 'Nenhum risco identificado.'); ?>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">5. Documentos vinculados</div>
        <div class="licitaia-report-body">
            <table class="licitaia-report-grid">
                <tr>
                    <th>Documento</th>
                    <th>Status</th>
                    <th>Texto extraido</th>
                </tr>
                <?php if (!empty($documents)) { ?>
                    <?php foreach ($documents as $document) { ?>
                        <tr>
                            <td><?php echo esc($document->original_file_name ?: $document->file_name ?: '-'); ?></td>
                            <td><?php echo esc($document->status ?: '-'); ?></td>
                            <td><?php echo trim((string) ($document->extracted_text ?? '')) !== '' ? 'Sim' : 'Nao'; ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">Nenhum documento vinculado.</td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">6. Checklist documental</div>
        <div class="licitaia-report-body">
            <table class="licitaia-report-grid">
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Observacoes</th>
                    <th>Documento vinculado</th>
                </tr>
                <?php if (!empty($checklist_items)) { ?>
                    <?php foreach ($checklist_items as $item) { ?>
                        <tr>
                            <td><?php echo esc($item->item_name_snapshot ?: $item->checklist_item_name ?: '-'); ?></td>
                            <td><?php echo esc(app_lang('licitaia_checklist_status_' . $item->status) ?: ($item->status ?: '-')); ?></td>
                            <td><?php echo esc($item->notes ?: '-'); ?></td>
                            <td><?php echo esc($item->document_original_file_name ?: $item->document_file_name ?: '-'); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Checklist ainda nao foi criado.</td>
                    </tr>
                <?php } ?>
            </table>

            <div style="margin-top: 10px;" class="licitaia-report-muted">
                Progresso do checklist: <?php echo (int) get_array_value($checklist_progress, 'percent', 0); ?>%
            </div>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">7. Recomendacao final</div>
        <div class="licitaia-report-body">
            <div style="margin-bottom: 12px;"><?php echo nl2br(esc($ai_recommendation !== '' ? $ai_recommendation : ($opportunity->ai_recommendation ?: '-'))); ?></div>

            <table class="licitaia-report-grid" style="margin-bottom: 12px;">
                <tr>
                    <th>Pontuacao tecnica</th>
                    <td><?php echo number_format($technical_score, 2, ',', '.'); ?></td>
                    <th>Nivel de risco</th>
                    <td><?php echo esc($risk_level ?: '-'); ?></td>
                </tr>
            </table>

            <div class="decision-box<?php echo $normalized_recommendation === 'participate' ? ' selected' : ''; ?>">
                <strong><?php echo esc(get_array_value($decision_options, 'participate', 'Participar')); ?></strong>
            </div>
            <div class="decision-box<?php echo $normalized_recommendation === 'not_participate' ? ' selected' : ''; ?>">
                <strong><?php echo esc(get_array_value($decision_options, 'not_participate', 'Nao participar')); ?></strong>
            </div>
            <div class="decision-box<?php echo $normalized_recommendation === 'analyze_better' ? ' selected' : ''; ?>">
                <strong><?php echo esc(get_array_value($decision_options, 'analyze_better', 'Analisar melhor')); ?></strong>
            </div>
        </div>
    </div>

    <div class="licitaia-report-section">
        <div class="licitaia-report-header">8. Assinatura / responsavel</div>
        <div class="licitaia-report-body">
            <div class="licitaia-report-muted" style="margin-bottom: 12px;">Responsavel interno: <?php echo esc($opportunity->responsible_name ?: '-'); ?></div>
            <div class="signature-box">
                <div class="signature-line"><?php echo esc($opportunity->responsible_name ?: ''); ?></div>
            </div>
        </div>
    </div>
</div>
