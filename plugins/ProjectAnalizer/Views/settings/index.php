<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "projectanalizer";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo app_lang("labor_profiles"); ?></h4>
                    <div class="title-button-group">
                        <?php
                        echo modal_anchor(
                            get_uri("projectanalizer_settings/labor_profile_modal_form"),
                            "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("labor_add_profile"),
                            array("class" => "btn btn-default", "title" => app_lang("labor_add_profile"))
                        );
                        echo anchor(
                            get_uri("projectanalizer_settings/logs"),
                            "<i data-feather='file-text' class='icon-16'></i> " . app_lang("pa_error_logs"),
                            array("class" => "btn btn-default ms-2", "title" => app_lang("pa_error_logs"))
                        );
                        ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="labor-profiles-table" class="display" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#labor-profiles-table").appTable({
            source: '<?php echo_uri("projectanalizer_settings/labor_profiles_list_data") ?>',
            columns: [
                {title: "<?php echo app_lang('labor_profile'); ?>"},
                {title: "<?php echo app_lang('labor_hourly_cost'); ?>", "class": "text-right w120"},
                {title: "<?php echo app_lang('labor_default_hours_per_day'); ?>", "class": "text-right w120"},
                {title: "<?php echo app_lang('status'); ?>", "class": "text-center w120"},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ]
        });
    });
</script>
