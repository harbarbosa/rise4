<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Categorias de Despesas</h1>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri('travelrefunds/categories/save'), array('class' => 'general-form')); ?>
                <input type="hidden" name="id" value="<?php echo $category_edit->id ?? ''; ?>" />
                <div class="row">
                    <div class="col-md-4 mb10">
                        <label>Titulo</label>
                        <input type="text" name="title" class="form-control" value="<?php echo esc($category_edit->title ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Ordem</label>
                        <input type="number" name="sort" class="form-control" value="<?php echo esc($category_edit->sort ?? '0'); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label class="d-block">Ativa</label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="is_active" value="1" <?php echo (($category_edit->is_active ?? 1) ? 'checked' : ''); ?> />
                            Sim
                        </label>
                    </div>
                    <div class="col-md-4 mb10">
                        <label class="d-block">NF obrigatoria</label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="requires_invoice" value="1" <?php echo (($category_edit->requires_invoice ?? 0) ? 'checked' : ''); ?> />
                            Sim
                        </label>
                    </div>
                    <div class="col-md-8 mb10">
                        <label>Descricao</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo esc($category_edit->description ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Lista de categorias</h4></div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                        <tr>
                            <th>Titulo</th>
                            <th>Descricao</th>
                            <th>NF obrigatoria</th>
                            <th>Status</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) { ?>
                            <tr>
                                <td><?php echo esc($category->title); ?></td>
                                <td><?php echo esc($category->description); ?></td>
                                <td><?php echo $category->requires_invoice ? 'Sim' : 'Nao'; ?></td>
                                <td><?php echo $category->is_active ? 'Ativa' : 'Inativa'; ?></td>
                                <td class="text-end">
                                    <a href="<?php echo get_uri('travelrefunds/categories?edit_id=' . $category->id); ?>" class="btn btn-default btn-sm">Editar</a>
                                <?php echo form_open(get_uri('travelrefunds/categories/delete/' . $category->id), array('class' => 'd-inline')); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Excluir esta categoria?');">Excluir</button>
                                <?php echo form_close(); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
