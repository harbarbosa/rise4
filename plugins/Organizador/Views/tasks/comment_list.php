<div id="organizador-comments-list">
    <?php foreach ($comments as $comment) { ?>
        <?php echo view('Organizador\\Views\\tasks\\comment_row', array('comment' => $comment, 'can_manage' => $can_edit || $login_user->is_admin || (int) $comment->created_by === (int) $login_user->id)); ?>
    <?php } ?>
</div>
