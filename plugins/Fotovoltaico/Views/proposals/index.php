<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_proposals'); ?></h4>
            <div class="title-button-group float-end">
                <?php if ($can_create_proposals || $can_manage_proposals) { ?>
                    <?php echo anchor(get_uri('fotovoltaico/proposal_wizard/start'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fotovoltaico_add_proposal'), array('class' => 'btn btn-default')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="proposals-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#proposals-table").appTable({
            source: '<?php echo_uri("fotovoltaico/proposals/list_data") ?>',
            order: [[7, 'desc']],
            filterDropdown: [
                {name: "status", class: "w200", options: <?php echo $status_dropdown; ?>}
            ],
            columns: [
                {title: "<?php echo app_lang('fotovoltaico_proposal_title') ?>", "class": "w20p"},
                {title: "<?php echo app_lang('fotovoltaico_proposal_client') ?>", "class": "w20p"},
                {title: "<?php echo app_lang('fotovoltaico_proposal_consumer_unit') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_proposal_distributor') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_proposal_current_version') ?>", "class": "text-center w90"},
                {title: "<?php echo app_lang('status') ?>", "class": "text-center w120"},
                {title: "<?php echo app_lang('total') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_updated_at') ?>", "class": "w120"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w80"}
            ]
        });
    });
</script>
