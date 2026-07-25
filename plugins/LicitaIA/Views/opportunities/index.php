<?php
$status_dropdown = $status_dropdown ?? array();
$sources_dropdown = $sources_dropdown ?? array();
$responsible_dropdown = $responsible_dropdown ?? array();
?>

<div id="page-content" class="page-wrapper clearfix grid-button">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('licitaia_opportunities'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_manage ?? false) { ?>
                    <?php echo modal_anchor(get_uri('licitaia/opportunities/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('licitaia_new_opportunity'), array('class' => 'btn btn-primary', 'title' => app_lang('licitaia_new_opportunity'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('licitaia_status'); ?></label>
                    <?php echo form_dropdown('status', $status_dropdown, '', 'class="form-control select2" id="licitaia-opportunity-status"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('licitaia_source'); ?></label>
                    <?php echo form_dropdown('source_id', $sources_dropdown, '', 'class="form-control select2" id="licitaia-opportunity-source"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('licitaia_responsible'); ?></label>
                    <?php echo form_dropdown('responsible_user_id', $responsible_dropdown, '', 'class="form-control select2" id="licitaia-opportunity-responsible"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('search'); ?></label>
                    <input type="text" id="licitaia-opportunity-search" class="form-control" />
                </div>
                <div class="col-12">
                    <button type="button" id="licitaia-opportunity-filter-btn" class="btn btn-primary btn-sm"><?php echo app_lang('filter'); ?></button>
                    <button type="button" id="licitaia-opportunity-clear-btn" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="licitaia-opportunities-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function licitaiaOpportunityFilters() {
        return {
            status: $("#licitaia-opportunity-status").val(),
            source_id: $("#licitaia-opportunity-source").val(),
            responsible_user_id: $("#licitaia-opportunity-responsible").val(),
            search: $("#licitaia-opportunity-search").val()
        };
    }

    function reloadLicitaiaOpportunitiesTable() {
        if (window.InstanceCollection && window.InstanceCollection["licitaia-opportunities-table"]) {
            window.InstanceCollection["licitaia-opportunities-table"].filterParams = $.extend({datatable: true}, licitaiaOpportunityFilters());
        }
        $("#licitaia-opportunities-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $(".select2").select2();

        $("#licitaia-opportunities-table").appTable({
            source: "<?php echo_uri('licitaia/opportunities/list_data'); ?>",
            filterParams: $.extend({datatable: true}, licitaiaOpportunityFilters()),
            columns: [
                {title: "<?php echo app_lang('licitaia_opportunity'); ?>"},
                {title: "<?php echo app_lang('licitaia_public_body'); ?>"},
                {title: "<?php echo app_lang('licitaia_edital_number'); ?>"},
                {title: "<?php echo app_lang('licitaia_process_number'); ?>"},
                {title: "<?php echo app_lang('licitaia_modality'); ?>"},
                {title: "<?php echo app_lang('licitaia_source'); ?>"},
                {title: "<?php echo app_lang('licitaia_status'); ?>"},
                {title: "<?php echo app_lang('licitaia_responsible'); ?>"},
                {title: "<?php echo app_lang('licitaia_deadline'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#licitaia-opportunity-filter-btn").on("click", function () {
            reloadLicitaiaOpportunitiesTable();
        });

        $("#licitaia-opportunity-clear-btn").on("click", function () {
            $("#licitaia-opportunity-status").val("").trigger("change");
            $("#licitaia-opportunity-source").val("").trigger("change");
            $("#licitaia-opportunity-responsible").val("").trigger("change");
            $("#licitaia-opportunity-search").val("");
            reloadLicitaiaOpportunitiesTable();
        });
    });
</script>
