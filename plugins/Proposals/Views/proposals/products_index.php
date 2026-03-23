<div id="page-content" class="page-wrapper clearfix grid-button">
    <div class="card">
        <div class="page-title clearfix items-page-title">
            <h1><?php echo app_lang('proposals_products'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("propostas/products_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('proposals_add_product'), array("class" => "btn btn-default", "title" => app_lang('proposals_add_product'))); ?>
                <?php if (isset($login_user) && $login_user->is_admin) { ?>
                    <?php echo js_anchor("<i data-feather='refresh-cw' class='icon-16'></i> " . app_lang('proposals_import_ca_items'), array("class" => "btn btn-default", "id" => "contaazul-import-items")); ?>
                    <span id="contaazul-import-items-status" class="text-muted ms-2"></span>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="products-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#products-table").appTable({
            source: '<?php echo_uri("propostas/products_list_data"); ?>',
            order: [[0, 'asc']],
            columns: [
                {title: "<?php echo app_lang('title'); ?>", "class": "w30p all"},
                {title: "<?php echo app_lang('proposals_ca_code'); ?>", "class": "w15p"},
                {title: "<?php echo app_lang('proposals_unit'); ?>", "class": "w10p"},
                {title: "<?php echo app_lang('proposals_cost'); ?>", "class": "text-right w10p"},
                {title: "<?php echo app_lang('proposals_sale'); ?>", "class": "text-right w10p"},
                {title: "<?php echo app_lang('proposals_markup'); ?>", "class": "text-right w10p"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w10p"}
            ]
        });

        $("#contaazul-import-items").on("click", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $status = $("#contaazul-import-items-status");
            $btn.prop("disabled", true);
            $status.removeClass("text-danger text-success").text("<?php echo app_lang('processing'); ?>...");

            $.post("<?php echo get_uri('contaazul/import-items'); ?>", {}, function (response) {
                if (response && response.success) {
                    var msg = "Importacao finalizada: " + (response.imported || 0) + " importados, " + (response.updated || 0) + " atualizados.";
                    $status.addClass("text-success").text(msg);
                    $("#products-table").appTable({reload: true});
                } else {
                    $status.addClass("text-danger").text((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }).fail(function () {
                $status.addClass("text-danger").text("<?php echo app_lang('error_occurred'); ?>");
            }).always(function () {
                $btn.prop("disabled", false);
            });
        });
    });
</script>
