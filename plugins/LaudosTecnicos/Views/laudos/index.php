<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h1>Laudos</h1>
        <div class="title-button-group">
            <?php if (!empty($can_create_laudos)) { ?>
                <?php echo modal_anchor(get_uri('laudostecnicos/laudos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo laudo", array('class' => 'btn btn-primary')); ?>
            <?php } ?>
        </div>
    </div>
    <?php echo view('LaudosTecnicos\\Views\\laudos\\table', get_defined_vars()); ?>
</div>
