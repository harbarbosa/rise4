<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Configuracoes</h1>
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
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
