<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_insolation'); ?></h5>
    <p class="text-off mb0">Informe a base de insolacao usada para o dimensionamento.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label for="insolation_city" class="form-label">Cidade</label>
        <?php echo form_input(array('id' => 'insolation_city', 'name' => 'insolation_city', 'value' => get_array_value($wizard_data, 'insolation_city'), 'class' => 'form-control')); ?>
    </div>
    <div class="col-md-3">
        <label for="insolation_state" class="form-label"><?php echo app_lang('fotovoltaico_state_code'); ?></label>
        <?php echo form_input(array('id' => 'insolation_state', 'name' => 'insolation_state', 'value' => get_array_value($wizard_data, 'insolation_state'), 'class' => 'form-control')); ?>
    </div>
    <div class="col-md-3">
        <label for="insolation_source" class="form-label">Fonte</label>
        <?php echo form_input(array('id' => 'insolation_source', 'name' => 'insolation_source', 'value' => get_array_value($wizard_data, 'insolation_source'), 'class' => 'form-control')); ?>
    </div>
    <div class="col-md-4">
        <label for="latitude" class="form-label">Latitude</label>
        <?php echo form_input(array('id' => 'latitude', 'name' => 'latitude', 'value' => get_array_value($wizard_data, 'latitude'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="longitude" class="form-label">Longitude</label>
        <?php echo form_input(array('id' => 'longitude', 'name' => 'longitude', 'value' => get_array_value($wizard_data, 'longitude'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="annual_insolation" class="form-label">Insolacao anual</label>
        <?php echo form_input(array('id' => 'annual_insolation', 'name' => 'annual_insolation', 'value' => get_array_value($wizard_data, 'annual_insolation'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-12">
        <button type="button" class="btn btn-outline-secondary" id="fetch-insolation">
            <i data-feather="cloud-rain" class="icon-16"></i> Consultar insolacao automaticamente
        </button>
        <span class="small text-off ms10">O valor retornado pode ser ajustado manualmente antes de seguir.</span>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt20">
    <div>
        <a href="<?php echo get_uri('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/' . $previous_step); ?>" class="btn btn-default">
            <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_previous'); ?>
        </a>
    </div>
    <button type="submit" class="btn btn-primary">
        <i data-feather="arrow-right" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_save_continue'); ?>
    </button>
</div>

<script type="text/javascript">
    $(function () {
        $("#fetch-insolation").on("click", function () {
            var latitude = $("#latitude").val();
            var longitude = $("#longitude").val();
            if (!latitude || !longitude) {
                appAlert.error("Informe latitude e longitude antes de consultar.");
                return;
            }

            $(this).prop("disabled", true);

            $.post("<?php echo get_uri('fotovoltaico/insolation/get_data'); ?>", {
                latitude: latitude,
                longitude: longitude
            }).done(function (response) {
                if (typeof response === "string") {
                    response = JSON.parse(response);
                }

                if (response && response.success) {
                    if (response.adjusted_annual_insolation !== undefined) {
                        $("#annual_insolation").val(response.adjusted_annual_insolation);
                    } else if (response.annual_insolation !== undefined) {
                        $("#annual_insolation").val(response.annual_insolation);
                    }
                } else {
                    appAlert.error((response && response.message) ? response.message : "Nao foi possivel consultar a insolacao.");
                }
            }).fail(function () {
                appAlert.error("Nao foi possivel consultar a insolacao.");
            }).always(function () {
                $("#fetch-insolation").prop("disabled", false);
            });
        });
    });
</script>
