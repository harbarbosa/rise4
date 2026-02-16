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
    $("#os-types-table").appTable({
      source: '<?php echo_uri("ordemservico/types_list_data") ?>',
      columns: [
        {title: 'Título'},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ]
    });
  });
</script>
