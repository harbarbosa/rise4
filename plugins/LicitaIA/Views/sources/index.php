<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('licitaia_sources'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_manage ?? false) { ?>
                    <?php echo modal_anchor(get_uri('licitaia/sources/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('licitaia_new_source'), array('class' => 'btn btn-primary', 'title' => app_lang('licitaia_new_source'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="licitaia-sources-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#licitaia-sources-table").appTable({
            source: "<?php echo_uri('licitaia/sources/list_data'); ?>",
            order: [[0, "asc"]],
            columns: [
                {title: "<?php echo app_lang('licitaia_source_name'); ?>"},
                {title: "<?php echo app_lang('licitaia_source_type'); ?>"},
                {title: "<?php echo app_lang('licitaia_source_url'); ?>"},
                {title: "<?php echo app_lang('licitaia_source_city'); ?>"},
                {title: "<?php echo app_lang('licitaia_source_state'); ?>"},
                {title: "<?php echo app_lang('licitaia_source_frequency'); ?>"},
                {title: "<?php echo app_lang('licitaia_source_last_search'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("body").on("click", ".js-source-action, .js-source-toggle-status", function (e) {
            e.preventDefault();

            var $button = $(this);
            var postData = {
                id: $button.attr("data-id")
            };

            if ($button.hasClass("js-source-toggle-status")) {
                postData.active = $button.attr("data-active");
            }

            appAjaxRequest({
                url: $button.attr("data-action-url"),
                type: "POST",
                dataType: "json",
                data: postData,
                success: function (result) {
                    if (result && result.success) {
                        appAlert.success(result.message);
                        $("#licitaia-sources-table").appTable({reload: true});
                    } else {
                        appAlert.error(result && result.message ? result.message : "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });
    });
</script>
