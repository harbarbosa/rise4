<?php echo form_open(get_uri("ordemservico/types_save"), array("id" => "os-types-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? '';?>" />
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Título</label>
        <div class=" col-md-9">
          <?php echo form_input(array("name"=>"title","value"=>$model_info->title ?? '',"class"=>"form-control","data-rule-required"=>true,"data-msg-required"=>app_lang("field_required"))); ?>
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
  $("#os-types-form").appForm({
    isModal: true,
    onSuccess: function (result) {
      if (result && result.success) {
        $("#os-types-table").appTable({newData: result.data, dataId: result.id});
        appAlert.success('Salvo com sucesso');

      }
    }
  });
});
</script>
