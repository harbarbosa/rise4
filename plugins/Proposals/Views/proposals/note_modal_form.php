<?php echo form_open(get_uri('propostas/save_note'), array('id' => 'note-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div id="notes-dropzone" class="post-dropzone">
    <div class="modal-body clearfix"><div class="container-fluid">
        <input type="hidden" name="note_id" value="<?php echo (int) $note_id; ?>" />
        <input type="hidden" name="proposal_id" value="<?php echo (int) $proposal_id; ?>" />
        <input type="hidden" name="project_id" value="0" /><input type="hidden" name="client_id" value="0" />
        <input type="hidden" name="user_id" value="0" /><input type="hidden" id="is_grid" name="is_grid" value="" />
        <div class="form-group"><div class="col-md-12">
            <?php echo form_input(array('id' => 'title', 'name' => 'title', 'value' => $note_info->title, 'class' => 'form-control notepad-title', 'placeholder' => app_lang('title'), 'autofocus' => true, 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required'))); ?>
        </div></div>
        <div class="form-group"><div class="col-md-12"><div class="notepad">
            <?php echo form_textarea(array('id' => 'description', 'name' => 'description', 'value' => process_images_from_content($note_info->description, false), 'class' => 'form-control', 'placeholder' => app_lang('description') . '...', 'data-rich-text-editor' => true, 'data-toolbar' => 'pdf_friendly_toolbar')); ?>
        </div></div></div>
        <div class="form-group"><div class="row"><div class="col-md-12">
            <?php echo form_dropdown('category_id', $note_categories_dropdown, array($note_info->category_id), "class='select2' id='category_id'"); ?>
        </div></div></div>
        <div class="form-group"><div class="col-md-12">
            <?php echo form_input(array('id' => 'note_labels', 'name' => 'labels', 'value' => $note_info->labels, 'class' => 'form-control', 'placeholder' => app_lang('labels'))); ?>
        </div></div>
        <div class="form-group"><label for="mark_as_public" class="col-md-12">
            <?php echo form_checkbox('is_public', '1', (bool) $note_info->is_public, "id='mark_as_public' class='float-start form-check-input'"); ?>
            <span class="float-start ml15"> <?php echo app_lang('mark_as_public'); ?> </span>
        </label></div>
        <div class="form-group"><div class="col-md-12 ms-auto"><?php echo view('includes/color_plate'); ?></div></div>
        <div class="form-group"><div class="col-md-12 row"><?php echo view('includes/file_list', array('files' => $note_info->files)); ?></div></div>
        <?php echo view('includes/dropzone_preview'); ?>
    </div></div>
    <div class="modal-footer">
        <?php echo view('includes/upload_button', array('show_link_copy_button' => true)); ?>
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
        <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
$(document).ready(function() {
    $('#note-form').appForm({onSuccess: function() { $('#app-modal').modal('hide'); $('#proposal-note-table').appTable({reload: true}); }});
    setTimeout(function() { $('#title').focus(); }, 200);
    $('#note_labels').select2({multiple: true, data: <?php echo json_encode($label_suggestions); ?>});
    $('#note-form .select2').select2();
});
</script>
