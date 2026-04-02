<div id="page-content" class="page-wrapper clearfix">
  <div class="row">
    
    <div class="col-sm-12 col-lg-12">
      <div class="card">
        <ul class="nav nav-tabs bg-white title scrollable-tabs" role="tablist">
          <li class="nav-item"><a role="presentation" class="nav-link active" data-bs-toggle="tab" href="#os-types-tab"><?php echo app_lang('os_menu_types'); ?></a></li>
          <li class="nav-item"><a role="presentation" class="nav-link" data-bs-toggle="tab" href="#os-reasons-tab"><?php echo app_lang('os_menu_reasons'); ?></a></li>
          <li class="nav-item"><a role="presentation" class="nav-link" data-bs-toggle="tab" href="#os-categories-tab">Categorias</a></li>
        </ul>
        <div class="tab-content p-3">
          <div role="tabpanel" class="tab-pane fade show active" id="os-types-tab">
           <div class="page-title clearfix">
  <h4 class="float-start mb-0"><?php echo app_lang('os_menu_types'); ?></h4>
  <div class="title-button-group float-end">
    <?php echo modal_anchor(get_uri("ordemservico/types_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('os_add_type'), array("class" => "btn btn-default", "title" => app_lang('os_add_type'))); ?>
  </div>
  <div class="clearfix"></div>
</div>
<div class="table-responsive">
  <table id="os-types-table" class="display" width="100%"></table>
  </div>
<script type="text/javascript">
  $(document).ready(function () {
    
  });
</script>
          </div>
          <div role="tabpanel" class="tab-pane fade" id="os-reasons-tab">
           <div class="page-title clearfix">
  <h4 class="float-start mb-0"><?php echo app_lang('os_menu_reasons'); ?></h4>
  <div class="title-button-group float-end">
    <?php echo modal_anchor(get_uri("ordemservico/reasons_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('os_add_reason'), array("class" => "btn btn-default", "title" => app_lang('os_add_reason'))); ?>
  </div>
  <div class="clearfix"></div>
</div>
<div class="table-responsive">
  <table id="os-reasons-table" class="display" width="100%"></table>
</div>
<script type="text/javascript">
  $(document).ready(function () {
    $("#os-reasons-table").appTable({
      source: '<?php echo_uri("ordemservico/reasons_list_data") ?>',
      columns: [
        {title: 'Título'},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ]
    });
  });
</script>
          </div>
          <div role="tabpanel" class="tab-pane fade" id="os-categories-tab">
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
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function () {
    
    $("#os-types-table").appTable({
      source: '<?php echo_uri("ordemservico/types_list_data") ?>',
      columns: [
        {title: 'Título'},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ]
    });


  });
</script>
