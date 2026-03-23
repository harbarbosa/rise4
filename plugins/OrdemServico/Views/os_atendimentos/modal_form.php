<?php echo form_open(get_uri("ordemservico/os_atendimentos_save"), array("id" => "os-atendimentos-form", "class" => "general-form", "role" => "form")); ?>
<div id="os-atend-dropzone" class="modal-body clearfix post-dropzone">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
    <input type="hidden" name="os_id" value="<?php echo $model_info->os_id ?? ($os_id ?? ''); ?>" />

    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Membros da equipe</label>
        <div class=" col-md-9">
          <?php echo form_input(array(
              "id" => "os_at_members",
              "name" => "member_ids",
              "value" => isset($selected_members) ? implode(',', json_decode($selected_members, true)) : "",
              "class" => "form-control",
              "placeholder" => "Selecione os membros"
          )); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Data inicial</label>
          <?php echo form_input(array("name"=>"start_date","value"=> isset($model_info->start_datetime)? date('Y-m-d', strtotime($model_info->start_datetime)) : "","class"=>"form-control","type"=>"date")); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Horário inicial</label>
          <?php echo form_input(array("name"=>"start_time","value"=> isset($model_info->start_datetime)? date('H:i', strtotime($model_info->start_datetime)) : "","class"=>"form-control","type"=>"time")); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Data final</label>
          <?php echo form_input(array("name"=>"end_date","value"=> isset($model_info->end_datetime)? date('Y-m-d', strtotime($model_info->end_datetime)) : "","class"=>"form-control","type"=>"date")); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Horário final</label>
          <?php echo form_input(array("name"=>"end_time","value"=> isset($model_info->end_datetime)? date('H:i', strtotime($model_info->end_datetime)) : "","class"=>"form-control","type"=>"time")); ?>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Observações</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"notes","value"=>$model_info->notes ?? '',"class"=>"form-control")); ?>
        </div>
      </div>
    </div>
    <?php echo view("includes/dropzone_preview"); ?>
  </div>
</div>
<div class="modal-footer">
  <div class="me-auto"><?php echo view("includes/upload_button"); ?></div>
  <button type="submit" class="btn btn-primary">Salvar</button>
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
  <script>
    (function(){
      try{
        var list = <?php echo isset($members_dropdown)?$members_dropdown:'[]';?>;
        var selected = <?php echo isset($selected_members)?$selected_members:'[]';?>;
        // initialize as multi-select; also keep hidden input value in sync as CSV
        var $el = $('#os_at_members');
        $el.appDropdown({ list_data: list, multiple: true }).val(selected).trigger('change');
        $el.on('change', function(){
          try{
            var v = $(this).val();
            if ($.isArray(v)) { $(this).val(v.join(',')); }
          }catch(e){}
        });
      }catch(e){}
    })();
    $(function(){
      $("#os-atendimentos-form").appForm({
        isModal: true,
        onSuccess: function (result) {
          if (result && result.success) {
            
            appAlert.success(result.message || 'Salvo com sucesso!');
            if (window.reloadOsAtendimentos) { window.reloadOsAtendimentos(); }
            try { $('#ajaxModal').modal('hide'); } catch(e) {}
          }
        }
      });
    });
  </script>
</div>
<?php echo form_close(); ?>
