<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo esc($product->title); ?></h4>
            <div class="title-button-group float-end">
                <?php echo anchor(get_uri('fotovoltaico/products'), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang('back'), array('class' => 'btn btn-default')); ?>
                <?php if ($can_manage_products) { ?>
                    <?php echo modal_anchor(get_uri('fotovoltaico/products/modal_form'), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-default', 'data-post-id' => $product->id, 'title' => app_lang('fotovoltaico_edit_product'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-none border">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_category'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->category_title ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_type'); ?></div>
                                <div class="fw-bold"><?php echo esc($product_type_label); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_sku'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->sku ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_distributor'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->distributor_title ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_brand'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->brand ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_model'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->model ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_unit'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->unit ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_warranty'); ?></div>
                                <div class="fw-bold"><?php echo esc($product->warranty ?: '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_power_rating'); ?></div>
                                <div class="fw-bold"><?php echo esc(to_decimal_format($product->power_rating)); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_efficiency'); ?></div>
                                <div class="fw-bold"><?php echo esc(to_decimal_format($product->efficiency)); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_cost_price'); ?></div>
                                <div class="fw-bold"><?php echo esc(to_currency((float) $product->cost_price, 'R$')); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_sale_price'); ?></div>
                                <div class="fw-bold"><?php echo esc(to_currency((float) $product->sale_price, 'R$')); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"><?php echo app_lang('fotovoltaico_product_active'); ?></div>
                                <div class="fw-bold"><?php echo $product->active ? "<span class='badge bg-success'>" . esc(app_lang('active')) . "</span>" : "<span class='badge bg-secondary'>" . esc(app_lang('inactive')) . "</span>"; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-none border">
                    <div class="card-body">
                        <h6 class="mb15"><?php echo app_lang('fotovoltaico_technical_specs'); ?></h6>
                        <?php if (!empty($specs_array)) { ?>
                            <pre class="mb0 bg-light p15 rounded" style="white-space: pre-wrap;"><?php echo esc(json_encode($specs_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                        <?php } else { ?>
                            <div class="text-muted">-</div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
