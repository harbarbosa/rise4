<div class="card">
    <div class="page-title clearfix">
        <h1><?php echo app_lang('project_materials'); ?></h1>
        <div class="title-button-group"><button class="btn btn-default" data-bs-toggle="collapse" data-bs-target="#add-project-material"><i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add'); ?></button></div>
    </div>
    <div id="add-project-material" class="collapse p15 border-bottom">
        <form id="project-material-add-form" class="row g-2">
            <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
            <div class="col-md-4"><label for="project-material-catalog-select">Material cadastrado</label><select name="item_id" id="project-material-catalog-select" class="form-control select2 project-material-select2" data-placeholder="Pesquise ou selecione um material"><option value="">- Material fora da lista -</option><?php foreach ($catalog_items as $item) { ?><option value="<?php echo (int)$item->id; ?>" data-unit="<?php echo esc($item->unit_type); ?>"><?php echo esc($item->title); ?></option><?php } ?></select></div>
            <div class="col-md-4"><label>Descrição</label><input name="description" class="form-control" placeholder="Informe para um material fora da lista"></div>
            <div class="col-md-2"><label>Quantidade</label><input name="quantity" class="form-control" inputmode="decimal" required></div>
            <div class="col-md-1"><label>Unidade</label><input name="unit" class="form-control" value="UN"></div>
            <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><?php echo app_lang('save'); ?></button></div>
        </form>
    </div>
    <?php if ($materials && $tasks) { ?>
    <form id="project-material-batch-form" class="p15 border-bottom bg-light">
        <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
        <div class="row align-items-end">
            <div class="col-md-5">
                <label><strong>Associação em lote</strong></label>
                <select name="task_id" id="batch-material-task-select" class="form-control select2 project-material-select2 project-material-task-select" data-placeholder="Selecione a tarefa" required>
                    <option value=""></option>
                    <?php foreach ($tasks as $task) { ?>
                        <option value="<?php echo (int)$task->id; ?>"><?php echo esc($task->title); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-4 text-off">Marque os materiais abaixo e informe a quantidade necessária para a tarefa.</div>
            <div class="col-md-3 text-end">
                <button class="btn btn-primary" type="submit"><i data-feather="check-square" class="icon-16"></i> Associar selecionados</button>
            </div>
        </div>
    </form>
    <?php } ?>
    <?php if ($materials) { ?>
    <form id="project-material-direct-request-form" class="p15 border-bottom">
        <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
        <div class="row align-items-end">
            <div class="col-md-6">
                <label><strong>Solicitar compra diretamente pelo projeto</strong></label>
                <div class="text-off">Marque os materiais na coluna “Solicitar” e informe a quantidade de cada item.</div>
            </div>
            <div class="col-md-3">
                <label>Data necessária</label>
                <input type="date" name="desired_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-primary" type="submit"><i data-feather="shopping-cart" class="icon-16"></i> Criar requisição</button>
            </div>
        </div>
    </form>
    <?php } ?>
    <?php if ($materials) { ?>
    <div class="project-material-search-bar">
        <div class="input-group">
            <span class="input-group-text project-material-search-icon" aria-hidden="true">&#128269;</span>
            <input type="search" id="project-material-search" class="form-control" placeholder="Pesquisar material por nome, unidade ou origem..." autocomplete="off">
            <button type="button" id="clear-project-material-search" class="btn btn-default" title="Limpar pesquisa">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div id="project-material-search-count" class="small text-off"></div>
    </div>
    <?php } ?>
    <div class="table-responsive">
        <table class="table table-hover mb0 project-materials-main-table">
            <thead>
                <tr>
                    <th class="material-check-column"><input type="checkbox" id="batch-material-check-all" class="form-check-input" title="Selecionar todos para associação"></th>
                    <th class="material-name-column">Material</th>
                    <th class="material-quantity-column">Quantidades</th>
                    <th class="material-purchase-column">Solicitar compra</th>
                    <th class="material-batch-column">Associação em lote</th>
                    <th class="material-individual-column">Associar individualmente</th>
                    <th class="material-actions-column text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($materials as $material) { $total=(float)$material->planned_quantity+(float)$material->additional_quantity; $available=max(0,$total-(float)$material->allocated_quantity); $request_available=max(0,$total-(float)$material->requested_quantity); ?>
                <tr class="project-material-row" data-material-search="<?php echo esc(strtolower($material->description.' '.$material->unit.' '.($material->source === 'proposal' ? 'proposta' : 'adicional'))); ?>">
                    <td class="material-check-cell"><input type="checkbox" class="form-check-input batch-material-check" value="<?php echo (int)$material->id; ?>" <?php echo $available>0?'':'disabled'; ?>></td>
                    <td class="material-name-cell">
                        <div class="material-title"><?php echo esc($material->description); ?></div>
                        <div class="material-meta">
                            <span class="badge bg-<?php echo $material->source === 'proposal' ? 'info' : 'secondary'; ?>"><?php echo $material->source === 'proposal' ? 'Proposta' : 'Adicional'; ?></span>
                            <span><?php echo esc($material->unit); ?></span>
                        </div>
                    </td>
                    <td class="material-quantity-cell">
                        <div class="material-quantity-grid">
                            <div><span>Previsto</span><strong><?php echo number_format((float)$material->planned_quantity, 3, ',', '.'); ?></strong></div>
                            <div><span>Adicional</span><strong><?php echo number_format((float)$material->additional_quantity, 3, ',', '.'); ?></strong></div>
                            <div><span>Total</span><strong><?php echo number_format($total, 3, ',', '.'); ?></strong></div>
                            <div class="is-available"><span>Disponível</span><strong><?php echo number_format($available, 3, ',', '.'); ?></strong></div>
                        </div>
                    </td>
                    <td class="material-purchase-cell">
                        <label class="material-inline-check">
                            <input type="checkbox" class="form-check-input direct-request-check" value="<?php echo (int)$material->id; ?>" <?php echo $request_available>0?'':'disabled'; ?>>
                            <span>Selecionar</span>
                        </label>
                        <input class="form-control direct-request-quantity" inputmode="decimal" placeholder="Quantidade" data-max="<?php echo number_format($request_available,4,'.',''); ?>" disabled>
                        <div class="small text-off">Saldo: <?php echo number_format($request_available,3,',','.'); ?> <?php echo esc($material->unit); ?></div>
                    </td>
                    <td class="material-batch-cell">
                        <input class="form-control batch-material-quantity" inputmode="decimal" placeholder="Quantidade" data-max="<?php echo number_format($available,4,'.',''); ?>" disabled>
                        <div class="small text-off">Marque a caixa no início da linha.</div>
                    </td>
                    <td class="material-individual-cell">
                        <form class="project-material-allocation-form d-flex gap-1">
                            <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>"><input type="hidden" name="project_material_id" value="<?php echo (int)$material->id; ?>">
                            <select name="task_id" class="form-control select2 project-material-select2 project-material-task-select" data-placeholder="Selecione a tarefa" required><option value=""></option><?php foreach ($tasks as $task) { ?><option value="<?php echo (int)$task->id; ?>"><?php echo esc($task->title); ?></option><?php } ?></select>
                            <input name="quantity" class="form-control w100" placeholder="Qtd." inputmode="decimal" required>
                            <button class="btn btn-default" type="submit">Associar</button>
                        </form>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($material->source === 'manual') { ?>
                            <button type="button" class="btn btn-default btn-sm edit-project-material" data-material-id="<?php echo (int)$material->id; ?>" data-quantity="<?php echo number_format((float)$material->additional_quantity,4,'.',''); ?>" title="Editar quantidade"><i data-feather="edit-2" class="icon-16"></i></button>
                            <button type="button" class="btn btn-danger btn-sm delete-project-material" data-material-id="<?php echo (int)$material->id; ?>" title="Excluir material"><i data-feather="trash-2" class="icon-16"></i></button>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$materials) { ?><tr><td colspan="7" class="text-center p30 text-off">Nenhum material planejado para este projeto.</td></tr><?php } ?>
            <tr id="project-material-search-empty" class="hide"><td colspan="7" class="text-center p30 text-off"><span class="project-material-search-empty-icon" aria-hidden="true">&#128269;</span> Nenhum material encontrado para esta pesquisa.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="mt20">
<?php foreach ($tasks as $task) { $rows=$allocations_by_task[(int)$task->id] ?? array(); if (!$rows) continue; ?>
    <div class="card mb15">
        <div class="card-header"><strong><?php echo esc($task->title); ?></strong><span class="text-off ml10">Materiais desta tarefa</span></div>
        <form class="project-material-request-form">
            <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>"><input type="hidden" name="task_id" value="<?php echo (int)$task->id; ?>">
            <div class="table-responsive"><table class="table mb0"><thead><tr><th></th><th>Material</th><th class="text-end">Necessário</th><th class="text-end">Já requisitado</th><th class="text-end">Falta requisitar</th><th>Solicitar agora</th><th class="text-end">Ações</th></tr></thead><tbody>
            <?php foreach ($rows as $row) { $remaining=max(0,(float)$row->quantity-(float)$row->requested_quantity); ?>
                <tr><td><input type="checkbox" class="form-check-input request-material-check" <?php echo $remaining>0?'':'disabled'; ?>></td><td><?php echo esc($row->description); ?><div class="small text-off"><?php echo esc($row->unit); ?></div></td><td class="text-end"><?php echo number_format((float)$row->quantity,3,',','.'); ?></td><td class="text-end"><?php echo number_format((float)$row->requested_quantity,3,',','.'); ?></td><td class="text-end"><strong><?php echo number_format($remaining,3,',','.'); ?></strong></td><td><input type="hidden" class="allocation-id" value="<?php echo (int)$row->id; ?>"><input class="form-control request-quantity w150" value="<?php echo $remaining>0?number_format($remaining,3,'.',''):''; ?>" inputmode="decimal" <?php echo $remaining>0?'':'disabled'; ?>></td><td class="text-end"><button type="button" class="btn btn-default btn-sm remove-material-allocation" data-allocation-id="<?php echo (int)$row->id; ?>" <?php echo (float)$row->requested_quantity>0?'disabled title="Cancele a requisição antes de desassociar"':'title="Desassociar material"'; ?>><i data-feather="unlink" class="icon-16"></i> Desassociar</button></td></tr>
            <?php } ?></tbody></table></div>
            <div class="p15 d-flex justify-content-end align-items-end gap-2"><div><label>Data necessária</label><input type="date" name="desired_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div><button class="btn btn-primary" type="submit"><i data-feather="shopping-cart" class="icon-16"></i> Criar requisição desta tarefa</button></div>
        </form>
    </div>
<?php } ?>
</div>

<style>
.project-material-search-bar{display:flex;align-items:center;gap:12px;padding:12px 15px;border-bottom:1px solid #e7e9ec;background:#fff}
.project-material-search-bar .input-group{width:min(620px,100%)}
.project-material-search-bar .input-group-text{background:#f8f9fa;border-color:#e1e5eb}
.project-material-search-icon,.project-material-search-empty-icon{font-family:"Segoe UI Emoji","Segoe UI Symbol",sans-serif;font-size:14px;line-height:1}
.project-material-search-bar .form-control{height:40px}
#project-material-search-count{white-space:nowrap}
.project-materials-main-table{table-layout:fixed;width:100%;min-width:1080px}
.project-materials-main-table th{font-size:11px;white-space:normal;vertical-align:bottom}
.project-materials-main-table td{vertical-align:top;padding:14px 10px}
.project-materials-main-table .material-check-column{width:3%}
.project-materials-main-table .material-name-column{width:22%}
.project-materials-main-table .material-quantity-column{width:18%}
.project-materials-main-table .material-purchase-column{width:13%}
.project-materials-main-table .material-batch-column{width:11%}
.project-materials-main-table .material-individual-column{width:28%}
.project-materials-main-table .material-actions-column{width:5%}
.material-title{font-size:13px;font-weight:600;line-height:1.4;color:#2c3e50;overflow-wrap:anywhere}
.material-meta{display:flex;align-items:center;gap:8px;margin-top:7px;color:#6c757d;font-size:11px}
.material-quantity-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
.material-quantity-grid>div{padding:7px 8px;border:1px solid #e8ebef;border-radius:5px;background:#f8f9fa}
.material-quantity-grid span{display:block;color:#7b8490;font-size:9px;text-transform:uppercase;letter-spacing:.03em}
.material-quantity-grid strong{display:block;margin-top:2px;font-size:11px;color:#344054}
.material-quantity-grid .is-available{border-color:#b7e4c7;background:#f1fbf4}
.material-quantity-grid .is-available strong{color:#218838}
.material-inline-check{display:flex;align-items:center;gap:6px;margin-bottom:7px;font-size:11px;cursor:pointer}
.material-purchase-cell .form-control,.material-batch-cell .form-control{width:100%!important}
.project-material-allocation-form{display:grid!important;grid-template-columns:minmax(150px,1fr) 82px auto;gap:6px!important;align-items:start}
.project-material-allocation-form .select2-container{min-width:0!important}
.project-material-allocation-form .form-control{width:100%!important}
@media(max-width:1200px){.project-materials-main-table{min-width:1050px}}

#add-project-material .select2-container{width:100%!important}
#add-project-material .select2-choice{height:38px;line-height:36px}
#project-material-batch-form .select2-container,
.project-material-allocation-form .select2-container{width:100%!important}
#project-material-batch-form .select2-choice{height:38px;line-height:36px}
.project-material-allocation-form{align-items:flex-start}
.project-material-allocation-form .select2-container{min-width:190px;flex:1 1 190px}
</style>
<script>
(function initializeProjectMaterials(attempt){
    if (!window.jQuery || !window.jQuery.fn) {
        if (attempt < 100) window.setTimeout(function(){ initializeProjectMaterials(attempt + 1); }, 50);
        return;
    }
    var $ = window.jQuery;
    $(function(){
    if ($.fn.select2) {
        $('.project-material-select2').each(function(){
            var $select = $(this);
            if (!$select.data('select2') && !$select.hasClass('select2-hidden-accessible')) {
                $select.select2({width:'100%', placeholder:$select.data('placeholder') || ''});
            }
        });
    }
    function normalizeMaterialSearch(value){
        value = String(value || '').toLowerCase();
        if (value.normalize) value = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return value;
    }

    function filterProjectMaterials(){
        var query = normalizeMaterialSearch($('#project-material-search').val());
        var visible = 0;
        var total = 0;

        $('.project-materials-main-table tbody .project-material-row').each(function(){
            total++;
            var $row = $(this);
            var searchable = normalizeMaterialSearch($row.attr('data-material-search') || $row.find('.material-name-cell').text());
            var show = !query || searchable.indexOf(query) !== -1;
            $row.toggle(show);
            if (show) visible++;
        });

        $('#project-material-search-empty').toggleClass('hide', visible > 0 || !total);
        $('#project-material-search-count').text(query ? visible + ' de ' + total + ' material(is)' : total + ' material(is)'); 
    }

    $('#project-material-search').on('input', filterProjectMaterials);
    $('#clear-project-material-search').on('click', function(){
        $('#project-material-search').val('').trigger('input').trigger('focus');
    });
    filterProjectMaterials();

    function send($form,url,data){var $b=$form.find('button[type=submit]');$b.prop('disabled',true);appAjaxRequest({url:url,type:'POST',dataType:'json',data:data||$form.serialize(),success:function(r){if(r&&r.success){if(r.redirect_to){window.location.href=r.redirect_to;}else{location.reload();}}else{appAlert.error((r&&r.message)||'Erro');$b.prop('disabled',false);}},error:function(){appAlert.error('Erro ao processar.');$b.prop('disabled',false);}});}
    $('#project-material-add-form').on('submit',function(e){e.preventDefault();send($(this),'<?php echo_uri('projectanalizer/project_materials/add'); ?>');});
    $('.project-material-allocation-form').on('submit',function(e){e.preventDefault();send($(this),'<?php echo_uri('projectanalizer/project_materials/allocate'); ?>');});
    $('.edit-project-material').on('click',function(){var $button=$(this),quantity=prompt('Informe a nova quantidade total do material:', $button.data('quantity'));if(quantity===null)return;quantity=$.trim(quantity);if(!quantity){appAlert.error('Informe uma quantidade válida.');return;}$button.prop('disabled',true);appAjaxRequest({url:'<?php echo_uri('projectanalizer/project_materials/update_material'); ?>',type:'POST',dataType:'json',data:{project_id:'<?php echo (int)$project_id; ?>',project_material_id:$button.data('material-id'),quantity:quantity},success:function(r){if(r&&r.success){appAlert.success(r.message);location.reload();}else{appAlert.error((r&&r.message)||'Erro');$button.prop('disabled',false);}},error:function(xhr){appAlert.error((xhr.responseJSON&&xhr.responseJSON.message)||'Erro ao processar.');$button.prop('disabled',false);}});});
    $('.delete-project-material').on('click',function(){var $button=$(this);if(!confirm('Deseja excluir este material do projeto?'))return;$button.prop('disabled',true);appAjaxRequest({url:'<?php echo_uri('projectanalizer/project_materials/delete_material'); ?>',type:'POST',dataType:'json',data:{project_id:'<?php echo (int)$project_id; ?>',project_material_id:$button.data('material-id')},success:function(r){if(r&&r.success){appAlert.success(r.message);location.reload();}else{appAlert.error((r&&r.message)||'Erro');$button.prop('disabled',false);}},error:function(xhr){appAlert.error((xhr.responseJSON&&xhr.responseJSON.message)||'Erro ao processar.');$button.prop('disabled',false);}});});
    $(document).off('change.projectMaterialBatchAll','#batch-material-check-all').on('change.projectMaterialBatchAll','#batch-material-check-all',function(){
        $('.batch-material-check:not(:disabled)').prop('checked',this.checked).trigger('change');
    });
    $(document).off('change.projectMaterialBatch','.batch-material-check').on('change.projectMaterialBatch','.batch-material-check',function(){
        var $checkbox=$(this);
        var $quantity=$checkbox.closest('tr').find('.batch-material-quantity');
        var checked=$checkbox.is(':checked');
        $quantity.prop('disabled',!checked).toggleClass('disabled',!checked);
        if(checked){
            if(!$quantity.val()) $quantity.val($quantity.data('max'));
            $quantity.trigger('focus').select();
        }else{
            $quantity.val('');
        }
    });
    $('#project-material-batch-form').on('submit',function(e){
        e.preventDefault();
        var $f=$(this);
        var $taskSelect=$f.find('[name=task_id]');
        var taskId=$taskSelect.val();

        if(!taskId){
            appAlert.error('Selecione uma tarefa antes de associar os materiais.');
            if($.fn.select2){
                $taskSelect.select2('open');
            }else{
                $taskSelect.trigger('focus');
            }
            return;
        }

        var data={project_id:$f.find('[name=project_id]').val(),task_id:taskId,project_material_id:[],quantity:[]};
        $('.batch-material-check:checked').each(function(){
            data.project_material_id.push($(this).val());
            data.quantity.push($(this).closest('tr').find('.batch-material-quantity').val());
        });
        send($f,'<?php echo_uri('projectanalizer/project_materials/allocate_batch'); ?>',data);
    });
    $('.direct-request-check').on('change',function(){var $q=$(this).closest('tr').find('.direct-request-quantity');$q.prop('disabled',!this.checked);if(this.checked&&!$q.val()){$q.val($q.data('max'));}else if(!this.checked){$q.val('');}});
    $('#project-material-direct-request-form').on('submit',function(e){e.preventDefault();var $f=$(this),data={project_id:$f.find('[name=project_id]').val(),desired_date:$f.find('[name=desired_date]').val(),project_material_id:[],request_quantity:[]};$('.direct-request-check:checked').each(function(){data.project_material_id.push($(this).val());data.request_quantity.push($(this).closest('tr').find('.direct-request-quantity').val());});send($f,'<?php echo_uri('projectanalizer/project_materials/create_request'); ?>',data);});
    $('.remove-material-allocation').on('click',function(){var $button=$(this);if(!confirm('Deseja desassociar este material da tarefa?'))return;$button.prop('disabled',true);appAjaxRequest({url:'<?php echo_uri('projectanalizer/project_materials/remove_allocation'); ?>',type:'POST',dataType:'json',data:{project_id:'<?php echo (int)$project_id; ?>',allocation_id:$button.data('allocation-id')},success:function(r){if(r&&r.success){appAlert.success(r.message);location.reload();}else{appAlert.error((r&&r.message)||'Erro');$button.prop('disabled',false);}},error:function(xhr){appAlert.error((xhr.responseJSON&&xhr.responseJSON.message)||'Erro ao processar.');$button.prop('disabled',false);}});});
    $('.project-material-request-form').on('submit',function(e){e.preventDefault();var $f=$(this),data={project_id:$f.find('[name=project_id]').val(),task_id:$f.find('[name=task_id]').val(),desired_date:$f.find('[name=desired_date]').val(),allocation_id:[],request_quantity:[]};$f.find('tbody tr').each(function(){if($(this).find('.request-material-check').is(':checked')){data.allocation_id.push($(this).find('.allocation-id').val());data.request_quantity.push($(this).find('.request-quantity').val());}});send($f,'<?php echo_uri('projectanalizer/project_materials/create_request'); ?>',data);});
    });
})(0);
</script>
