<div id="page-content" class="page-wrapper clearfix">
  <div class="row">
    <div class="col-sm-12">
      <div class="card">
        <div class="page-title clearfix">
          <h4>Ordem de Serviço</h4>
          <div class="title-button-group">
            <?php echo modal_anchor(get_uri("ordemservico/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova OS", array("class" => "btn btn-default", "title" => "Nova Ordem de Serviço", "data-modal-lg" => true)); ?>
          </div>
        </div>
        <div class="table-responsive">
          <table id="os-table" class="display" width="100%"></table>
        </div>
      </div>
    </div>
  </div>
  
</div>

<style>
  /* Make entire OS table row look clickable */
  #os-table tbody tr { cursor: pointer; }
  /* Keep standard cursor on interactive elements inside the row */
  #os-table tbody tr a,
  #os-table tbody tr button,
  #os-table tbody tr input,
  #os-table tbody tr label,
  #os-table tbody tr i { cursor: auto; }
</style>

<script type="text/javascript">
  $(document).ready(function () {
    var $table = $("#os-table").appTable({
      source: '<?php echo_uri("ordemservico/list_data") ?>',
      columns: [
        {title: 'ID', "class": "w100"},
        {title: 'Título'},
        {title: '<?php echo app_lang("client") ?>'},
        {title: 'Técnico'},
        {title: '<?php echo app_lang("status") ?>'},
        {title: 'Data de abertura'},
        {title: '<?php echo app_lang("end_date") ?>'},
        {title: 'Tipo'},
        {title: 'Motivo'},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ]
    });

    // Clique na linha abre a visualização, exceto em ações/links
    $("#os-table").on('click', 'tbody tr', function (e) {
      if ($(e.target).closest('a, button, .dropdown, .btn, i, input, label').length) { return; }
      var href = $(this).find('td a').first().attr('href');
      if (href) { window.location = href; }
    });
  });
</script>
