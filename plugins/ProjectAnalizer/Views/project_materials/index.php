<div class="card">
    <div class="page-title clearfix">
        <h1><?php echo app_lang('project_materials'); ?></h1>
        <div class="title-button-group"><button class="btn btn-default" data-bs-toggle="collapse" data-bs-target="#add-project-material"><i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add'); ?></button></div>
    </div>
    <div id="add-project-material" class="collapse p15 border-bottom">
        <form id="project-material-add-form" class="row g-2">
            <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
            <div class="col-md-4"><label>Material cadastrado</label><select name="item_id" class="form-control select2"><option value="">- Material fora da lista -</option><?php foreach ($catalog_items as $item) { ?><option value="<?php echo (int)$item->id; ?>" data-unit="<?php echo esc($item->unit_type); ?>"><?php echo esc($item->title); ?></option><?php } ?></select></div>
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
                <select name="task_id" class="form-control" required>
                    <option value="">Selecione a tarefa</option>
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
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead><tr><th class="w50"><input type="checkbox" id="batch-material-check-all" class="form-check-input"></th><th>Material</th><th>Origem</th><th class="text-end">Previsto</th><th class="text-end">Adicional</th><th class="text-end">Total</th><th class="text-end">Disponível</th><th>Qtd. em lote</th><th>Associar individualmente</th></tr></thead>
            <tbody>
            <?php foreach ($materials as $material) { $total=(float)$material->planned_quantity+(float)$material->additional_quantity; $available=max(0,$total-(float)$material->allocated_quantity); ?>
                <tr>
                    <td><input type="checkbox" class="form-check-input batch-material-check" value="<?php echo (int)$material->id; ?>" <?php echo $available>0?'':'disabled'; ?>></td>
                    <td><strong><?php echo esc($material->description); ?></strong><div class="text-off small"><?php echo esc($material->unit); ?></div></td>
                    <td><span class="badge bg-<?php echo $material->source === 'proposal' ? 'info' : 'secondary'; ?>"><?php echo $material->source === 'proposal' ? 'Proposta' : 'Adicional'; ?></span></td>
                    <td class="text-end"><?php echo number_format((float)$material->planned_quantity, 3, ',', '.'); ?></td>
                    <td class="text-end"><?php echo number_format((float)$material->additional_quantity, 3, ',', '.'); ?></td>
                    <td class="text-end"><strong><?php echo number_format($total, 3, ',', '.'); ?></strong></td>
                    <td class="text-end"><strong><?php echo number_format($available, 3, ',', '.'); ?></strong></td>
                    <td><input class="form-control batch-material-quantity w120" inputmode="decimal" placeholder="Qtd." data-max="<?php echo number_format($available,4,'.',''); ?>" disabled></td>
                    <td>
                        <form class="project-material-allocation-form d-flex gap-1">
                            <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>"><input type="hidden" name="project_material_id" value="<?php echo (int)$material->id; ?>">
                            <select name="task_id" class="form-control" required><option value="">Tarefa</option><?php foreach ($tasks as $task) { ?><option value="<?php echo (int)$task->id; ?>"><?php echo esc($task->title); ?></option><?php } ?></select>
                            <input name="quantity" class="form-control w100" placeholder="Qtd." inputmode="decimal" required>
                            <button class="btn btn-default" type="submit">Associar</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$materials) { ?><tr><td colspan="9" class="text-center p30 text-off">Nenhum material planejado para este projeto.</td></tr><?php } ?>
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

<script>
$(function(){
    if ($.fn.select2) $('.select2').select2();
    function send($form,url,data){var $b=$form.find('button[type=submit]');$b.prop('disabled',true);appAjaxRequest({url:url,type:'POST',dataType:'json',data:data||$form.serialize(),success:function(r){if(r&&r.success){if(r.redirect_to){window.location.href=r.redirect_to;}else{location.reload();}}else{appAlert.error((r&&r.message)||'Erro');$b.prop('disabled',false);}},error:function(){appAlert.error('Erro ao processar.');$b.prop('disabled',false);}});}
    $('#project-material-add-form').on('submit',function(e){e.preventDefault();send($(this),'<?php echo_uri('projectanalizer/project_materials/add'); ?>');});
    $('.project-material-allocation-form').on('submit',function(e){e.preventDefault();send($(this),'<?php echo_uri('projectanalizer/project_materials/allocate'); ?>');});
    $('#batch-material-check-all').on('change',function(){$('.batch-material-check:not(:disabled)').prop('checked',this.checked).trigger('change');});
    $('.batch-material-check').on('change',function(){var $q=$(this).closest('tr').find('.batch-material-quantity');$q.prop('disabled',!this.checked);if(this.checked&&!$q.val()){$q.val($q.data('max'));}else if(!this.checked){$q.val('');}});
    $('#project-material-batch-form').on('submit',function(e){e.preventDefault();var $f=$(this),data={project_id:$f.find('[name=project_id]').val(),task_id:$f.find('[name=task_id]').val(),project_material_id:[],quantity:[]};$('.batch-material-check:checked').each(function(){data.project_material_id.push($(this).val());data.quantity.push($(this).closest('tr').find('.batch-material-quantity').val());});send($f,'<?php echo_uri('projectanalizer/project_materials/allocate_batch'); ?>',data);});
    $('.remove-material-allocation').on('click',function(){var $button=$(this);if(!confirm('Deseja desassociar este material da tarefa?'))return;$button.prop('disabled',true);appAjaxRequest({url:'<?php echo_uri('projectanalizer/project_materials/remove_allocation'); ?>',type:'POST',dataType:'json',data:{project_id:'<?php echo (int)$project_id; ?>',allocation_id:$button.data('allocation-id')},success:function(r){if(r&&r.success){appAlert.success(r.message);location.reload();}else{appAlert.error((r&&r.message)||'Erro');$button.prop('disabled',false);}},error:function(xhr){appAlert.error((xhr.responseJSON&&xhr.responseJSON.message)||'Erro ao processar.');$button.prop('disabled',false);}});});
    $('.project-material-request-form').on('submit',function(e){e.preventDefault();var $f=$(this),data={project_id:$f.find('[name=project_id]').val(),task_id:$f.find('[name=task_id]').val(),desired_date:$f.find('[name=desired_date]').val(),allocation_id:[],request_quantity:[]};$f.find('tbody tr').each(function(){if($(this).find('.request-material-check').is(':checked')){data.allocation_id.push($(this).find('.allocation-id').val());data.request_quantity.push($(this).find('.request-quantity').val());}});send($f,'<?php echo_uri('projectanalizer/project_materials/create_request'); ?>',data);});
});
</script>
