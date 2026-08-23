<?php echo form_open(get_uri("ordemservico/os_atendimentos_save"), array("id" => "os-atendimentos-form", "class" => "general-form", "role" => "form")); ?>
<div id="os-atend-dropzone" class="modal-body clearfix post-dropzone">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
    <input type="hidden" name="os_id" value="<?php echo $model_info->os_id ?? ($os_id ?? ''); ?>" />

    <div class="form-group">
      <div class="row">
        <label class="col-md-3">Status</label>
        <div class="col-md-9">
          <?php $attendance_status = $model_info->status ?? 'agendado'; ?>
          <select name="status" class="form-control">
            <option value="agendado" <?php echo $attendance_status === 'agendado' ? 'selected' : ''; ?>>Agendado</option>
            <option value="em_atendimento" <?php echo $attendance_status === 'em_atendimento' ? 'selected' : ''; ?>>Em atendimento</option>
            <option value="finalizado" <?php echo $attendance_status === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
            <option value="pendente" <?php echo $attendance_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
            <option value="cancelado" <?php echo $attendance_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label class="col-md-3">Resultado</label>
        <div class="col-md-9">
          <?php $attendance_result = $model_info->resultado ?? ''; ?>
          <select name="resultado" class="form-control">
            <option value="">Ainda não informado</option>
            <option value="resolvido" <?php echo $attendance_result === 'resolvido' ? 'selected' : ''; ?>>Problema resolvido</option>
            <option value="pendente" <?php echo $attendance_result === 'pendente' ? 'selected' : ''; ?>>Problema não resolvido</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label class="col-md-3">Pendência</label>
        <div class="col-md-9">
          <?php echo form_textarea(array("name"=>"pendencia","value"=>$model_info->pendencia ?? '',"class"=>"form-control","rows"=>"2","placeholder"=>"Descreva o que ficou pendente")); ?>
        </div>
      </div>
    </div>

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
        <label class=" col-md-3">Defeito apresentado</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"defeito_apresentado","value"=>$model_info->defeito_apresentado ?? '',"class"=>"form-control","rows"=>"2","placeholder"=>"Descreva o problema relatado pelo cliente")); ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Diagnóstico</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"diagnostico","value"=>$model_info->diagnostico ?? '',"class"=>"form-control","rows"=>"2","placeholder"=>"Informe a causa ou diagnóstico técnico")); ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Solução encontrada</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"solucao_encontrada","value"=>$model_info->solucao_encontrada ?? '',"class"=>"form-control","rows"=>"2","placeholder"=>"Descreva o serviço realizado e a solução aplicada")); ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Causa raiz</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"causa_raiz","value"=>$model_info->causa_raiz ?? '',"class"=>"form-control","rows"=>"2")); ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Materiais utilizados</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"materiais_utilizados","value"=>$model_info->materiais_utilizados ?? '',"class"=>"form-control","rows"=>"2","placeholder"=>"Peças, materiais ou ferramentas utilizados")); ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Observações</label>
        <div class=" col-md-9">
          <?php echo form_textarea(array("name"=>"notes","value"=>$model_info->notes ?? '',"class"=>"form-control","rows"=>"2")); ?>
        </div>
      </div>
    </div>
    <?php if (!empty($checklist_items)) { ?>
      <div class="card p15 mb15">
        <h4 class="mb15">Checklist da manutenção</h4>
        <div class="table-responsive">
          <table class="table table-bordered mb0">
            <thead><tr><th>Verificação</th><th class="w150">Resultado</th><th>Observação</th></tr></thead>
            <tbody>
              <?php foreach ($checklist_items as $check_item) { ?>
                <tr>
                  <td>
                    <?php echo esc($check_item->title); ?>
                    <?php if (!empty($check_item->required)) { ?><span class="badge bg-warning ms5">Obrigatório</span><?php } ?>
                  </td>
                  <td>
                    <select name="checklist_status[<?php echo (int)$check_item->id; ?>]" class="form-control">
                      <option value="pending" <?php echo ($check_item->status ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                      <option value="ok" <?php echo ($check_item->status ?? '') === 'ok' ? 'selected' : ''; ?>>OK</option>
                      <option value="not_ok" <?php echo ($check_item->status ?? '') === 'not_ok' ? 'selected' : ''; ?>>Não OK</option>
                      <option value="na" <?php echo ($check_item->status ?? '') === 'na' ? 'selected' : ''; ?>>Não se aplica</option>
                    </select>
                  </td>
                  <td><input type="text" name="checklist_notes[<?php echo (int)$check_item->id; ?>]" value="<?php echo esc($check_item->notes ?? ''); ?>" class="form-control" placeholder="Observação"></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php } ?>
    <?php if (!empty($model_info->files)) { ?>
      <div class="form-group">
        <label>Anexos existentes</label>
        <div class="row">
          <?php echo view("includes/file_list", ["files" => $model_info->files]); ?>
        </div>
      </div>
    <?php } ?>
    <?php echo view("includes/dropzone_preview"); ?>
  </div>
  <div class="modal-footer">
    <div class="me-auto"><?php echo view("includes/upload_button"); ?></div>
    <button type="submit" class="btn btn-primary">Salvar</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
  </div>
</div>
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
<?php echo form_close(); ?>
