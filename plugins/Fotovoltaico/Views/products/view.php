<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_product'); ?>: <?php echo esc($item->brand . ' ' . $item->model); ?></h1>
            </div>

            <div class="card p20">
                <p><strong><?php echo app_lang('type'); ?>:</strong> <?php echo esc($item->type); ?></p>
                <p><strong><?php echo app_lang('sku'); ?>:</strong> <?php echo esc($item->sku ?? '-'); ?></p>
                <p><strong><?php echo app_lang('power_w'); ?>:</strong> <?php echo esc($item->power_w ?? '-'); ?></p>
                <p><strong><?php echo app_lang('cost'); ?>:</strong> <?php echo to_currency($item->cost ?? 0); ?></p>
                <p><strong><?php echo app_lang('price'); ?>:</strong> <?php echo to_currency($item->price ?? 0); ?></p>
                <p><strong><?php echo app_lang('warranty'); ?>:</strong> <?php echo esc($item->warranty_years ?? '-'); ?></p>
                <p><strong>Datasheet:</strong> <?php echo esc($item->datasheet_url ?? '-'); ?></p>
            </div>

            <div class="card p20 mt15">
                <h4><?php echo app_lang('fv_specs'); ?></h4>
                <?php if (!empty($item->specs) && is_array($item->specs)) { ?>
                    <ul class="list-group">
                        <?php foreach ($item->specs as $key => $value) { ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong><?php echo esc($key); ?></strong>
                                <span><?php echo esc($value); ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <div class="text-muted"><?php echo app_lang('no_record_found'); ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
