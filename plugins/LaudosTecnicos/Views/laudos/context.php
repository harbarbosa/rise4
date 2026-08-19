<div class="page-title clearfix">
    <div class="title-button-group">
        <?php if (!empty($can_create_laudos)) { ?>
            <?php
            $attrs = array('class' => 'btn btn-primary');
            if (($context_type ?? '') === 'client' && !empty($context_id)) {
                $attrs['data-post-client_id'] = $context_id;
            }
            if (($context_type ?? '') === 'project' && !empty($context_id)) {
                $attrs['data-post-project_id'] = $context_id;
            }
            echo modal_anchor(get_uri('laudostecnicos/laudos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo laudo", $attrs);
            ?>
        <?php } ?>
    </div>
</div>
<?php echo view('LaudosTecnicos\\Views\\laudos\\table', get_defined_vars()); ?>
