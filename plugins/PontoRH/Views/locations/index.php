<?php
$active_dropdown = $status_dropdown ?? array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_locations'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage)) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/locais/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add') . ' ' . app_lang('pontorh_location'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_location'), 'data-modal-lg' => '1')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><?php echo app_lang('search'); ?></label>
                    <input type="text" id="pontorh-filter-search" class="form-control" autocomplete="off" placeholder="<?php echo app_lang('search'); ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('status'); ?></label>
                    <?php echo form_dropdown('active', $active_dropdown, '', 'class="form-control select2" id="pontorh-filter-status"'); ?>
                </div>
                <div class="col-md-5">
                    <button type="button" id="pontorh-filter-btn" class="btn btn-primary btn-sm me-2"><?php echo app_lang('filter'); ?></button>
                    <button type="button" id="pontorh-clear-btn" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-locations-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhLocationsFilters() {
        return {
            search: $("#pontorh-filter-search").val(),
            active: $("#pontorh-filter-status").val()
        };
    }

    function reloadPontorhLocationsTable() {
        if (window.InstanceCollection && window.InstanceCollection["pontorh-locations-table"]) {
            window.InstanceCollection["pontorh-locations-table"].filterParams = $.extend({datatable: true}, pontorhLocationsFilters());
        }
        $("#pontorh-locations-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $(".page-wrapper .select2").select2();

        $("#pontorh-locations-table").appTable({
            source: "<?php echo_uri('pontorh/locais/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhLocationsFilters()),
            order: [[0, "asc"]],
            columns: [
                {title: "<?php echo app_lang('name'); ?>"},
                {title: "<?php echo app_lang('address'); ?>"},
                {title: "Latitude"},
                {title: "Longitude"},
                {title: "<?php echo app_lang('pontorh_allowed_radius_meters'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ]
        });

        $("#pontorh-filter-btn").on("click", function () {
            reloadPontorhLocationsTable();
        });

        $("#pontorh-clear-btn").on("click", function () {
            $("#pontorh-filter-search").val('');
            $("#pontorh-filter-status").val('').trigger('change');
            reloadPontorhLocationsTable();
        });
    });
</script>
