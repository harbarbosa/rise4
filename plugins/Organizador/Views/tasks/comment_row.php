<?php
$comment = $comment ?? (object) array();
$can_manage = !empty($can_manage);
$files = array();
if (!empty($comment->files)) {
    $files = @unserialize($comment->files);
    if (!is_array($files)) {
        $files = array();
    }
}
?>
<div id="organizador-comment-<?php echo (int) $comment->id; ?>" class="comment-container text-break b-b pb15 mb15">
    <div class="d-flex">
        <div class="flex-shrink-0 comment-avatar">
            <span class="avatar avatar-sm">
                <img src="<?php echo get_avatar($comment->created_by_avatar ?? ''); ?>" alt="..." />
            </span>
        </div>
        <div class="w-100 ps-2">
            <div class="mb5">
                <?php
                if (($comment->user_type ?? 'staff') === 'staff') {
                    echo get_team_member_profile_link($comment->created_by, $comment->created_by_user ?: '-');
                } else {
                    echo esc($comment->created_by_user ?: '-');
                }
                ?>
                <small><span class="text-off"><?php echo format_to_relative_time($comment->created_at); ?></span></small>

                <?php if ($can_manage) { ?>
                    <span class="float-end">
                        <?php echo ajax_anchor(get_uri('organizador/tasks/delete_comment'), "<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'text-danger', 'title' => app_lang('delete'), 'data-post-id' => $comment->id, 'data-fade-out-on-success' => '#organizador-comment-' . $comment->id)); ?>
                    </span>
                <?php } ?>
            </div>

            <?php if (!empty($comment->description)) { ?>
                <div class="mb10"><?php echo nl2br(link_it(esc($comment->description))); ?></div>
            <?php } ?>

            <?php if ($files) { ?>
                <div class="comment-image-box clearfix">
                    <?php echo view('includes/timeline_preview', array('files' => $files)); ?>
                    <div class="mt10">
                        <?php echo anchor(get_uri('organizador/tasks/download_comment_files/' . $comment->id), sprintf(app_lang('download_files'), count($files)), array('class' => 'float-end')); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
