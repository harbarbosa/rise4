<?php
$project = $project ?? null;
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('fotovoltaico/projects_save'), array('id' => 'fv-project-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="id" value="<?php echo $project->id ?? ''; ?>" />

        <div class="form-group">
            <label for="client_id"><?php echo app_lang('client'); ?></label>
            <?php echo form_dropdown('client_id', $clients, $project->client_id ?? '', "class='select2 validate-hidden' id='client_id'"); ?>
        </div>

        <div class="form-group">
            <label for="title"><?php echo app_lang('title'); ?></label>
            <?php echo form_input(array('id' => 'title', 'name' => 'title', 'value' => $project->title ?? '', 'class' => 'form-control', 'placeholder' => app_lang('title'), 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required'))); ?>
        </div>

        <div class="form-group">
            <label for="status"><?php echo app_lang('status'); ?></label>
            <?php echo form_dropdown('status', array('draft' => 'draft', 'sent' => 'sent', 'won' => 'won', 'lost' => 'lost'), $project->status ?? 'draft', "class='select2' id='status'"); ?>
        </div>

        <div class="form-group">
            <label for="city"><?php echo app_lang('city'); ?></label>
            <?php echo form_input(array('id' => 'city', 'name' => 'city', 'value' => $project->city ?? '', 'class' => 'form-control', 'placeholder' => app_lang('city'))); ?>
        </div>

        <div class="form-group">
            <label for="state"><?php echo app_lang('state'); ?></label>
            <?php echo form_input(array('id' => 'state', 'name' => 'state', 'value' => $project->state ?? '', 'class' => 'form-control', 'placeholder' => app_lang('state'))); ?>
        </div>

        <div class="form-group">
            <label for="lat">Latitude</label>
            <?php echo form_input(array('id' => 'lat', 'name' => 'lat', 'value' => $project->lat ?? '', 'class' => 'form-control', 'placeholder' => 'Latitude')); ?>
        </div>

        <div class="form-group">
            <label for="lon">Longitude</label>
            <?php echo form_input(array('id' => 'lon', 'name' => 'lon', 'value' => $project->lon ?? '', 'class' => 'form-control', 'placeholder' => 'Longitude')); ?>
        </div>

    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="button" class="btn btn-primary" id="fv-project-save"><?php echo app_lang('save'); ?></button>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();
        $('#fv-project-form').appForm({
            onSuccess: function (result) {
                $('#fv-projects-table').appTable({newData: result.data, dataId: result.id});
            }
        });

        $('#fv-project-save').on('click', function () {
            $('#fv-project-form').trigger('submit');
        });
    });
</script>
