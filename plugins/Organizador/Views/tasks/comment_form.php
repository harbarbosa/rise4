<div id="organizador-comment-form-container">
    <?php echo form_open(get_uri('organizador/tasks/save_comment'), array('id' => 'organizador-comment-form', 'class' => 'general-form', 'role' => 'form')); ?>
    <div class="d-flex b-b comment-form-container">
        <div class="flex-shrink-0 d-none d-sm-block">
            <div class="avatar avatar-sm pr15 d-table-cell">
                <img src="<?php echo get_avatar($login_user->image); ?>" alt="..." />
            </div>
        </div>
        <div class="w-100">
            <div id="organizador-comment-dropzone" class="post-dropzone mb-3 form-group">
                <input type="hidden" name="task_id" value="<?php echo (int) $task->id; ?>">
                <textarea id="organizador-comment-description" name="description" class="form-control comment_description" placeholder="<?php echo app_lang('write_a_comment'); ?>" style="min-height: 120px;"></textarea>
                <?php echo view('includes/dropzone_preview'); ?>
                <footer class="card-footer b-a clearfix">
                    <div class="float-start">
                        <?php
                        $upload_button_text = app_lang('upload_file');
                        $hide_recording = true;
                        echo view('includes/upload_button');
                        ?>
                    </div>
                    <button class="btn btn-primary float-end" type="submit"><i data-feather="send" class="icon-16"></i> <?php echo app_lang('post_comment'); ?></button>
                </footer>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#organizador-comment-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                $("#organizador-comment-description").val("");
                $("#organizador-comments-list").prepend(result.data);

                if (window.formDropzone && window.formDropzone["organizador-comment-dropzone"]) {
                    window.formDropzone["organizador-comment-dropzone"].removeAllFiles();
                }

                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
