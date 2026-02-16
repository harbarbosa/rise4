<?php
load_css(array(
    "assets/css/app.all.css"
));
?>

<div style="padding:20px;">
    <h2><?php echo app_lang("evolucao_ff"); ?></h2>
    <h4><?php echo $project_info->title; ?></h4>

    <hr />

    <h5><?php echo app_lang("physical_progress"); ?>: <?php echo round($summary["project_actual_percent"], 2); ?>%</h5>
    <h5><?php echo app_lang("planned_today"); ?>: <?php echo round($summary["project_planned_percent"], 2); ?>%</h5>
    <h5><?php echo app_lang("planned_financial_today"); ?>: <?php echo to_currency($summary["financial_planned_today"]); ?></h5>
    <h5><?php echo app_lang("realized_financial_today"); ?>: <?php echo to_currency($summary["financial_realized_today"]); ?></h5>

    <hr />

    <h4><?php echo app_lang("schedule"); ?></h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th><?php echo app_lang("milestone"); ?></th>
            <th><?php echo app_lang("planned"); ?></th>
            <th><?php echo app_lang("progress"); ?></th>
            <th><?php echo app_lang("planned_financial"); ?></th>
            <th><?php echo app_lang("tasks"); ?></th>
            <th><?php echo app_lang("completed"); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($summary["milestones"])) { ?>
            <?php foreach ($summary["milestones"] as $milestone) { ?>
                <tr>
                    <td><?php echo $milestone["title"]; ?></td>
                    <td><?php echo $milestone["planned_percent"]; ?>%</td>
                    <td><?php echo $milestone["actual_percent"]; ?>%</td>
                    <td><?php echo to_currency($milestone["planned_financial"]); ?></td>
                    <td><?php echo $milestone["tasks_count"]; ?></td>
                    <td><?php echo $milestone["completed_count"]; ?></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="6"><?php echo app_lang("no_data"); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <h4><?php echo app_lang("planned_costs"); ?></h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th><?php echo app_lang("tasks"); ?></th>
            <th><?php echo app_lang("cost_type"); ?></th>
            <th><?php echo app_lang("planned_value"); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $task_title_map = array();
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $task_title_map[$task->id] = $task->title;
            }
        }
        ?>
        <?php if (!empty($task_costs)) { ?>
            <?php foreach ($task_costs as $cost) { ?>
                <tr>
                    <td><?php echo get_array_value($task_title_map, $cost->task_id, ""); ?></td>
                    <td><?php echo app_lang("cost_" . $cost->cost_type); ?></td>
                    <td><?php echo to_currency($cost->planned_value); ?></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="3"><?php echo app_lang("no_data"); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <h4><?php echo app_lang("realized_costs"); ?></h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th><?php echo app_lang("date"); ?></th>
            <th><?php echo app_lang("tasks"); ?></th>
            <th><?php echo app_lang("cost_type"); ?></th>
            <th><?php echo app_lang("value"); ?></th>
            <th><?php echo app_lang("description"); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($realized_items)) { ?>
            <?php foreach ($realized_items as $item) { ?>
                <tr>
                    <td><?php echo format_to_date($item->date, false); ?></td>
                    <td><?php echo get_array_value($task_title_map, $item->task_id, "-"); ?></td>
                    <td><?php echo app_lang("cost_" . $item->cost_type); ?></td>
                    <td><?php echo to_currency($item->value); ?></td>
                    <td><?php echo $item->description; ?></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5"><?php echo app_lang("no_data"); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
