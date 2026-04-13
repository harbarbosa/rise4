<?php
$model_info = $model_info ?? (object) array('id' => 0, 'title' => '', 'color' => '#4A8AF4', 'sort' => 0);
?>

<?php echo form_open(get_uri('organizador/tags/save'), array('id' => 'organizador-tag-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('organizador_tag_title'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => $model_info->title,
                        "class" => "form-control",
                        "placeholder" => app_lang('organizador_tag_title'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('organizador_tag_color'); ?></label>
                <div class="col-md-9">
                    <?php echo view("includes/color_plate", array("model_info" => $model_info)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sort" class="col-md-3"><?php echo app_lang('organizador_tag_sort'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "sort",
                        "name" => "sort",
                        "type" => "number",
                        "value" => $model_info->sort,
                        "class" => "form-control",
                        "placeholder" => app_lang('organizador_tag_sort'),
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#organizador-tag-form").appForm({
            onSuccess: function (result) {
                $("#organizador-tags-table").appTable({newData: result.data, dataId: result.id});
            }
        });

        setTimeout(function () {
            $("#title").focus();
        }, 200);
    });
</script>
