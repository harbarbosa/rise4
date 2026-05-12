<?php
$kpis = $kpis ?? (object) array();
$submission_stats = $submission_stats ?? (object) array();

$cards = array(
    array('title' => 'Total de documentos', 'value' => (int) ($kpis->total_documents ?? 0), 'class' => 'bg-primary text-white', 'url' => get_uri('ged/documents')),
    array('title' => 'Documentos vencidos', 'value' => (int) ($kpis->expired_documents ?? 0), 'class' => 'bg-danger text-white', 'url' => get_uri('ged/documents?expiration_scope=overdue')),
    array('title' => 'Vencendo em 30 dias', 'value' => (int) ($kpis->expiring_30_documents ?? 0), 'class' => 'bg-warning text-dark', 'url' => get_uri('ged/documents?expiration_scope=expiring_30')),
    array('title' => 'Vencendo em 7 dias', 'value' => (int) ($kpis->expiring_7_documents ?? 0), 'class' => 'bg-warning text-dark', 'url' => get_uri('ged/documents?expiration_scope=expiring_7')),
);
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('ged'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_documents)) { ?>
                    <?php echo modal_anchor(get_uri('ged/documents/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo documento", array('class' => 'btn btn-default me-1', 'title' => 'Novo documento')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($cards as $card) { ?>
                    <div class="col-md-4 col-lg-2">
                        <a href="<?php echo esc($card['url']); ?>" class="text-decoration-none d-block">
                            <div class="p15 rounded <?php echo $card['class']; ?>">
                                <div class="small opacity-75"><?php echo esc($card['title']); ?></div>
                                <div class="fs-3 fw-bold"><?php echo (int) $card['value']; ?></div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <div class="row mt30 g-3">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb0">Documentos vencendo nos proximos 30 dias</h5>
                            <span class="badge bg-warning text-dark"><?php echo count($recent_documents_expiring ?? array()); ?></span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Titulo</th>
                                        <th>Tipo</th>
                                        <th>Vencimento</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_documents_expiring)) { ?>
                                        <?php foreach ($recent_documents_expiring as $doc) { ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($can_view_documents)) { ?>
                                                        <?php echo anchor(get_uri('ged/documents/view/' . $doc->id), esc($doc->title)); ?>
                                                    <?php } else { ?>
                                                        <?php echo esc($doc->title); ?>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo esc($doc->document_type_name ?: '-'); ?></td>
                                                <td><?php echo get_expiration_badge($doc->expiration_date); ?></td>
                                                <td><?php echo get_document_status_label(get_expiration_status($doc->expiration_date)); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="4" class="text-muted">Nenhum documento encontrado.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb0">Documentos vencidos</h5>
                            <span class="badge bg-danger"><?php echo count($recent_documents_expired ?? array()); ?></span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Titulo</th>
                                        <th>Tipo</th>
                                        <th>Vencimento</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_documents_expired)) { ?>
                                        <?php foreach ($recent_documents_expired as $doc) { ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($can_view_documents)) { ?>
                                                        <?php echo anchor(get_uri('ged/documents/view/' . $doc->id), esc($doc->title)); ?>
                                                    <?php } else { ?>
                                                        <?php echo esc($doc->title); ?>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo esc($doc->document_type_name ?: '-'); ?></td>
                                                <td><?php echo get_expiration_badge($doc->expiration_date); ?></td>
                                                <td><?php echo get_document_status_label(get_expiration_status($doc->expiration_date)); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="4" class="text-muted">Nenhum documento vencido encontrado.</td></tr>
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
