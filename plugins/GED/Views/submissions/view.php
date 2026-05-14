<?php
$model_info = $model_info ?? (object) array();
$document_status_meta = $document_status_meta ?? array('html' => "<span class='badge bg-secondary'>Pendente</span>");
$irregular_meta = $irregular_meta ?? array('html' => "<span class='badge bg-success'>Regular</span>");
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('ged_field_document'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_edit)) { ?>
                    <?php echo modal_anchor(get_uri('ged/submissions/modal_form'), "<i data-feather='edit' class='icon-16'></i> Editar", array('class' => 'edit btn btn-default', 'data-post-id' => $model_info->id)); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb15"><strong><?php echo app_lang('ged_field_document'); ?>:</strong>
                        <?php if (!empty($can_view_documents)) { ?>
                            <?php echo anchor(get_uri('ged/documents/view/' . $model_info->document_id), esc($model_info->document_title ?: '-')); ?>
                        <?php } else { ?>
                            <?php echo esc($model_info->document_title ?: '-'); ?>
                        <?php } ?>
                    </div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_document_type'); ?>:</strong> <?php echo esc($model_info->document_type_name ?: '-'); ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_owner_type'); ?>:</strong>
                        <?php
                        if (($model_info->document_owner_type ?? '') === 'employee') {
                            echo esc($model_info->employee_name ?: '-');
                        } else {
                            echo app_lang('ged_field_company');
                        }
                        ?>
                    </div>
                    <div class="mb15"><strong>Enviado em:</strong> <?php echo esc($model_info->submitted_at ? format_to_date($model_info->submitted_at, false) : '-'); ?></div>
                    <div class="mb15"><strong>Documento vinculado:</strong> <?php echo $document_status_meta['html']; ?></div>
                    <div class="mb15"><strong>Irregularidade:</strong> <?php echo $irregular_meta['html']; ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_notes'); ?>:</strong><br><?php echo nl2br(esc($model_info->notes ?: '-')); ?></div>
                </div>

                <div class="col-md-4">
                    <div class="alert alert-warning">
                        <strong>Regra de integridade</strong><br>
                        Um envio fica irregular quando o documento vinculado vence ou esta vencendo.
                    </div>

                    <?php if (!empty($can_delete)) { ?>
                        <?php echo js_anchor("<i data-feather='x' class='icon-16'></i> Excluir", array(
                            'class' => 'btn btn-outline-danger',
                            'data-id' => $model_info->id,
                            'data-action-url' => get_uri('ged/submissions/delete'),
                            'data-action' => 'delete-confirmation'
                        )); ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
