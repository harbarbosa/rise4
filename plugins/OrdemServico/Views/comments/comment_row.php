

<div id="os-comment-<?php echo $comment->id; ?>" class="comment-highlight-section">
  <div class="comment-container text-break b-b">
    <div class="d-flex">
      <div class="flex-shrink-0 comment-avatar">
        <span class="avatar avatar-sm">
          <img src="<?php echo get_avatar($comment->created_by_avatar); ?>" alt="..." />
        </span>
      </div>
      <div class="w-100 ps-2">
        <div class="mb5">
          <span class="dark strong"><?php echo esc($comment->created_by_user); ?></span>
          <small><span class="text-off"><?php echo format_to_relative_time($comment->created_at ?: $comment->updated_at); ?></span></small>
          <?php if ($login_user->is_admin || $comment->created_by == $login_user->id) { ?>
            <span class="float-end dropdown comment-dropdown">
              <div class="text-off dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                <i data-feather="chevron-down" class="icon-16 clickable"></i>
              </div>
              <ul class="dropdown-menu dropdown-menu-end" role="menu">
                <li role="presentation"><?php echo js_anchor("<i data-feather='x' class='icon-16'></i> " . app_lang('delete'), ["class" => "dropdown-item", "title" => app_lang('delete'), "data-id" => $comment->id, "data-action-url" => get_uri("ordemservico/comment_delete"), "data-action" => "delete-confirmation", "data-fade-out-on-success" => "#os-comment-".$comment->id, "data-success-callback" => "loadOsComments"]); ?></li>
              </ul>
            </span>
          <?php } ?>
        </div>
        <div class="mb5">
          <?php echo nl2br(link_it(convert_mentions(esc($comment->comment)))); ?>
        </div>
      </div>
    </div>
  </div>
</div>
