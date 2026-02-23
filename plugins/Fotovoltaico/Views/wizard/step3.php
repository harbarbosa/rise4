<div class="page-content clearfix">
    <div class="card p20">
        <h3>Wizard FV - <?php echo app_lang('fv_location'); ?></h3>
        <p><?php echo app_lang('fv_project'); ?>: <?php echo esc($project->title); ?></p>

        <div class="form-group">
            <label><?php echo app_lang('address'); ?></label>
            <input type="text" class="form-control" id="fv-address" placeholder="EndereÃ§o completo" />
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo app_lang('fv_latitude'); ?></label>
                    <input type="text" class="form-control" id="fv-lat" placeholder="-23.000000" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo app_lang('fv_longitude'); ?></label>
                    <input type="text" class="form-control" id="fv-lon" placeholder="-46.000000" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <label><?php echo app_lang('fv_provider'); ?></label>
            <select id="fv-provider" class="form-control">
                <option value="pvgis">PVGIS</option>
                <option value="nasa">NASA POWER</option>
            </select>
        </div>

        <button class="btn btn-default" id="fv-fetch-irradiation"><?php echo app_lang('fv_fetch_irradiation'); ?></button>

        <div class="mt15">
            <label><?php echo app_lang('fv_irradiation_monthly'); ?></label>
            <textarea id="fv-irradiation-monthly" class="form-control" rows="2" placeholder="Ex.: 150,150,150,... (12 valores)"></textarea>
        </div>

        <div class="mt10">
            <label><?php echo app_lang('fv_irradiation_annual'); ?></label>
            <div class="form-control-plaintext" id="fv-irradiation-annual">-</div>
        </div>

        <button class="btn btn-primary mtop10" id="fv-save-irradiation"><?php echo app_lang('fv_save_irradiation'); ?></button>

        <div class="mt15">
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/2'), app_lang('previous'), array('class' => 'btn btn-default')); ?>
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/4'), app_lang('next'), array('class' => 'btn btn-primary')); ?>
        </div>
    </div>
</div>

<script>
    $(function () {
        function parseMonthly(text) {
            var values = text.split(",").map(function (v) {
                return parseFloat(v.replace(",", ".").trim()) || 0;
            });
            return values.filter(function (v) { return v !== null; });
        }

        $("#fv-fetch-irradiation").on("click", function () {
            var lat = $("#fv-lat").val();
            var lon = $("#fv-lon").val();
            var provider = $("#fv-provider").val();
            $.post("<?php echo get_uri('fotovoltaico/irradiation/fetch'); ?>", {
                lat: lat,
                lon: lon,
                provider: provider
            }, function (result) {
                if (result && result.success) {
                    var monthly = result.data.monthly || [];
                    $("#fv-irradiation-monthly").val(monthly.join(","));
                    $("#fv-irradiation-annual").text((result.data.annual || 0).toFixed(2));
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        $("#fv-save-irradiation").on("click", function () {
            var monthly = parseMonthly($("#fv-irradiation-monthly").val());
            if (monthly.length !== 12) {
                appAlert.error("<?php echo app_lang('fv_irradiation_12'); ?>");
                return;
            }
            var annual = monthly.reduce(function (sum, v) { return sum + v; }, 0);
            $("#fv-irradiation-annual").text(annual.toFixed(2));

            $.post("<?php echo get_uri('fotovoltaico/projects/' . $project->id . '/irradiation_snapshot_save'); ?>", {
                provider: $("#fv-provider").val(),
                lat: $("#fv-lat").val(),
                lon: $("#fv-lon").val(),
                monthly_json: JSON.stringify(monthly),
                annual_value: annual
            }, function (result) {
                if (result && result.success) {
                    appAlert.success(result.message);
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });
    });
</script>
