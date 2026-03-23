<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><?= app_lang("team_activities") ?></h4>
        <?php if ($can_add_activity): ?>
            <?php echo modal_anchor(get_uri("projectanalizer/team_activity_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_activity") , array("class" => "btn btn-primary", "title" => app_lang('add_activity'))); ?>
           
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table id="team-activities-table" class="display dataTable no-footer" width="100%">
            <thead>
                <tr>
                    <th><?= app_lang("member") ?></th>
                    <th><?= app_lang("task") ?></th>
                    <th><?= app_lang("date") ?></th>
                    <th>Percentual Executado</th>
                    <th><?= app_lang("hours") ?></th>
                    <th><?= app_lang("description") ?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $("#team-activities-table").appTable({
    source: '<?= get_uri("projectanalizer/team_activities_list/" . $project_id) ?>',
    order: [[2, "desc"]],
    columns: [
        {title: "<?= app_lang('members') ?>", "class": "w100"},
        {title: "<?= app_lang('task') ?>"},
        {title: "<?= app_lang('date') ?>"},
        {title: "Percentual Executado", "class": "w120 text-right"},
        {title: "<?= app_lang('total_hours') ?>"},
        {title: "<?= app_lang('description') ?>"},
        {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w80"}
    ]
    }); 

    $("#add-activity-btn").click(function () {
        appModal({
            url: "<?= get_uri("projectanalizer/team_activity_modal_form/" . $project_id) ?>",
            title: "<?= app_lang("add_activity") ?>"
        });
    });
});
</script>

