<div class="page-title clearfix">
  <h4 class="float-start mb-0">Categorias</h4>
  <div class="title-button-group float-end">
    <?php echo modal_anchor(get_uri("ordemservico/categories_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova Categoria", array("class" => "btn btn-default", "title" => "Nova Categoria")); ?>
  </div>
  <div class="clearfix"></div>
</div>
<div class="table-responsive">
  <table id="os-categories-table" class="display" width="100%"></table>
</div>
<script type="text/javascript">
  $(document).ready(function () {
    $("#os-categories-table").appTable({
      source: '<?php echo_uri("ordemservico/categories_list_data") ?>',
      columns: [
        {title: 'Título'},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ]
    });
  });
</script>

