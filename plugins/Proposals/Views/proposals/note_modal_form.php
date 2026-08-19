<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?php echo $note_id ? app_lang('edit_note') : app_lang('add_note'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <?php echo form_open('propostas/save_note', array('id' => 'proposal-note-form', 'class' => 'dialog-form')); ?>
        <div class="modal-body">
            <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">
            <input type="hidden" name="note_id" value="<?php echo $note_id; ?>">
            <div class="form-group">
                <label for="title"><?php echo app_lang('title'); ?></label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo isset($note_info->title) ? esc($note_info->title) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="content"><?php echo app_lang('description'); ?></label>
                <textarea name="content" id="content" class="form-control" rows="6"><?php echo isset($note_info->content) ? esc($note_info->content) : ''; ?></textarea>
            </div>
            <div class="form-check">
                <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" <?php echo isset($note_info->is_public) && $note_info->is_public ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_public"><?php echo app_lang('public'); ?></label>
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
        $('#proposal-note-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#app-modal').modal('hide');
                        $("#proposal-note-table").appTable({reload: true});
                        appAlert.success(response.message);
                    } else {
                        appAlert.error(response.message);
                    }
                }
            });
        });
    });
</script>