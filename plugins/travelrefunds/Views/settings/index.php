<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Configuracoes</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds/categories'), '<i data-feather="layers" class="icon-16"></i> Categorias', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports'), '<i data-feather="bar-chart-2" class="icon-16"></i> Relatorios', array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <p class="text-muted mb-0">As configuracoes abaixo controlam regras globais do modulo, aprovadores e comportamento das despesas.</p>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri('travelrefunds/settings/save'), array('class' => 'general-form')); ?>
                <div class="row">
                    <div class="col-md-4 mb10">
                        <label class="d-block">Modulo habilitado</label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="travelrefunds_enabled" value="1" <?php echo (($settings['travelrefunds_enabled'] ?? '1') === '1') ? 'checked' : ''; ?> />
                            Sim
                        </label>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Simbolo padrao de moeda</label>
                        <input type="text" name="travelrefunds_default_currency_symbol" class="form-control" value="<?php echo esc($settings['travelrefunds_default_currency_symbol'] ?? '$'); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label class="d-block">Permitir comprovantes publicos</label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="travelrefunds_allow_public_receipts" value="1" <?php echo (($settings['travelrefunds_allow_public_receipts'] ?? '0') === '1') ? 'checked' : ''; ?> />
                            Sim
                        </label>
                    </div>
                    <div class="col-md-4 mb10">
                        <label class="d-block">Permitir despesas sem comprovante</label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="travelrefunds_allow_expenses_without_receipt" value="1" <?php echo (($settings['travelrefunds_allow_expenses_without_receipt'] ?? '1') === '1') ? 'checked' : ''; ?> />
                            Sim
                        </label>
                    </div>
                    <div class="col-md-8 mb10">
                        <label>Aprovadores padrao</label>
                        <select name="default_approver_ids[]" class="form-control select2" multiple="multiple" style="width:100%;">
                            <?php foreach ($users as $user) { ?>
                                <?php
                                $user_id = (int) $user->id;
                                $selected = in_array($user_id, $selected_approver_ids ?? array(), true) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $user_id; ?>" <?php echo $selected; ?>>
                                    <?php echo esc($user->first_name . ' ' . $user->last_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <small class="text-muted">Usado como sugerencia para o fluxo de aprovacao.</small>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Limite sem aprovacao especial</label>
                        <input type="text" name="travelrefunds_special_approval_limit" class="form-control money" value="<?php echo esc($settings['travelrefunds_special_approval_limit'] ?? '0'); ?>" />
                    </div>
                    <div class="col-md-12 mb10">
                        <label class="d-block">Categorias de despesas</label>
                        <a href="<?php echo get_uri('travelrefunds/categories'); ?>" class="btn btn-default btn-sm">Gerenciar categorias</a>
                        <small class="text-muted d-block mt-2">A obrigatoriedade de NF e configurada por categoria.</small>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
    });
</script>
