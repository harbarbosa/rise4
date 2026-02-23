<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_kits'); ?></h1>
                <div class="title-button-group">
                    <?php echo modal_anchor(get_uri('fotovoltaico/kits_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fv_new_kit'), array('class' => 'btn btn-default', 'title' => app_lang('fv_new_kit'))); ?>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table id="fv-kits-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var filterDropdowns = [
            {name: "is_active", class: "w150", options: {"": "-", "1": "<?php echo app_lang('yes'); ?>", "0": "<?php echo app_lang('no'); ?>"}}
        ];

        $("#fv-kits-table").appTable({
            source: '<?php echo_uri("fotovoltaico/kits_list_data"); ?>',
            filterDropdown: filterDropdowns,
            columns: [
                {title: '<?php echo app_lang("active"); ?>'},
                {title: '<?php echo app_lang("name"); ?>'},
                {title: '<?php echo app_lang("fv_default_losses"); ?>'},
                {title: '<?php echo app_lang("fv_default_markup"); ?>'},
                {title: '<?php echo app_lang("fv_items"); ?>'},
                {title: '<?php echo app_lang("fv_power_kwp"); ?>'},
                {title: '<?php echo app_lang("cost"); ?>'},
                {title: '<?php echo app_lang("price"); ?>'},
                {title: '<?php echo app_lang("actions"); ?>', "class": "text-center option w250"}
            ]
        });

        $("body").on("click", ".js-toggle-active", function () {
            var $btn = $(this);
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/kits_toggle_active'); ?>",
                type: "POST",
                dataType: "json",
                data: {id: $btn.data("id"), is_active: $btn.data("active")}
            }).done(function () {
                $("#fv-kits-table").appTable({newData: null});
            });
        });
    });
</script>
