<?php
$filters = is_array($filters ?? null) ? $filters : array();
$filter_options = is_array($filter_options ?? null) ? $filter_options : array();
$summary = is_array($summary ?? null) ? $summary : array();
$reports = is_array($reports ?? null) ? $reports : array();
$can_export = !empty($can_export);

$document_types_dropdown = get_array_value($filter_options, 'document_types_dropdown') ?: array();
$employees_dropdown = get_array_value($filter_options, 'employees_dropdown') ?: array();
$suppliers_dropdown = get_array_value($filter_options, 'suppliers_dropdown') ?: array();
$document_status_dropdown = get_array_value($filter_options, 'document_status_dropdown') ?: array();
$portal_status_dropdown = get_array_value($filter_options, 'portal_status_dropdown') ?: array();

$expired_documents = get_array_value($reports, 'expired_documents') ?: array();
$expiring_30_documents = get_array_value($reports, 'expiring_30_documents') ?: array();
$documents_by_employee = get_array_value($reports, 'documents_by_employee') ?: array();
$documents_by_supplier = get_array_value($reports, 'documents_by_supplier') ?: array();
$portal_submissions = get_array_value($reports, 'portal_submissions') ?: array();
$pending_portal_submissions = get_array_value($reports, 'pending_portal_submissions') ?: array();
$expired_document_submissions = get_array_value($reports, 'expired_document_submissions') ?: array();

$summary_documents = get_array_value($summary, 'documents') ?: array();
$summary_submissions = get_array_value($summary, 'submissions') ?: array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb20">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="mb-0"><?php echo app_lang('ged_reports'); ?></h4>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" disabled>
                        <i data-feather="file-text" class="icon-16"></i> Exportar PDF
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                        <i data-feather="download" class="icon-16"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri('ged/reports'), array('method' => 'get', 'class' => 'general-form', 'role' => 'form')); ?>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de documento</label>
                        <?php echo form_dropdown('document_type_id', $document_types_dropdown, get_array_value($filters, 'document_type_id'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Funcionário</label>
                        <?php echo form_dropdown('employee_id', $employees_dropdown, get_array_value($filters, 'employee_id'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fornecedor</label>
                        <?php echo form_dropdown('supplier_id', $suppliers_dropdown, get_array_value($filters, 'supplier_id'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status do documento</label>
                        <?php echo form_dropdown('document_status', $document_status_dropdown, get_array_value($filters, 'document_status'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status no portal</label>
                        <?php echo form_dropdown('portal_status', $portal_status_dropdown, get_array_value($filters, 'portal_status'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vencimento início</label>
                        <input type="date" name="expiration_start" value="<?php echo esc(get_array_value($filters, 'expiration_start')); ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vencimento fim</label>
                        <input type="date" name="expiration_end" value="<?php echo esc(get_array_value($filters, 'expiration_end')); ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary me-1">Filtrar</button>
                        <a href="<?php echo get_uri('ged/reports'); ?>" class="btn btn-default">Limpar</a>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <div class="row g-3 mb20">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-off">Documentos vencidos</div>
                    <h3 class="mb0 text-danger"><?php echo (int) get_array_value($summary_documents, 'expired'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-off">Documentos vencendo em 30 dias</div>
                    <h3 class="mb0 text-warning"><?php echo (int) get_array_value($summary_documents, 'expiring_30'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-off">Envios com documentos vencidos</div>
                    <h3 class="mb0 text-danger"><?php echo (int) get_array_value($summary_submissions, 'with_expired_documents'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-off">Envios pendentes no portal</div>
                    <h3 class="mb0 text-info"><?php echo (int) get_array_value($summary_submissions, 'pending'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-header"><h5 class="mb-0">Legenda</h5></div>
        <div class="card-body">
            <?php foreach ($expiration_labels as $label) { ?>
                <span class="me-2 mb-2 d-inline-block"><?php echo $label; ?></span>
            <?php } ?>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-header"><h5 class="mb-0">1. Documentos vencidos</h5></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Funcionário</th>
                        <th>Fornecedor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($expired_documents)) { foreach ($expired_documents as $row) { ?>
                    <tr>
                        <td><?php echo esc($row->title ?: '-'); ?></td>
                        <td><?php echo esc($row->document_type_name ?: '-'); ?></td>
                        <td><?php echo esc($row->employee_name ?: '-'); ?></td>
                        <td><?php echo esc($row->supplier_name ?: '-'); ?></td>
                        <td><?php echo esc($row->expiration_date ? format_to_date($row->expiration_date, false) : '-'); ?></td>
                        <td><?php echo get_document_status_label(get_expiration_status($row->expiration_date ?? null)); ?></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-header"><h5 class="mb-0">2. Documentos vencendo em 30 dias</h5></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Funcionário</th>
                        <th>Fornecedor</th>
                        <th>Vencimento</th>
                        <th>Badge</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($expiring_30_documents)) { foreach ($expiring_30_documents as $row) { ?>
                    <tr>
                        <td><?php echo esc($row->title ?: '-'); ?></td>
                        <td><?php echo esc($row->document_type_name ?: '-'); ?></td>
                        <td><?php echo esc($row->employee_name ?: '-'); ?></td>
                        <td><?php echo esc($row->supplier_name ?: '-'); ?></td>
                        <td><?php echo esc($row->expiration_date ? format_to_date($row->expiration_date, false) : '-'); ?></td>
                        <td><?php echo get_expiration_badge($row->expiration_date ?? null); ?></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb20">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">3. Documentos por funcionário</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Funcionário</th>
                                <th>Total</th>
                                <th>Vencidos</th>
                                <th>Vencendo 30d</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($documents_by_employee)) { foreach ($documents_by_employee as $row) { ?>
                            <tr>
                                <td><?php echo esc($row->employee_name ?: '-'); ?></td>
                                <td><?php echo (int) $row->total_documents; ?></td>
                                <td><span class="badge bg-danger"><?php echo (int) $row->expired_documents; ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?php echo (int) $row->expiring_30_documents; ?></span></td>
                            </tr>
                        <?php } } else { ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">4. Documentos por fornecedor</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                                <th>Total</th>
                                <th>Vencidos</th>
                                <th>Vencendo 30d</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($documents_by_supplier)) { foreach ($documents_by_supplier as $row) { ?>
                            <tr>
                                <td><?php echo esc($row->supplier_name ?: '-'); ?></td>
                                <td><?php echo (int) $row->total_documents; ?></td>
                                <td><span class="badge bg-danger"><?php echo (int) $row->expired_documents; ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?php echo (int) $row->expiring_30_documents; ?></span></td>
                            </tr>
                        <?php } } else { ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-header"><h5 class="mb-0">5. Documentos enviados para fornecedores / portais</h5></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Fornecedor</th>
                        <th>Enviado em</th>
                        <th>Status portal</th>
                        <th>Status doc</th>
                        <th>Irregular</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($portal_submissions)) { foreach ($portal_submissions as $row) { ?>
                    <tr>
                        <td><?php echo esc($row->document_title ?: '-'); ?></td>
                        <td><?php echo esc($row->supplier_name ?: '-'); ?></td>
                        <td><?php echo esc($row->submitted_at ? format_to_date($row->submitted_at, false) : '-'); ?></td>
                        <td><?php echo get_portal_status_label($row->portal_status ?: 'pending'); ?></td>
                        <td><?php echo get_document_status_label(get_expiration_status($row->document_expiration_date ?? null)); ?></td>
                        <td><?php echo get_expiration_badge($row->document_expiration_date ?? null); ?></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb20">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">6. Envios com documentos vencidos</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Fornecedor</th>
                                <th>Vencimento</th>
                                <th>Status portal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($expired_document_submissions)) { foreach ($expired_document_submissions as $row) { ?>
                            <tr>
                                <td><?php echo esc($row->document_title ?: '-'); ?></td>
                                <td><?php echo esc($row->supplier_name ?: '-'); ?></td>
                                <td><?php echo esc($row->document_expiration_date ? format_to_date($row->document_expiration_date, false) : '-'); ?></td>
                                <td><?php echo get_portal_status_label($row->portal_status ?: 'pending'); ?></td>
                            </tr>
                        <?php } } else { ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">7. Envios pendentes no portal</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Fornecedor</th>
                                <th>Enviado em</th>
                                <th>Status portal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($pending_portal_submissions)) { foreach ($pending_portal_submissions as $row) { ?>
                            <tr>
                                <td><?php echo esc($row->document_title ?: '-'); ?></td>
                                <td><?php echo esc($row->supplier_name ?: '-'); ?></td>
                                <td><?php echo esc($row->submitted_at ? format_to_date($row->submitted_at, false) : '-'); ?></td>
                                <td><?php echo get_portal_status_label($row->portal_status ?: 'pending'); ?></td>
                            </tr>
                        <?php } } else { ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        Estrutura pronta para exportação futura. Os datasets já são renderizados por report, então PDF/Excel pode ser adicionado sem alterar a lógica de consulta.
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
    });
</script>
