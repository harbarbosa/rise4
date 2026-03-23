<?php foreach (($comments ?? []) as $comment) {
    echo view('OrdemServico\\Views\\comments\\comment_row', ['comment' => $comment, 'login_user' => $login_user]);
} ?>

