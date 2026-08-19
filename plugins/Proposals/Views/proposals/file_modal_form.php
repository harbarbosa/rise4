<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?php echo app_lang('add_files'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <?php echo form_open_multipart('propostas/save_file', array('id' => 'proposal-file-form', 'class' => 'dialog-form')); ?>
        <div class="modal-body">
            <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">
            <div class="form-group">
                <label for="file"><?php echo app_lang('file'); ?></label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
            <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#proposal-file-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            var formData = new FormData(this);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#app-modal').modal('hide');
                        $("#proposal-file-table").appTable({reload: true});
                        appAlert.success(response.message);
                    } else {
                        appAlert.error(response.message);
                    }
                }
            });
        });
    });
</script>