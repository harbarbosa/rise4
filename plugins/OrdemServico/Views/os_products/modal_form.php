<?php echo form_open(get_uri("ordemservico/os_products_save"), array("id" => "os-products-item-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
  <div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? '';?>" />
    <input type="hidden" name="os_id" value="<?php echo $model_info->os_id ?? '';?>" />

    <div class="form-group">
      <div class="row">
        <label class=" col-md-3">Selecionar do Catálogo</label>
        <div class=" col-md-9">
          <?php echo form_input(array(
              "id"=>"os_product_catalog",
              "name"=>"product_id",
              "value"=> $model_info->product_id ?? '',
              "class"=>"form-control",
              "placeholder"=>"Selecione o Produto"
          )); ?>
          
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
$(function()
{
  // Lista completa de produtos (A→Z) via appDropdown
  try{
    var prods = <?php echo isset($products_dropdown)?$products_dropdown:'[]';?>;
    var prodLookup = <?php echo isset($products_lookup)?$products_lookup:'{}';?>;
    var $catalog = $('#os_product_catalog');
    $catalog.appDropdown({ list_data: prods }).on('change', function(){
      var selId = $(this).val(); if(!selId){ return; }
      var info = prodLookup[selId] || prodLookup[parseInt(selId||0)];
      if(info){
        $("input[name='descricao']").val(info.title || "");
        $("input[name='unidade']").val(info.unit_type || 'UN');
        $("input[name='valor_unitario']").val(info.rate || "");
      }
    });
    var initial = $catalog.val(); if(initial){ $catalog.trigger('change'); }
  }catch(e){}

  $("#os-products-item-form").appForm({
    isModal: true,
    onSuccess: function (result) {
      if (result && result.success) {
        $("#os-products-table").appTable({reload: true});
        if (window.loadOsProductTotals) { window.loadOsProductTotals(); }
        appAlert.success(result.message || 'Produto salvo com sucesso!');
      }
    }
  });
}); 


</script>
