<?php echo form_open(get_uri("ordemservico/save"), array("id" => "os-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo isset($model_info->id) ? $model_info->id : ""; ?>" />

    <div class="form-group">
      <div class="row">
        <label for="titulo" class=" col-md-3">Título</label>
        <div class=" col-md-9">
          <?php
          echo form_input(array(
              "id" => "os_titulo",
              "name" => "titulo",
              "value" => isset($model_info->titulo) ? $model_info->titulo : "",
              "class" => "form-control",
              "placeholder" => "Título da OS",
          ));
          ?>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label for="cliente_id" class=" col-md-3">Cliente</label>
        <div class=" col-md-9">
          <?php
          echo form_input(array(
              "id" => "os_client_id",
              "name" => "cliente_id",
              "value" => isset($model_info->cliente_id) ? $model_info->cliente_id : "",
              "class" => "form-control",
              "placeholder" => app_lang('client'),
              "data-rule-required" => true,
              "data-msg-required" => app_lang("field_required"),
          ));
          ?>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label for="tecnico_id" class=" col-md-3">Técnico</label>
        <div class=" col-md-9">
          <?php
          echo form_input(array(
              "id" => "os_tech_id",
              "name" => "tecnico_id",
              "value" => isset($model_info->tecnico_id) ? $model_info->tecnico_id : "",
              "class" => "form-control",
              "placeholder" => "Técnico",
          ));
          ?>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label for="os_status" class=" col-md-3">Status</label>
        <div class=" col-md-9">
          <?php
            $current_status = isset($model_info->status) ? $model_info->status : "aberta";
            echo form_input(array(
                "id" => "os_status",
                "name" => "status",
                "value" => $current_status,
                "class" => "form-control",
                "placeholder" => app_lang('status'),
                "data-rule-required" => true,
                "data-msg-required" => app_lang("field_required"),
            ));
          ?>
        </div>
      </div>
    </div>

    <!-- Data de abertura automática; data de fechamento removida -->

    <div class="form-group">
      <div class="row">
        <label for="descricao" class=" col-md-3">Descrição</label>
        <div class=" col-md-9">
          <?php
          echo form_textarea(array(
              "id" => "descricao",
              "name" => "descricao",
              "value" => isset($model_info->descricao) ? $model_info->descricao : "",
              "class" => "form-control",
              "rows" => 3,
          ));
          ?>
        </div>
      </div>
    </div>


    <div class="form-group">
      <div class="row">
        <label for="os_tipo_id" class=" col-md-3">Tipo</label>
        <div class=" col-md-9">
          <?php
          echo form_input(array(
              "id" => "os_tipo_id",
              "name" => "tipo_id",
              "value" => isset($model_info->tipo_id) ? $model_info->tipo_id : "",
              "class" => "form-control",
              "placeholder" => "Tipo",
              "data-rule-required" => true,
              "data-msg-required" => app_lang("field_required"),
          ));
          ?>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label for="os_motivo_id" class=" col-md-3">Motivo</label>
        <div class=" col-md-9">
          <?php
          echo form_input(array(
              "id" => "os_motivo_id",
              "name" => "motivo_id",
              "value" => isset($model_info->motivo_id) ? $model_info->motivo_id : "",
              "class" => "form-control",
              "placeholder" => "Motivo",
              "data-rule-required" => true,
              "data-msg-required" => app_lang("field_required"),
          ));
          ?>
        </div>
      </div>
    </div>

    
  </div>
</div>
<div class="modal-footer">
  <button type="submit" class="btn btn-primary">Salvar</button>
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</div>
<?php echo form_close(); ?>

<script>
$(function() {
  $("#os-form").appForm({
    isModal: true,
    onSuccess: function (result) {
      if (result && result.success) {
        $("#os-table").appTable({newData: result.data, dataId: result.id});
        appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>");
      }
    }
  });
  try {
    var clientsCfg = <?php echo isset($clients_dropdown) ? $clients_dropdown : '[]'; ?>;
    $('#os_client_id').appDropdown({ list_data: clientsCfg });
  } catch(err) {}
  try {
    var techCfg = <?php echo isset($technicians_dropdown) ? $technicians_dropdown : '[]'; ?>;
    $('#os_tech_id').appDropdown({ list_data: techCfg });
  } catch(err) {}
  try {
    var tiposCfg = <?php echo isset($tipos_dropdown) ? $tipos_dropdown : '[]'; ?>;
    $('#os_tipo_id').appDropdown({ list_data: tiposCfg });
  } catch(err) {}
  try {
    var motivosCfg = <?php echo isset($motivos_dropdown) ? $motivos_dropdown : '[]'; ?>;
    $('#os_motivo_id').appDropdown({ list_data: motivosCfg });
  } catch(err) {}
  try {
    var statusCfg = [
      {id: '', text: '-'},
      {id: 'aberta', text: 'Aberta'},
      {id: 'em_andamento', text: 'Em andamento'},
      {id: 'fechada', text: 'Fechada'},
      {id: 'cancelada', text: 'Cancelada'}
    ];
    $('#os_status').appDropdown({ list_data: statusCfg });
  } catch(err) {}
});
</script>
