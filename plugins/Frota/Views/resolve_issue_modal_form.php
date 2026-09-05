<?php echo form_open(get_uri('frota/ocorrencias/' . (int)($model_info->id ?? 0) . '/resolver'), ['id' => 'frota-resolve-issue-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="resolution" class="col-md-3">Solução / observação</label>
                <div class="col-md-9">
                    <?php echo form_textarea([
                        'id' => 'resolution',
                        'name' => 'resolution',
                        'value' => $model_info->resolution ?? '',
                        'class' => 'form-control',
                        'placeholder' => 'Descreva a solução aplicada',
                        'rows' => 5,
                        'data-rule-required' => true,
                        'data-msg-required' => app_lang('field_required')
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Marcar como resolvida</button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
$(document).ready(function(){
    $("#frota-resolve-issue-form").appForm({onSuccess:function(){location.reload();}});
});
</script>
