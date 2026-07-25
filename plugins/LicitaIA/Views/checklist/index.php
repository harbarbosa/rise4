<?php
$categories_dropdown = $categories_dropdown ?? array();
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('licitaia_checklist'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_manage ?? false) { ?>
                    <?php echo modal_anchor(get_uri('licitaia/checklist/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('licitaia_new_checklist_item'), array('class' => 'btn btn-primary', 'title' => app_lang('licitaia_new_checklist_item'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <label><?php echo app_lang('licitaia_category'); ?></label>
                    <?php echo form_dropdown('category_filter', $categories_dropdown, '', 'class="form-control select2" id="licitaia-checklist-category-filter"'); ?>
                </div>
                <div class="col-md-4">
                    <label><?php echo app_lang('status'); ?></label>
                    <?php echo form_dropdown('active_filter', array('' => '-', 'active' => app_lang('yes'), 'inactive' => app_lang('no')), '', 'class="form-control select2" id="licitaia-checklist-active-filter"'); ?>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="licitaia-checklist-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#licitaia-checklist-category-filter, #licitaia-checklist-active-filter").select2();

        $("#licitaia-checklist-table").appTable({
            source: "<?php echo_uri('licitaia/checklist/list_data'); ?>",
            postData: function () {
                return {
                    category: $("#licitaia-checklist-category-filter").val(),
                    status: $("#licitaia-checklist-active-filter").val(),
                };
            },
            columns: [
                {title: "<?php echo app_lang('licitaia_checklist_item'); ?>"},
                {title: "<?php echo app_lang('licitaia_category'); ?>"},
                {title: "<?php echo app_lang('licitaia_checklist_description'); ?>"},
                {title: "<?php echo app_lang('licitaia_required'); ?>"},
                {title: "<?php echo app_lang('active'); ?>"},
                {title: "<?php echo app_lang('sort'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            onSuccess: function () {
                $("#licitaia-checklist-table").appTable({reload: true});
            }
        });

        $("#licitaia-checklist-category-filter, #licitaia-checklist-active-filter").change(function () {
            $("#licitaia-checklist-table").appTable({reload: true});
        });
    });
</script>
