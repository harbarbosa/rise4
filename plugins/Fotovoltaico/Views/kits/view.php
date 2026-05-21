<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo esc($kit->title); ?></h4>
            <div class="title-button-group float-end">
                <?php echo anchor(get_uri('fotovoltaico/kits'), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang('back'), array('class' => 'btn btn-default')); ?>
                <?php if ($can_manage_kits) { ?>
                    <?php echo modal_anchor(get_uri('fotovoltaico/kits/modal_form'), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-default', 'data-post-id' => $kit->id, 'title' => app_lang('fotovoltaico_edit_kit'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-none border">
                    <div class="card-body">
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('status'); ?></div>
                            <div><?php echo $status_label; ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_kit_code'); ?></div>
                            <div class="fw-bold"><?php echo esc($kit->code ?: '-'); ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_product_category'); ?></div>
                            <div class="fw-bold"><?php echo esc($kit->category_title ?: '-'); ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_product_distributor'); ?></div>
                            <div class="fw-bold"><?php echo esc($kit->distributor_title ?: '-'); ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_kit_power_kwp'); ?></div>
                            <div class="fw-bold"><?php echo esc(to_decimal_format($kit->power_kwp)); ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_kit_total_cost'); ?></div>
                            <div class="fw-bold"><?php echo esc(to_currency((float) $totals['total_cost'], 'R$')); ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_kit_total_price'); ?></div>
                            <div class="fw-bold"><?php echo esc(to_currency((float) $totals['total_price'], 'R$')); ?></div>
                        </div>
                        <div class="mb15">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_kit_margin'); ?></div>
                            <div class="fw-bold"><?php echo esc(to_currency((float) $totals['margin_value'], 'R$')); ?> (<?php echo esc(number_format((float) $totals['margin_percent'], 2, ',', '.')); ?>%)</div>
                        </div>
                        <div class="mb0">
                            <div class="text-muted"><?php echo app_lang('fotovoltaico_notes'); ?></div>
                            <div><?php echo nl2br(esc($kit->notes ?: '-')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php echo view('Fotovoltaico\\Views\\kits\\bom', array(
                    'kit' => $kit,
                    'product_options' => $product_options,
                    'product_lookup_json' => $product_lookup_json,
                    'can_manage_kits' => $can_manage_kits
                )); ?>
            </div>
        </div>
    </div>
</div>
