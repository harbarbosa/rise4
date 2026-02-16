<?php echo form_open(get_uri("ordemservico/comment_save"), array("id" => "os-comment-modal-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
    <input type="hidden" name="os_id" value="<?php echo $model_info->os_id ?? ''; ?>" />
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Comentário</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"comment","value"=>$model_info->comment ?? '',"class"=>"form-control","rows"=>4,"data-rule-required"=>true,"data-msg-required"=>app_lang("field_required"))); ?>
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
  $("#os-comment-modal-form").appForm({
    isModal: true,
    onSuccess: function(result){
      if(result && result.success){
        // reload comments list if a table exists
        if(window.loadOsComments){ loadOsComments(); }
        appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>");
      }
    }
  });
});
</script>

