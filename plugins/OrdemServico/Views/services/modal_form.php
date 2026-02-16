<?php echo form_open(get_uri("ordemservico/services_save"), array("id" => "os-services-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? '';?>" />
    <input type="hidden" name="tipo" id="os_service_tipo" value="<?php echo $model_info->tipo ?: 'ordem_servico';?>" />

    <div class="row">
      <div class="col-md-12">
        <div class="card p15">
         

          <div class="form-group">
            <div class="row">
              <label class=" col-md-3">Descrição *</label>
              <div class=" col-md-9">
                <?php echo form_input(array("name"=>"descricao","value"=>$model_info->descricao ?? '',"class"=>"form-control","data-rule-required"=>true,"data-msg-required"=>app_lang("field_required"))); ?>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label class=" col-md-3">Categoria da Receita</label>
              <div class=" col-md-9">
                <?php echo form_input(array("id"=>"os_categoria_receita","name"=>"categoria_receita","value"=>$model_info->categoria_receita ?? '',"class"=>"form-control","placeholder"=>"Categoria")); ?>
              </div>
            </div>
          </div>

          <h5 class="mt15">Precificação</h5>
          <div class="form-group">
            <div class="row">
              <label class=" col-md-3">Custo (R$)</label>
              <div class=" col-md-9">
                <?php echo form_input(array("name"=>"custo","value"=>$model_info->custo ?? '0',"class"=>"form-control")); ?>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <label class=" col-md-3">Margem (%)</label>
              <div class=" col-md-9">
                <?php echo form_input(array("name"=>"margem","value"=>$model_info->margem ?? '0',"class"=>"form-control")); ?>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <label class=" col-md-3">Venda (R$)</label>
              <div class=" col-md-9">
                <?php echo form_input(array("name"=>"valor_venda","value"=>$model_info->valor_venda ?? '0',"class"=>"form-control")); ?>
              </div>
            </div>
          </div>
        </div>
      </div>

  
    </div>

  </div>
</div>
<div class="modal-footer">
  <button type="submit" class="btn btn-primary">Salvar Serviço</button>
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</div>
<?php echo form_close(); ?>

<script>
$(function(){
  // set active toggle based on current value
  var current = $('#os_service_tipo').val() || 'ordem_servico';
  $('.tipo-btn').removeClass('active');
  $('.tipo-btn[data-value="'+current+'"]').addClass('active');
  $('.tipo-btn').on('click', function(){
    $('.tipo-btn').removeClass('active');
    $(this).addClass('active');
    $('#os_service_tipo').val($(this).data('value'));
  });

  // categories appDropdown
  try {
    var cats = <?php echo isset($categories_dropdown)?$categories_dropdown:'[]';?>;
    $('#os_categoria_receita').appDropdown({ list_data: cats });
  } catch(e) {}

  $("#os-services-form").appForm({
    isModal: true,
    onSuccess: function (result) {
      if (result && result.success) {
        $("#os-services-table").appTable({newData: result.data, dataId: result.id});
        appAlert.success(result.message || 'Serviço salvo com sucesso!');
      }
    }
  });
});
</script>

