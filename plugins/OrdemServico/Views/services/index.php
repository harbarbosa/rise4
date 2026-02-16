<div id="page-content" class="page-wrapper clearfix">
  <div class="row">
    <div class="col-sm-12">
      <div class="card">
        <div class="page-title clearfix">
          <h4>Serviços</h4>
          
          <div class="title-button-group">
            <?php echo modal_anchor(get_uri("ordemservico/services_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Novo Serviço", array("class" => "btn btn-default", "title" => "Novo Serviço")); ?>
          </div>
        </div>
        <div class="table-responsive">
          <table id="os-services-table" class="display" width="100%"></table>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  $(document).ready(function () {
    $("#os-services-table").appTable({
      source: '<?php echo_uri("ordemservico/services_list_data") ?>',
      columns: [
        {title: 'Descrição'},
        {title: 'Tipo'},
        {title: 'Categoria'},
        {title: 'Custo', "class": "text-end w120"},
        {title: 'Margem', "class": "text-end w120"},
        {title: 'Valor Venda', "class": "text-end w120"},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ]
    });
  });
</script>

