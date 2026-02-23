<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_proposal_assistant'); ?></h1>
            </div>
            <div class="card p20">
                <div class="row">
                    <div class="col-md-4">
                        <label><?php echo app_lang('client'); ?></label>
                        <select id="fv-assist-client" class="form-control">
                            <option value=""><?php echo app_lang('choose'); ?></option>
                            <?php foreach ($clients as $client) { ?>
                                <option value="<?php echo $client->id; ?>"><?php echo esc($client->company_name); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label><?php echo app_lang('fv_consumption_history'); ?> (kWh/mês)</label>
                        <input type="text" id="fv-assist-consumption" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label><?php echo app_lang('fv_zip'); ?></label>
                        <input type="text" id="fv-assist-cep" class="form-control" placeholder="00000-000" />
                    </div>
                </div>

                <hr/>

                <div class="row">
                    <div class="col-md-6">
                        <label><?php echo app_lang('fv_select_kit'); ?></label>
                        <select id="fv-assist-kit" class="form-control">
                            <option value=""><?php echo app_lang('choose'); ?></option>
                            <?php foreach ($kits as $kit) { ?>
                                <option value="<?php echo $kit->id; ?>"><?php echo esc($kit->name); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label><?php echo app_lang('fv_regulatory_profile'); ?></label>
                        <select id="fv-assist-profile" class="form-control">
                            <option value=""><?php echo app_lang('choose'); ?></option>
                            <?php foreach ($profiles as $profile) { ?>
                                <option value="<?php echo $profile->id; ?>" data-rules='<?php echo esc($profile->rules_json); ?>'><?php echo esc($profile->name); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <hr/>

                <div class="row">
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_latitude'); ?></label>
                        <input type="text" id="fv-assist-lat" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_longitude'); ?></label>
                        <input type="text" id="fv-assist-lon" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_provider'); ?></label>
                        <select id="fv-assist-provider" class="form-control">
                            <option value="pvgis">PVGIS</option>
                            <option value="nasa">NASA POWER</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_installed_power'); ?></label>
                        <input type="text" id="fv-assist-power" class="form-control" placeholder="kWp" />
                    </div>
                </div>

                <div class="row mtop10">
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_tariff_total_value'); ?></label>
                        <input type="text" id="fv-assist-tariff" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_tariff_growth'); ?></label>
                        <input type="text" id="fv-assist-tariff-growth" class="form-control" value="0" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_investment'); ?></label>
                        <input type="text" id="fv-assist-investment" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_losses_percent'); ?></label>
                        <input type="text" id="fv-assist-losses" class="form-control" value="14" />
                    </div>
                </div>

                <div class="mtop10">
                    <label><?php echo app_lang('fv_irradiation_monthly'); ?></label>
                    <textarea id="fv-assist-irradiation" class="form-control" rows="2" placeholder="Ex.: 150,150,150,... (12 valores)"></textarea>
                    <small class="text-muted"><?php echo app_lang('fv_assist_irradiation_hint'); ?></small>
                </div>

                <hr/>

                <h5><?php echo app_lang('fv_checklist'); ?></h5>
                <ul id="fv-assist-checklist" style="font-size:13px;">
                    <li data-check="client"><?php echo app_lang('fv_missing_client'); ?></li>
                    <li data-check="kit"><?php echo app_lang('fv_missing_kit'); ?></li>
                    <li data-check="coords"><?php echo app_lang('fv_missing_coordinates'); ?></li>
                    <li data-check="tariff"><?php echo app_lang('fv_missing_tariff'); ?></li>
                </ul>

                <button class="btn btn-primary" id="fv-assist-generate"><?php echo app_lang('fv_assist_generate'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        function updateChecklist() {
            var hasClient = !!$("#fv-assist-client").val();
            var hasKit = !!$("#fv-assist-kit").val();
            var hasCoords = !!$("#fv-assist-lat").val() && !!$("#fv-assist-lon").val();
            var hasTariff = !!$("#fv-assist-tariff").val();

            $("#fv-assist-checklist li[data-check='client']").toggleClass("text-success", hasClient);
            $("#fv-assist-checklist li[data-check='kit']").toggleClass("text-success", hasKit);
            $("#fv-assist-checklist li[data-check='coords']").toggleClass("text-success", hasCoords);
            $("#fv-assist-checklist li[data-check='tariff']").toggleClass("text-success", hasTariff);
        }

        $("#fv-assist-client, #fv-assist-kit, #fv-assist-lat, #fv-assist-lon, #fv-assist-tariff").on("change input", updateChecklist);
        updateChecklist();

        $("#fv-assist-generate").on("click", function () {
            var monthly = $("#fv-assist-irradiation").val().split(",").map(function (v) {
                return parseFloat(v.replace(",", ".").trim()) || 0;
            }).filter(function (v) { return v !== null; });

            var tariffSnapshot = {
                tariff_mode: "total",
                tariff_value: $("#fv-assist-tariff").val(),
                tariff_growth_percent_year: $("#fv-assist-tariff-growth").val()
            };

            $.post("<?php echo get_uri('fotovoltaico/assistant_generate'); ?>", {
                client_id: $("#fv-assist-client").val(),
                kit_id: $("#fv-assist-kit").val(),
                profile_id: $("#fv-assist-profile").val(),
                consumption_kwh_month: $("#fv-assist-consumption").val(),
                cep: $("#fv-assist-cep").val(),
                lat: $("#fv-assist-lat").val(),
                lon: $("#fv-assist-lon").val(),
                irradiation_provider: $("#fv-assist-provider").val(),
                irradiation_monthly: monthly.length === 12 ? JSON.stringify(monthly) : "",
                system_power_kwp: $("#fv-assist-power").val(),
                losses_percent: $("#fv-assist-losses").val(),
                tariff_snapshot: JSON.stringify(tariffSnapshot),
                tariff_growth_percent_year: $("#fv-assist-tariff-growth").val(),
                investment_value: $("#fv-assist-investment").val()
            }, function (result) {
                if (result && result.success) {
                    appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>");
                    window.location.href = "<?php echo get_uri('fotovoltaico/projects_view'); ?>/" + result.project_id;
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });
    });
</script>
