<?php
$projects = isset($projects) && is_array($projects) ? $projects : array();
?>

<div class="page-title clearfix">
    <h1><?php echo app_lang('project_materials'); ?></h1>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead>
                <tr>
                    <th><?php echo app_lang('project'); ?></th>
                    <th><?php echo app_lang('client'); ?></th>
                    <th><?php echo app_lang('status'); ?></th>
                    <th class="text-end"><?php echo app_lang('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$projects) { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted p20"><?php echo app_lang('no_record_found'); ?></td>
                    </tr>
                <?php } ?>
                <?php foreach ($projects as $project) { ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_uri('projectanalizer/project_materials/' . (int)$project->id); ?>">
                                <?php echo esc($project->title); ?>
                            </a>
                        </td>
                        <td><?php echo esc($project->company_name ?? '-'); ?></td>
                        <td><?php echo esc($project->status_title ?? '-'); ?></td>
                        <td class="text-end">
                            <a class="btn btn-default btn-sm" href="<?php echo get_uri('projectanalizer/project_materials/' . (int)$project->id); ?>">
                                <i data-feather="package" class="icon-16"></i> <?php echo app_lang('project_materials'); ?>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    feather.replace();
});
</script>
