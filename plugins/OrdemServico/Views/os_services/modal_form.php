<?php echo form_open(get_uri("ordemservico/os_services_save"), array("id" => "os-services-item-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? '';?>" />
    <input type="hidden" name="os_id" value="<?php echo $model_info->os_id ?? '';?>" />

    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Selecionar do Catálogo</label>
        <div class=" col-md-9">
          <?php echo form_input(array(
              "id"=>"os_service_catalog",
              "name"=>"service_id",
              "value"=> $model_info->service_id ?? '',
              "class"=>"form-control",
              "placeholder"=>"Buscar serviço cadastrado"
          )); ?>
          <div class="text-off small mt5">Ao selecionar, a Descrição e o Valor unitário serão preenchidos automaticamente.</div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Descrição *</label>
        <div class=" col-md-9">
          <?php echo form_input(array("name"=>"descricao","value"=>$model_info->descricao ?? '',"class"=>"form-control","data-rule-required"=>true,"data-msg-required"=>app_lang("field_required"))); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="form-group">
          <label>Quantidade</label>
          <?php echo form_input(array(
              "name"=>"quantidade",
              "value"=> isset($model_info->quantidade) && $model_info->quantidade !== '' ? to_decimal_format($model_info->quantidade) : "",
              "class"=>"form-control",
              "placeholder"=>"0,00"
          )); ?>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Unidade</label>
          <?php echo form_input(array("name"=>"unidade","value"=>$model_info->unidade ?? 'UN',"class"=>"form-control")); ?>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Valor unitário (R$)</label>
          <?php echo form_input(array(
              "name"=>"valor_unitario",
              "value"=> isset($model_info->valor_unitario) && $model_info->valor_unitario !== '' ? to_decimal_format($model_info->valor_unitario) : "",
              "class"=>"form-control",
              "placeholder"=>"0,00"
          )); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Desconto (R$)</label>
          <?php echo form_input(array(
              "name"=>"desconto",
              "value"=> isset($model_info->desconto) && $model_info->desconto !== '' ? to_decimal_format($model_info->desconto) : "",
              "class"=>"form-control",
              "placeholder"=>"0,00"
          )); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Tipo de Cobrança</label>
          <?php echo form_dropdown("tipo_cobranca", ["cobrado"=>"Cobrado","nao_cobrado"=>"Sem Cobrança"], $model_info->tipo_cobranca ?? 'cobrado', "class='form-control'"); ?>
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

$(function(){
   let firstLoad = true;
    var vv = $("input[name='valor_unitario']").val();
  // Catálogo: dropdown e preenchimento de campos
  try {
    var services = <?php echo isset($services_dropdown)?$services_dropdown:'[]';?>;
    var svcLookup = <?php echo isset($services_lookup)?$services_lookup:'{}';?>;
    $('#os_service_catalog').appDropdown({ list_data: services }).on('change', function(){
      var selId = $(this).val();
     
       if (firstLoad) {
        firstLoad = false;
        if (vv && vv.trim() !== "") {
            return; // não executa a requisição
        }
    }
      
      // Sempre buscar o valor atual no banco
      $.post('<?php echo get_uri('ordemservico/service_info'); ?>', { id: selId })
        .done(function(res){
         
          if (res && res.success && res.data){
         
            $("input[name='descricao']").val(res.data.descricao || "");
            var vv = res.data.valor_venda_formatted || (res.data.valor_venda != null ? (""+res.data.valor_venda) : "");
            $("input[name='valor_unitario']").val(vv);
          } else {
            // fallback no lookup local, se houver
            var info = (svcLookup[selId] || svcLookup[parseInt(selId||0)]) || null;
            if (info){
              $("input[name='descricao']").val(info.descricao || "");
              $("input[name='valor_unitario']").val(info.valor_venda_formatted || (info.valor_venda != null ? (""+info.valor_venda) : ""));
            }
          }
        });
    });
    // se houver valor inicial, dispara change para atualizar do banco
    var initialSel = $('#os_service_catalog').val();
    if (initialSel) { $('#os_service_catalog').trigger('change'); }
  } catch(e) {}

  $("#os-services-item-form").appForm({
    isModal: true,
    onSuccess: function (result) {
      if (result && result.success) {
        $("#os-services-table").appTable({reload: true});
        if (window.loadOsServiceTotals) { window.loadOsServiceTotals(); }
        appAlert.success(result.message || 'Serviço salvo com sucesso!');
      }
    }
  });
});
</script>
