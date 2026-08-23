<?php echo form_open(get_uri("ordemservico/checklists_save"), ["id" => "os-checklists-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo (int)($model_info->id ?? 0); ?>" />
    <input type="hidden" name="tipo_id" value="<?php echo (int)($tipo_id ?? $model_info->tipo_id ?? 0); ?>" />
    <div class="form-group">
      <div class="row">
        <label class="col-md-3">Item do checklist</label>
        <div class="col-md-9">
          <?php echo form_input(["name" => "title", "value" => $model_info->title ?? '', "class" => "form-control", "placeholder" => "Ex.: Verificar armazenamento do DVR", "data-rule-required" => true, "data-msg-required" => app_lang('field_required')]); ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="row">
        <label class="col-md-3">Ordem de exibição</label>
        <div class="col-md-3">
          <?php echo form_input(["type" => "number", "name" => "sort_order", "value" => $model_info->sort_order ?? 0, "class" => "form-control", "min" => 0]); ?>
        </div>
        <div class="col-md-6 pt10">
          <label><?php echo form_checkbox("required", "1", !empty($model_info->required)); ?> Item obrigatório</label>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
</div>
<?php echo form_close(); ?>
<script>
$(function(){
  $('#os-checklists-form').appForm({
    isModal: true,
    onSuccess: function(result){
      if (result && result.success) {
        if (window.reloadOsChecklists) { window.reloadOsChecklists(); }
        appAlert.success(result.message || 'Salvo com sucesso');
      }
    }
  });
});
</script>
