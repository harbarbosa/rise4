<?php echo form_open(get_uri('laudostecnicos/normas/save'), array('id' => 'laudostecnicos-norm-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Codigo</label><input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>" required></div>
        <div class="col-md-8"><label class="form-label">Titulo</label><input type="text" name="title" class="form-control" value="<?php echo esc($model_info->title ?? ''); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Instituicao</label><input type="text" name="institution" class="form-control" value="<?php echo esc($model_info->institution ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Categoria</label><input type="text" name="category" class="form-control" value="<?php echo esc($model_info->category ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label">Edicao</label><input type="text" name="edition" class="form-control" value="<?php echo esc($model_info->edition ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label">Ano</label><input type="number" name="year" class="form-control" value="<?php echo esc($model_info->year ?? ''); ?>"></div>
        <div class="col-md-12"><label class="form-label">Descricao</label><textarea name="description" class="form-control"><?php echo esc($model_info->description ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Link</label><input type="text" name="link" class="form-control" value="<?php echo esc($model_info->link ?? ''); ?>"></div>
        <div class="col-md-6"><label class="form-label">Arquivo autorizado</label><input type="text" name="authorized_file" class="form-control" value="<?php echo esc($model_info->authorized_file ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Status</label><input type="text" name="status" class="form-control" value="<?php echo esc($model_info->status ?? 'active'); ?>"></div>
        <div class="col-md-8"><label class="form-label">Observacao</label><input type="text" name="observation" class="form-control" value="<?php echo esc($model_info->observation ?? ''); ?>"></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>$("#laudostecnicos-norm-form").appForm();</script>
