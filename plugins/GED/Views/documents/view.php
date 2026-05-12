<?php
$model_info = $model_info ?? (object) array();
$status_meta = $status_meta ?? array('html' => "<span class='badge bg-secondary'>Pendente</span>", 'status' => 'pending');
$storage_info = $storage_info ?? array('absolute_path' => '');

$owner_label = 'Empresa';
if (($model_info->owner_type ?? '') === 'employee') {
    $owner_label = $model_info->employee_name ?? 'Funcionario';
} elseif (($model_info->owner_type ?? '') === 'supplier') {
    $owner_label = $model_info->supplier_name ?? 'Fornecedor';
}
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo esc($model_info->title ?: app_lang('ged_documents')); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_download) && !empty($model_info->file_path)) { ?>
                    <?php echo anchor(get_uri('ged/documents/download/' . $model_info->id), "<i data-feather='download' class='icon-16'></i> Baixar", array('class' => 'btn btn-default', 'target' => '_blank')); ?>
                <?php } ?>
                <?php if (!empty($can_edit)) { ?>
                    <?php echo modal_anchor(get_uri('ged/documents/modal_form'), "<i data-feather='edit' class='icon-16'></i> Editar", array('class' => 'edit btn btn-default', 'data-post-id' => $model_info->id)); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb15"><strong><?php echo app_lang('ged_field_document_type'); ?>:</strong> <?php echo esc($model_info->document_type_name ?: '-'); ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_owner_type'); ?>:</strong> <?php echo esc($owner_label); ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_issue_date'); ?>:</strong> <?php echo esc($model_info->issue_date ? format_to_date($model_info->issue_date, false) : '-'); ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_expiration_date'); ?>:</strong> <?php echo esc($model_info->expiration_date ? format_to_date($model_info->expiration_date, false) : '-'); ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_status'); ?>:</strong> <?php echo $status_meta['html']; ?></div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_file'); ?>:</strong>
                        <?php if (!empty($model_info->file_path) && !empty($can_download)) { ?>
                            <?php echo anchor(get_uri('ged/documents/download/' . $model_info->id), esc($model_info->original_filename ?: basename($model_info->file_path)), array('target' => '_blank')); ?>
                        <?php } else { ?>
                            -
                        <?php } ?>
                    </div>
                    <div class="mb15"><strong><?php echo app_lang('ged_field_notes'); ?>:</strong><br><?php echo nl2br(esc($model_info->notes ?: '-')); ?></div>
                </div>

                <div class="col-md-4">
                    <div class="alert alert-info">
                        <strong><?php echo app_lang('ged_field_expiration_status'); ?></strong><br>
                        <?php echo $status_meta['html']; ?><br>
                        <span class="small">O status e calculado com base na data de vencimento.</span>
                    </div>

                    <?php if (!empty($storage_info['absolute_path'])) { ?>
                        <div class="small text-muted">
                            Arquivo protegido pelo controller. Caminho interno nao e exposto na interface.
                        </div>
                    <?php } ?>

                    <?php if (!empty($can_delete)) { ?>
                        <div class="mt20">
                            <?php echo js_anchor("<i data-feather='x' class='icon-16'></i> Excluir", array(
                                'class' => 'btn btn-outline-danger',
                                'data-id' => $model_info->id,
                                'data-action-url' => get_uri('ged/documents/delete'),
                                'data-action' => 'delete-confirmation'
                            )); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
