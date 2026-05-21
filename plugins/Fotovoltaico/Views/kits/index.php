<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_kits'); ?></h4>
            <div class="title-button-group float-end">
                <?php if ($can_manage_kits) { ?>
                    <?php echo modal_anchor(get_uri("fotovoltaico/kits/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fotovoltaico_add_kit'), array("class" => "btn btn-default", "title" => app_lang('fotovoltaico_add_kit'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="kits-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#kits-table").appTable({
            source: '<?php echo_uri("fotovoltaico/kits/list_data") ?>',
            order: [[0, 'asc']],
            filterDropdown: [
                {name: "category_id", class: "w200", options: <?php echo $categories_dropdown; ?>},
                {name: "status", class: "w150", options: <?php echo $status_dropdown; ?>}
            ],
            columns: [
                {title: "<?php echo app_lang('fotovoltaico_kit_name') ?>", "class": "w20p"},
                {title: "<?php echo app_lang('fotovoltaico_kit_code') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_product_category') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_kit_power_kwp') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('status') ?>", "class": "text-center w100"},
                {title: "<?php echo app_lang('fotovoltaico_kit_total_cost') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_kit_total_price') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_kit_margin') ?>", "class": "text-right w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });
    });
</script>
