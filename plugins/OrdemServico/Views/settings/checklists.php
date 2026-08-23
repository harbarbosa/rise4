<div id="page-content" class="page-wrapper clearfix">
  <div class="card">
    <div class="page-title clearfix">
      <h1 class="float-start">Checklist por tipo de OS</h1>
      <div class="title-button-group float-end">
        <select id="os-checklist-type" class="form-control d-inline-block" style="width: 240px;">
          <?php foreach (($checklist_types ?? []) as $type) { ?>
            <option value="<?php echo (int)$type['id']; ?>" <?php echo (int)$selected_type === (int)$type['id'] ? 'selected' : ''; ?>><?php echo esc($type['title']); ?></option>
          <?php } ?>
        </select>
        <?php echo modal_anchor(get_uri('ordemservico/checklists_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar item", ['id' => 'os-new-checklist-item', 'class' => 'btn btn-default ms10', 'title' => 'Adicionar item', 'data-post-tipo_id' => (int)($selected_type ?? 0)]); ?>
      </div>
    </div>
    <p class="text-off">Cadastre os passos que o técnico deverá conferir durante a manutenção.</p>
    <div class="table-responsive">
      <table id="os-checklists-table" class="display" width="100%"></table>
    </div>
  </div>
</div>
<script>
$(function(){
  function loadOsChecklists(){
    var tipoId = parseInt($('#os-checklist-type').val() || 0, 10);
    $('#os-new-checklist-item').attr('data-post-tipo_id', tipoId);
    $('#os-checklists-table').appTable({
      source: '<?php echo_uri("ordemservico/checklists_list_data") ?>',
      serverSide: false,
      datatable: { ajax: { type: 'POST', data: function(d){ d.tipo_id = tipoId; } } },
      columns: [
        {title: 'Item do checklist'},
        {title: 'Tipo', class: 'w120'},
        {title: 'Ordem', class: 'w80 text-center'},
        {title: '<i data-feather="menu" class="icon-16"></i>', class: 'text-center option w100'}
      ]
    });
  }
  window.reloadOsChecklists = loadOsChecklists;
  $('#os-checklist-type').on('change', loadOsChecklists);
  loadOsChecklists();
});
</script>
