<?php echo form_open_multipart(get_uri("propostas/save_file"), array("id" => "file-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="category_id" class="col-md-3"><?php echo app_lang('category'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("category_id", $file_categories_dropdown ?? array(), array(), "class='select2' id='category_id'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="file" class="col-md-3"><?php echo app_lang('file'); ?></label>
                <div class="col-md-9">
                    <input type="file" name="file" id="file" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => app_lang('description'),
                        "rows" => 3
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary" id="file-save-button"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#file-form").appForm({
            onSuccess: function(result) {
                $("#proposal-file-table").appTable({
                    reload: true
                });
            }
        });

        $("#file-form .select2").select2();
    });
</script>