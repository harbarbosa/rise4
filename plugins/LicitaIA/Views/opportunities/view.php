<?php
$opportunity = $opportunity ?? (object) array();
$documents = $documents ?? array();
$tasks = $tasks ?? array();
$checklist_items = $checklist_items ?? array();
$checklist_progress = $checklist_progress ?? array('percent' => 0, 'total' => 0, 'done' => 0, 'pending' => 0);
$checklist_documents_dropdown = $checklist_documents_dropdown ?? array();
$ai_logs = $ai_logs ?? array();
$latest_report = $latest_report ?? null;
$status_dropdown = $status_dropdown ?? array();
$responsible_dropdown = $responsible_dropdown ?? array();

include_once PLUGINPATH . 'LicitaIA/Views/shared/ui.php';

$summary_cards = array(
    array(
        'label' => app_lang('licitaia_status'),
        'value' => esc(app_lang('licitaia_status_' . ($opportunity->status ?? 'new'))),
        'badge' => licitaia_status_badge_class($opportunity->status ?? 'new'),
        'icon' => 'flag',
    ),
    array(
        'label' => app_lang('licitaia_risk_level'),
        'value' => esc(ucfirst((string) ($opportunity->risk_level ?? '-'))),
        'badge' => licitaia_risk_badge_class($opportunity->risk_level ?? ''),
        'icon' => 'shield',
    ),
    array(
        'label' => app_lang('licitaia_recommendation_result'),
        'value' => esc(ucfirst(str_replace('_', ' ', (string) ($opportunity->recommendation ?? '-')))),
        'badge' => licitaia_recommendation_badge_class($opportunity->recommendation ?? ''),
        'icon' => 'thumbs-up',
    ),
    array(
        'label' => app_lang('licitaia_technical_score'),
        'value' => $opportunity->technical_score !== null ? number_format((float) $opportunity->technical_score, 2, ',', '.') : '-',
        'badge' => 'primary',
        'icon' => 'bar-chart-2',
    ),
);

$document_status_badges = array(
    'uploaded' => 'secondary',
    'text_extracted' => 'success',
    'pending_extraction' => 'warning',
    'processing' => 'info',
    'failed' => 'danger',
);

$checklist_status_dropdown = array(
    'pending' => app_lang('licitaia_checklist_status_pending'),
    'separated' => app_lang('licitaia_checklist_status_separated'),
    'validated' => app_lang('licitaia_checklist_status_validated'),
    'sent' => app_lang('licitaia_checklist_status_sent'),
    'not_applicable' => app_lang('licitaia_checklist_status_not_applicable'),
);

?>

<div class="page-content project-details-view clearfix">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
    <div class="card">
        <div class="page-title clearfix">
            <div class="d-flex flex-column">
                <h1 class="mb-1"><?php echo esc($opportunity->title ?: app_lang('licitaia_opportunity')); ?></h1>
                <div class="text-muted">
                    <?php echo esc($opportunity->public_body ?: '-'); ?>
                    <?php if (!empty($opportunity->edital_number)) { ?> · <?php echo esc($opportunity->edital_number); ?><?php } ?>
                </div>
            </div>
            <div class="title-button-group d-flex flex-wrap gap-2">
                <?php if ($can_manage ?? false) { ?>
                    <?php echo modal_anchor(get_uri('licitaia/opportunities/modal_form/' . (int) $opportunity->id), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-default')); ?>
                    <?php echo js_anchor("<i data-feather='cpu' class='icon-16'></i> " . app_lang('licitaia_analyze_with_ai'), array('class' => 'btn btn-primary js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-action-url' => get_uri('licitaia/ai_analysis/analyze/' . (int) $opportunity->id), 'data-ai-loading' => '1')); ?>
                    <?php echo js_anchor("<i data-feather='refresh-cw' class='icon-16'></i> " . app_lang('licitaia_reanalyze'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-action-url' => get_uri('licitaia/ai_analysis/reanalyze/' . (int) $opportunity->id), 'data-ai-loading' => '1')); ?>
                    <?php echo anchor(get_uri('licitaia/reports/technical_opinion/' . (int) $opportunity->id), "<i data-feather='file-text' class='icon-16'></i> " . app_lang('licitaia_generate_opinion'), array('class' => 'btn btn-default')); ?>
                    <?php echo js_anchor("<i data-feather='check-square' class='icon-16'></i> " . app_lang('licitaia_create_checklist'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-action-url' => get_uri('licitaia/checklist/create_for_opportunity/' . (int) $opportunity->id))); ?>
                <?php } ?>
                <?php if ($can_create_tasks ?? false) { ?>
                    <?php echo js_anchor("<i data-feather='briefcase' class='icon-16'></i> " . app_lang('licitaia_create_analysis_task'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'analysis', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/analysis'))); ?>
                    <?php echo js_anchor("<i data-feather='folder' class='icon-16'></i> " . app_lang('licitaia_create_document_task'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'documentation', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/documentation'))); ?>
                    <?php echo js_anchor("<i data-feather='file-plus' class='icon-16'></i> " . app_lang('licitaia_create_proposal_task'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'proposal', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/proposal'))); ?>
                    <?php echo js_anchor("<i data-feather='clock' class='icon-16'></i> " . app_lang('licitaia_create_session_task'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'session', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/session'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <?php if ($message = session()->getFlashdata('success_message')) { ?>
                <div class="alert alert-success"><?php echo esc($message); ?></div>
            <?php } ?>
            <?php if ($message = session()->getFlashdata('error_message')) { ?>
                <div class="alert alert-danger"><?php echo esc($message); ?></div>
            <?php } ?>

            <div class="row g-3 mb-4">
                <?php foreach ($summary_cards as $card) { ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted"><?php echo esc($card['label']); ?></div>
                                    <div class="font-26 fw-bold"><?php echo $card['value']; ?></div>
                                </div>
                                <div class="avatar avatar-md bg-<?php echo esc($card['badge']); ?> text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i data-feather="<?php echo esc($card['icon']); ?>" class="icon-20"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php echo licitaia_badge_html(app_lang('licitaia_status_' . ($opportunity->status ?? 'new')), licitaia_status_badge_class($opportunity->status ?? 'new')); ?>
                <?php echo licitaia_badge_html(app_lang('licitaia_ai_' . ($opportunity->ai_status ?? 'pending')), 'light text-dark border'); ?>
                <?php echo licitaia_badge_html(ucfirst((string) ($opportunity->risk_level ?? '-')), licitaia_risk_badge_class($opportunity->risk_level ?? ''), 'shield'); ?>
                <?php echo licitaia_badge_html(ucfirst(str_replace('_', ' ', (string) ($opportunity->recommendation ?? '-'))), licitaia_recommendation_badge_class($opportunity->recommendation ?? ''), 'thumbs-up'); ?>
                <?php if (!empty($opportunity->submission_deadline)) { ?>
                    <?php echo licitaia_badge_html(app_lang('licitaia_deadline') . ': ' . esc(format_to_date($opportunity->submission_deadline, false)), 'light text-dark border', 'calendar'); ?>
                <?php } ?>
            </div>

            <ul class="nav nav-tabs bg-white title rounded classic mb20 scrollable-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#licitaia-tab-general" role="tab"><?php echo app_lang('licitaia_general_data'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#licitaia-tab-documents" role="tab"><?php echo app_lang('licitaia_documents'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#licitaia-tab-ai" role="tab"><?php echo app_lang('licitaia_ai_analysis'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#licitaia-tab-checklist" role="tab"><?php echo app_lang('licitaia_checklist'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#licitaia-tab-tasks" role="tab"><?php echo app_lang('licitaia_tasks'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#licitaia-tab-report" role="tab"><?php echo app_lang('licitaia_technical_opinion'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#licitaia-tab-history" role="tab"><?php echo app_lang('licitaia_history'); ?></a>
                </li>
            </ul>

            <div class="tab-content p-0 pt-3">
                <div class="tab-pane fade show active" id="licitaia-tab-general" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_general_data'); ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_public_body'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($opportunity->public_body ?: '-'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_modality'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($opportunity->modality ?: '-'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_edital_number'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($opportunity->edital_number ?: '-'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_process_number'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($opportunity->process_number ?: '-'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_city'); ?> / <?php echo app_lang('licitaia_state'); ?></div>
                                            <div class="fw-semibold"><?php echo esc(trim((string) ($opportunity->city ?: '-'))); ?><?php echo !empty($opportunity->state) ? ' / ' . esc($opportunity->state) : ''; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_estimated_value'); ?></div>
                                            <div class="fw-semibold"><?php echo $opportunity->estimated_value !== null ? to_currency((float) $opportunity->estimated_value) : '-'; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_opening_date'); ?></div>
                                            <div class="fw-semibold"><?php echo esc(!empty($opportunity->opening_date) ? format_to_date($opportunity->opening_date, false) : '-'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_deadline'); ?></div>
                                            <div class="fw-semibold"><?php echo esc(!empty($opportunity->submission_deadline) ? format_to_date($opportunity->submission_deadline, false) : '-'); ?></div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_object'); ?></div>
                                            <div><?php echo nl2br(esc($opportunity->object ?: '-')); ?></div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_description'); ?></div>
                                            <div><?php echo nl2br(esc($opportunity->description ?: '-')); ?></div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_original_link'); ?></div>
                                            <div>
                                                <?php if (!empty($opportunity->original_link)) { ?>
                                                    <a href="<?php echo esc($opportunity->original_link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc($opportunity->original_link); ?></a>
                                                <?php } else { ?>
                                                    -
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_details'); ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_responsible'); ?></div>
                                        <div class="fw-semibold"><?php echo esc($opportunity->responsible_name ?: '-'); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_source'); ?></div>
                                        <div class="fw-semibold"><?php echo esc($opportunity->source_name ?: '-'); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_ai_status'); ?></div>
                                        <div class="fw-semibold"><?php echo esc(app_lang('licitaia_ai_' . ($opportunity->ai_status ?? 'pending'))); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_update_status'); ?></div>
                                        <?php if ($can_manage ?? false) { ?>
                                            <?php echo form_open(get_uri('licitaia/opportunities/update_status'), array('id' => 'licitaia-opportunity-status-form', 'class' => 'general-form', 'role' => 'form')); ?>
                                                <?php echo form_hidden('id', (string) $opportunity->id); ?>
                                                <div class="mb-2">
                                                    <?php echo form_dropdown('status', $status_dropdown, $opportunity->status ?? 'new', 'class="form-control select2"'); ?>
                                                </div>
                                                <div class="mb-2">
                                                    <?php echo form_dropdown('responsible_user_id', $responsible_dropdown, $opportunity->responsible_user_id ?? '', 'class="form-control select2"'); ?>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100"><?php echo app_lang('save'); ?></button>
                                            <?php echo form_close(); ?>
                                        <?php } else { ?>
                                            <div class="fw-semibold"><?php echo esc($opportunity->status ?: '-'); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="licitaia-tab-documents" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_upload_document'); ?></h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($can_manage ?? false) { ?>
                                        <?php echo form_open_multipart(get_uri('licitaia/opportunities/upload_document/' . (int) $opportunity->id), array('class' => 'general-form')); ?>
                                            <div class="mb-3">
                                                <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.bmp,.webp" required />
                                                <small class="text-muted"><?php echo app_lang('licitaia_document_upload_help'); ?></small>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i data-feather="upload" class="icon-16"></i> <?php echo app_lang('save'); ?>
                                            </button>
                                        <?php echo form_close(); ?>
                                    <?php } else { ?>
                                        <div class="text-muted"><?php echo app_lang('forbidden'); ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_documents'); ?></h4>
                                </div>
                                <div class="table-responsive">
                                    <table id="licitaia-documents-table" class="table table-hover mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th><?php echo app_lang('file'); ?></th>
                                                <th><?php echo app_lang('status'); ?></th>
                                                <th><?php echo app_lang('created_at'); ?></th>
                                                <th class="text-end"><?php echo app_lang('actions'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($documents)) { ?>
                                                <?php foreach ($documents as $document) { ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?php echo esc($document->original_file_name ?: $document->file_name ?: '-'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo esc(get_array_value($document_status_badges, $document->status, 'secondary')); ?>">
                                                                <?php echo esc(app_lang('licitaia_document_status_' . $document->status) ?: $document->status); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo esc($document->created_at ?: '-'); ?></td>
                                                        <td class="text-end">
                                                            <?php echo anchor(get_uri('licitaia/opportunities/download_document/' . (int) $document->id), "<i data-feather='download' class='icon-16'></i>", array('class' => 'action-icon', 'title' => app_lang('licitaia_document_download'), 'target' => '_blank', 'rel' => 'noopener noreferrer')); ?>
                                                            <?php if ($can_manage ?? false) { ?>
                                                                <?php echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'delete action-icon text-danger', 'title' => app_lang('licitaia_document_delete'), 'data-id' => (int) $document->id, 'data-action-url' => get_uri('licitaia/opportunities/delete_document/' . (int) $document->id), 'data-action' => 'delete-confirmation')); ?>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr><td colspan="4" class="text-center text-muted py-4"><?php echo app_lang('licitaia_no_documents'); ?></td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="licitaia-tab-ai" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <h4 class="mb-0"><?php echo app_lang('licitaia_ai_analysis'); ?></h4>
                                        <?php if ($can_manage ?? false) { ?>
                                            <div class="d-flex flex-wrap gap-2">
                                        <?php echo js_anchor("<i data-feather='refresh-cw' class='icon-16'></i> " . app_lang('licitaia_reanalyze'), array('class' => 'btn btn-default btn-sm js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-action-url' => get_uri('licitaia/ai_analysis/reanalyze/' . (int) $opportunity->id), 'data-ai-loading' => '1')); ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_ai_summary'); ?></div>
                                        <div><?php echo nl2br(esc($opportunity->ai_summary ?: '-')); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_ai_recommendation'); ?></div>
                                        <div><?php echo nl2br(esc($opportunity->ai_recommendation ?: '-')); ?></div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_technical_score'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($opportunity->technical_score !== null ? number_format((float) $opportunity->technical_score, 2, ',', '.') : '-'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_risk_level'); ?></div>
                                            <div class="fw-semibold"><?php echo esc(ucfirst((string) ($opportunity->risk_level ?? '-'))); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_recommendation_result'); ?></div>
                                            <div class="fw-semibold"><?php echo esc(ucfirst(str_replace('_', ' ', (string) ($opportunity->recommendation ?? '-')))); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_ai_requirements'); ?></h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $ai_requirements = array();
                                    if (!empty($opportunity->ai_requirements)) {
                                        $decoded_requirements = json_decode((string) $opportunity->ai_requirements, true);
                                        if (is_array($decoded_requirements)) {
                                            $ai_requirements = $decoded_requirements;
                                        }
                                    }
                                    $ai_risks = array();
                                    if (!empty($opportunity->ai_risks)) {
                                        $decoded_risks = json_decode((string) $opportunity->ai_risks, true);
                                        if (is_array($decoded_risks)) {
                                            $ai_risks = $decoded_risks;
                                        }
                                    }
                                    $requirements_labels = array(
                                        'technical_requirements' => app_lang('licitaia_technical_requirements'),
                                        'habilitation_requirements' => app_lang('licitaia_habilitation_requirements'),
                                        'documents_required' => app_lang('licitaia_documents_required'),
                                        'deadlines' => app_lang('licitaia_deadlines'),
                                        'financial_points' => app_lang('licitaia_financial_points'),
                                        'operational_points' => app_lang('licitaia_operational_points'),
                                        'restrictive_clauses' => app_lang('licitaia_restrictive_clauses'),
                                    );
                                    ?>
                                    <?php if (!empty($ai_risks)) { ?>
                                        <div class="mb-4">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_ai_risks'); ?></div>
                                            <ul class="mb-0 ps-3">
                                                <?php foreach ($ai_risks as $risk_item) { ?>
                                                    <li><?php echo esc(is_array($risk_item) ? json_encode($risk_item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $risk_item); ?></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                    <?php foreach ($ai_requirements as $label => $items) { ?>
                                        <div class="mb-3">
                                            <div class="fw-semibold mb-1"><?php echo esc(get_array_value($requirements_labels, $label, $label)); ?></div>
                                            <ul class="mb-0 ps-3">
                                                <?php foreach ((array) $items as $item) { ?>
                                                    <li><?php echo esc(is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $item); ?></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                    <?php if (empty($ai_requirements) && empty($ai_risks)) { ?>
                                        <div class="text-muted"><?php echo app_lang('licitaia_empty_state'); ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="licitaia-tab-checklist" role="tabpanel">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_documental_checklist'); ?></h4>
                            <span class="badge bg-light text-dark border"><?php echo (int) get_array_value($checklist_progress, 'percent', 0); ?>%</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                <div class="text-muted"><?php echo (int) get_array_value($checklist_progress, 'done', 0); ?>/<?php echo (int) get_array_value($checklist_progress, 'total', 0); ?> <?php echo app_lang('licitaia_checklist_items_done'); ?></div>
                                <?php if ($can_manage ?? false) { ?>
                                    <?php echo js_anchor("<i data-feather='check-square' class='icon-16'></i> " . app_lang('licitaia_create_checklist'), array('class' => 'btn btn-default js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-action-url' => get_uri('licitaia/checklist/create_for_opportunity/' . (int) $opportunity->id))); ?>
                                <?php } ?>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo (int) get_array_value($checklist_progress, 'percent', 0); ?>%;" aria-valuenow="<?php echo (int) get_array_value($checklist_progress, 'percent', 0); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($checklist_items)) { ?>
                        <?php foreach ($checklist_items as $item) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                        <div>
                                            <div class="fw-semibold"><?php echo esc($item->item_name_snapshot ?: $item->checklist_item_name ?: '-'); ?></div>
                                            <div class="text-muted small"><?php echo esc($item->category ?: '-'); ?></div>
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php echo licitaia_badge_html(app_lang('licitaia_required') . ': ' . (!empty($item->is_required) ? app_lang('yes') : app_lang('no')), 'light text-dark border'); ?>
                                            <?php echo licitaia_badge_html(app_lang('licitaia_checklist_status_' . $item->status) ?: ($item->status ?: '-'), 'secondary'); ?>
                                        </div>
                                    </div>
                                    <div class="mb-2 text-muted small"><?php echo esc($item->description ?: '-'); ?></div>
                                    <?php if ($can_manage ?? false) { ?>
                                        <?php echo form_open(get_uri('licitaia/checklist/update_opportunity_item/' . (int) $item->id), array('class' => 'general-form js-opportunity-checklist-form')); ?>
                                            <?php echo form_hidden('id', (string) $item->id); ?>
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label><?php echo app_lang('status'); ?></label>
                                                    <?php echo form_dropdown('status', $checklist_status_dropdown, $item->status ?: 'pending', 'class="form-control select2"'); ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <label><?php echo app_lang('licitaia_document'); ?></label>
                                                    <?php echo form_dropdown('document_id', $checklist_documents_dropdown, $item->document_id ?? '', 'class="form-control select2"'); ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <label><?php echo app_lang('notes'); ?></label>
                                                    <textarea name="notes" class="form-control" rows="2"><?php echo esc($item->notes ?: ''); ?></textarea>
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary w-100"><?php echo app_lang('save'); ?></button>
                                                </div>
                                            </div>
                                        <?php echo form_close(); ?>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="card">
                            <div class="card-body text-muted text-center py-4"><?php echo app_lang('licitaia_empty_state'); ?></div>
                        </div>
                    <?php } ?>
                </div>

                <div class="tab-pane fade" id="licitaia-tab-tasks" role="tabpanel">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_tasks'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-md-3">
                                    <?php echo js_anchor("<i data-feather='briefcase' class='icon-16'></i> " . app_lang('licitaia_create_analysis_task'), array('class' => 'btn btn-default w-100 js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'analysis', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/analysis'))); ?>
                                </div>
                                <div class="col-md-3">
                                    <?php echo js_anchor("<i data-feather='folder' class='icon-16'></i> " . app_lang('licitaia_create_document_task'), array('class' => 'btn btn-default w-100 js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'documentation', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/documentation'))); ?>
                                </div>
                                <div class="col-md-3">
                                    <?php echo js_anchor("<i data-feather='file-plus' class='icon-16'></i> " . app_lang('licitaia_create_proposal_task'), array('class' => 'btn btn-default w-100 js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'proposal', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/proposal'))); ?>
                                </div>
                                <div class="col-md-3">
                                    <?php echo js_anchor("<i data-feather='clock' class='icon-16'></i> " . app_lang('licitaia_create_session_task'), array('class' => 'btn btn-default w-100 js-opportunity-action', 'data-id' => (int) $opportunity->id, 'data-task-type' => 'session', 'data-action-url' => get_uri('licitaia/opportunities/create_task/' . (int) $opportunity->id . '/session'))); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th><?php echo app_lang('title'); ?></th>
                                        <th><?php echo app_lang('assigned_to'); ?></th>
                                        <th><?php echo app_lang('deadline'); ?></th>
                                        <th><?php echo app_lang('status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($tasks)) { ?>
                                        <?php foreach ($tasks as $task) { ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo esc($task->title ?: '-'); ?></td>
                                                <td><?php echo esc(trim((string) ($task->assigned_to_name ?? '')) ?: '-'); ?></td>
                                                <td><?php echo esc($task->deadline ?: '-'); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo esc($task->status_title ?: '-'); ?></span></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4"><?php echo app_lang('licitaia_empty_state'); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="licitaia-tab-report" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_technical_opinion'); ?></h4>
                                    <?php if ($can_manage ?? false) { ?>
                                        <?php echo anchor(get_uri('licitaia/reports/technical_opinion/' . (int) $opportunity->id), app_lang('licitaia_generate_opinion'), array('class' => 'btn btn-default btn-sm')); ?>
                                    <?php } ?>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($latest_report)) { ?>
                                        <div class="mb-3">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('title'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($latest_report->title ?: '-'); ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_generated_at'); ?></div>
                                            <div class="fw-semibold"><?php echo esc($latest_report->generated_at ?: '-'); ?></div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php echo anchor(get_uri('licitaia/reports/download/' . (int) $opportunity->id), "<i data-feather='download' class='icon-16'></i> " . app_lang('licitaia_download_pdf'), array('class' => 'btn btn-primary')); ?>
                                        </div>
                                    <?php } else { ?>
                                        <div class="text-muted"><?php echo app_lang('licitaia_empty_state'); ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="mb-0"><?php echo app_lang('licitaia_recommendation_result'); ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <?php echo licitaia_badge_html(app_lang('licitaia_recommendation_' . ($opportunity->recommendation ?? 'analisar_melhor')) ?: ucfirst((string) ($opportunity->recommendation ?? '-')), licitaia_recommendation_badge_class($opportunity->recommendation ?? ''), 'thumbs-up'); ?>
                                    </div>
                                    <div class="text-muted small text-uppercase fw-bold"><?php echo app_lang('licitaia_ai_recommendation'); ?></div>
                                    <div><?php echo nl2br(esc($opportunity->ai_recommendation ?: '-')); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="licitaia-tab-history" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_history'); ?></h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                    <th><?php echo app_lang('licitaia_type'); ?></th>
                                        <th><?php echo app_lang('status'); ?></th>
                                        <th><?php echo app_lang('provider'); ?></th>
                                        <th><?php echo app_lang('model'); ?></th>
                                        <th><?php echo app_lang('created_at'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ai_logs)) { ?>
                                        <?php foreach ($ai_logs as $log) { ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo esc($log->request_type ?: '-'); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo esc($log->status ?: '-'); ?></span></td>
                                                <td><?php echo esc($log->provider ?: '-'); ?></td>
                                                <td><?php echo esc($log->model_name ?: '-'); ?></td>
                                                <td><?php echo esc($log->created_at ?: '-'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4"><?php echo app_lang('licitaia_empty_state'); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="licitaia-ai-loading-overlay" style="display:none;position:fixed;inset:0;z-index:20000;background:rgba(15,23,42,0.72);backdrop-filter:blur(4px);">
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:24px;">
        <div class="card shadow-lg" style="max-width:520px;width:100%;border:0;">
            <div class="card-body text-center p-4 p-md-5">
                <div class="mb-4">
                    <span class="spinner-border text-primary" role="status" aria-hidden="true" style="width:3rem;height:3rem;"></span>
                </div>
                <h4 class="mb-2"><?php echo esc(app_lang('licitaia_ai_processing') ?: 'Processando IA'); ?></h4>
                <div id="licitaia-ai-loading-message" class="text-muted fs-5">...</div>
                <div class="small text-muted mt-3"><?php echo esc(app_lang('licitaia_ai_loading_hint') ?: 'Aguarde enquanto o sistema envia os documentos e monta a resposta.'); ?></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var licitaiaAiLoadingTimer = null;
        var licitaiaAiLoadingMessages = [
            "<?php echo esc(app_lang('licitaia_ai_loading_message_1') ?: 'Enviando para IA...'); ?>",
            "<?php echo esc(app_lang('licitaia_ai_loading_message_2') ?: 'Analisando documentos...'); ?>",
            "<?php echo esc(app_lang('licitaia_ai_loading_message_3') ?: 'Validando requisitos...'); ?>",
            "<?php echo esc(app_lang('licitaia_ai_loading_message_4') ?: 'Montando recomendacao...'); ?>",
            "<?php echo esc(app_lang('licitaia_ai_loading_message_5') ?: 'Finalizando processamento...'); ?>"
        ];
        var licitaiaAiLoadingIndex = 0;

        function updateLicitaiaAiLoadingMessage() {
            if (!licitaiaAiLoadingMessages.length) {
                return;
            }

            $("#licitaia-ai-loading-message").text(licitaiaAiLoadingMessages[licitaiaAiLoadingIndex % licitaiaAiLoadingMessages.length]);
            licitaiaAiLoadingIndex++;
        }

        function showLicitaiaAiLoading() {
            updateLicitaiaAiLoadingMessage();
            $("#licitaia-ai-loading-overlay").fadeIn(150);

            if (licitaiaAiLoadingTimer) {
                clearInterval(licitaiaAiLoadingTimer);
            }

            licitaiaAiLoadingTimer = setInterval(updateLicitaiaAiLoadingMessage, 1800);
        }

        function hideLicitaiaAiLoading() {
            if (licitaiaAiLoadingTimer) {
                clearInterval(licitaiaAiLoadingTimer);
                licitaiaAiLoadingTimer = null;
            }

            $("#licitaia-ai-loading-overlay").fadeOut(150);
        }

        $(".select2").select2();

        $("#licitaia-opportunity-status-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });

        $(".js-opportunity-checklist-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });

        $(document).on("click", ".js-opportunity-action", function (e) {
            e.preventDefault();
            var $button = $(this);
            var isAiAction = $button.attr("data-ai-loading") === "1";

            if (isAiAction) {
                showLicitaiaAiLoading();
            }

            appAjaxRequest({
                url: $button.data("action-url"),
                type: "post",
                dataType: "json",
                data: {
                    id: $button.data("id"),
                    task_type: $button.data("task-type")
                },
                success: function (response) {
                        if (response && response.success) {
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                                return;
                            }
                            if (isAiAction) {
                                hideLicitaiaAiLoading();
                            }
                            window.location.reload();
                            return;
                        }

                        appAlert.error((response && response.message) ? response.message : "<?php echo esc(app_lang('error_occurred')); ?>");
                        if (isAiAction) {
                            hideLicitaiaAiLoading();
                        }
                },
                error: function () {
                    appAlert.error("<?php echo esc(app_lang('error_occurred')); ?>");
                    if (isAiAction) {
                        hideLicitaiaAiLoading();
                    }
                }
            });
        });

        $(document).on("click", "#licitaia-documents-table a.delete[data-action='delete-confirmation']", function (e) {
            e.preventDefault();
            var $button = $(this);
            var actionUrl = $button.attr("data-action-url");
            var documentId = $button.attr("data-id");

            if (!actionUrl) {
                appAlert.error("<?php echo esc(app_lang('error_occurred')); ?>");
                return false;
            }

            $button.appConfirmation({
                title: "<?php echo esc(app_lang('are_you_sure')); ?>",
                btnConfirmLabel: "<?php echo esc(app_lang('yes')); ?>",
                btnCancelLabel: "<?php echo esc(app_lang('no')); ?>",
                onConfirm: function () {
                    appAjaxRequest({
                        url: actionUrl,
                        type: "post",
                        dataType: "json",
                        data: {
                            id: documentId
                        },
                        success: function (response) {
                            if (response && response.success) {
                                window.location.reload();
                                return;
                            }

                            appAlert.error((response && response.message) ? response.message : "<?php echo esc(app_lang('error_occurred')); ?>");
                        },
                        error: function () {
                            appAlert.error("<?php echo esc(app_lang('error_occurred')); ?>");
                        }
                    });
                }
            });

            return false;
        });
    });
</script>
