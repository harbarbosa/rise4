<?php echo form_open(get_uri("ordemservico/os_files_save"), array("id" => "os-files-form", "class" => "general-form", "role" => "form")); ?>
<div id="os-files-dropzone" class="modal-body clearfix post-dropzone">
  <div class="container-fluid">
    <input type="hidden" name="os_id" value="<?php echo (int)($os_id ?? 0); ?>" />
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Descrição</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"description","class"=>"form-control","placeholder"=>"Descrição")); ?>
        </div>
      </div>
    </div>
    <?php echo view("includes/dropzone_preview"); ?>
  </div>
  <div class="modal-footer">
    <div class="me-auto"><?php echo view("includes/upload_button"); ?></div>
    <button type="submit" class="btn btn-primary">Salvar</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
  </div>
</div>
<?php echo form_close(); ?>
<script>
$(function(){
  $("#os-files-form").appForm({
    isModal: true,
    onSuccess: function(res){ 
      if(res && res.success){ 
        appAlert.success(res.message||'Salvo!'); 
        if(window.reloadOsFiles)
          { 
            window.reloadOsFiles(); 
          } try{
            //$('#ajaxModal').modal('hide');
          }catch(e){} } }
  });
});
</script>

