<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_project'); ?>: <?php echo esc($project->title); ?></h1>
                <div class="title-button-group">
                    <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/1'), app_lang('fv_wizard'), array('class' => 'btn btn-default')); ?>
                </div>
            </div>

            <div class="card p20">
                <p><strong><?php echo app_lang('client'); ?>:</strong> <?php echo esc($project->client_id); ?></p>
                <p><strong><?php echo app_lang('status'); ?>:</strong> <?php echo esc($project->status); ?></p>
                <p><strong><?php echo app_lang('city'); ?>:</strong> <?php echo esc($project->city ?? '-'); ?></p>
                <p><strong><?php echo app_lang('state'); ?>:</strong> <?php echo esc($project->state ?? '-'); ?></p>
            </div>

            <div class="card p20 mtop20">
                <h4><?php echo app_lang('fv_results'); ?></h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="well">
                            <div class="text-muted"><?php echo app_lang('fv_installed_power'); ?></div>
                            <div class="h3" id="fv-result-power">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well">
                            <div class="text-muted"><?php echo app_lang('fv_annual_generation'); ?></div>
                            <div class="h3" id="fv-result-generation"><?php echo number_format((float)$annual_generation, 2, ',', '.'); ?> kWh</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well">
                            <div class="text-muted"><?php echo app_lang('fv_annual_savings'); ?></div>
                            <div class="h3" id="fv-result-savings"><?php echo $financial ? to_currency($financial->annual_savings_year1) : '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well">
                            <div class="text-muted"><?php echo app_lang('fv_payback'); ?></div>
                            <div class="h3" id="fv-result-payback"><?php echo $financial ? ($financial->payback_years . 'a ' . $financial->payback_months . 'm') : '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well">
                            <div class="text-muted"><?php echo app_lang('fv_irr'); ?></div>
                            <div class="h3" id="fv-result-irr"><?php echo $financial ? number_format((float)$financial->irr_percent, 2, ',', '.') . '%' : '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well">
                            <div class="text-muted"><?php echo app_lang('fv_npv'); ?></div>
                            <div class="h3" id="fv-result-npv"><?php echo $financial ? to_currency($financial->npv_value) : '-'; ?></div>
                        </div>
                    </div>
                </div>

                <hr />
                <h5><?php echo app_lang('fv_calculation_inputs'); ?></h5>
                <div class="row">
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_installed_power'); ?></label>
                        <input type="text" id="fv-input-power" class="form-control" value="" placeholder="kWp" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_losses_percent'); ?></label>
                        <input type="text" id="fv-input-losses" class="form-control" value="14" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_tariff_date'); ?></label>
                        <input type="date" id="fv-input-tariff-date" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_investment'); ?></label>
                        <input type="text" id="fv-input-investment" class="form-control" value="25000" />
                    </div>
                </div>
                <div class="row mtop10">
                    <div class="col-md-4">
                        <label><?php echo app_lang('fv_tariff_select_utility'); ?></label>
                        <select id="fv-input-utility" class="form-control">
                            <option value=""><?php echo app_lang('choose'); ?></option>
                            <?php if (!empty($utilities)) { ?>
                                <?php foreach ($utilities as $utility) { ?>
                                    <option value="<?php echo $utility->id; ?>"><?php echo esc($utility->name); ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label><?php echo app_lang('fv_select_tariff'); ?></label>
                        <select id="fv-input-tariff-id" class="form-control">
                            <option value=""><?php echo app_lang('choose'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3 mtop10">
                        <label class="d-block">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="fv-input-tariff-manual" />
                            <label class="form-check-label" for="fv-input-tariff-manual"><?php echo app_lang('fv_tariff_manual'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="row mtop10">
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_tariff_mode'); ?></label>
                        <select id="fv-input-tariff-mode" class="form-control">
                            <option value="components"><?php echo app_lang('fv_tariff_components'); ?></option>
                            <option value="total"><?php echo app_lang('fv_tariff_total'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3 fv-tariff-components">
                        <label><?php echo app_lang('fv_tariff_te'); ?></label>
                        <input type="text" id="fv-input-te" class="form-control" value="0" />
                    </div>
                    <div class="col-md-3 fv-tariff-components">
                        <label><?php echo app_lang('fv_tariff_tusd'); ?></label>
                        <input type="text" id="fv-input-tusd" class="form-control" value="0" />
                    </div>
                    <div class="col-md-3 fv-tariff-components">
                        <label><?php echo app_lang('fv_tariff_flags'); ?></label>
                        <input type="text" id="fv-input-flags" class="form-control" value="0" />
                    </div>
                    <div class="col-md-3 fv-tariff-total d-none">
                        <label><?php echo app_lang('fv_tariff_total_value'); ?></label>
                        <input type="text" id="fv-input-tariff-total" class="form-control" value="0.90" />
                    </div>
                </div>
                <div class="row mtop10">
                    <div class="col-md-4">
                        <label><?php echo app_lang('fv_tariff_total'); ?></label>
                        <div class="form-control-plaintext" id="fv-tariff-total-display">-</div>
                    </div>
                    <div class="col-md-4">
                        <label><?php echo app_lang('fv_tariff_breakdown'); ?></label>
                        <div class="form-control-plaintext" id="fv-tariff-breakdown-display">-</div>
                    </div>
                    <div class="col-md-4">
                        <label class="d-block">&nbsp;</label>
                        <button class="btn btn-default" id="fv-save-tariff"><?php echo app_lang('fv_save_tariff_snapshot'); ?></button>
                    </div>
                </div>
                <div class="row mtop10">
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_tariff_growth'); ?></label>
                        <input type="text" id="fv-input-tariff-growth" class="form-control" value="0" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_degradation'); ?></label>
                        <input type="text" id="fv-input-degradation" class="form-control" value="0.5" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_opex'); ?></label>
                        <input type="text" id="fv-input-opex" class="form-control" value="0" />
                    </div>
                    <div class="col-md-3">
                        <label><?php echo app_lang('fv_discount_rate'); ?></label>
                        <input type="text" id="fv-input-discount" class="form-control" value="8" />
                    </div>
                </div>
                <div class="mtop10">
                    <label><?php echo app_lang('fv_irradiation_monthly'); ?></label>
                    <textarea id="fv-input-irradiation" class="form-control" rows="2" placeholder="Ex.: 150,150,150,... (12 valores)"></textarea>
                </div>
                <div class="mtop10">
                    <button class="btn btn-primary" id="fv-recalc-btn"><?php echo app_lang('fv_recalculate'); ?></button>
                </div>
            </div>

            <div class="card p20 mtop20">
                <h4><?php echo app_lang('fv_regulatory_profile'); ?></h4>
                <div class="form-group">
                    <label><?php echo app_lang('fv_select_profile'); ?></label>
                    <select id="fv-reg-profile" class="form-control">
                        <option value=""><?php echo app_lang('choose'); ?></option>
                        <?php if (!empty($reg_profiles)) { ?>
                            <?php foreach ($reg_profiles as $profile) { ?>
                                <option value="<?php echo $profile->id; ?>" data-rules='<?php echo esc($profile->rules_json); ?>'><?php echo esc($profile->name); ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo app_lang('fv_snapshot_json'); ?></label>
                    <textarea id="fv-reg-snapshot" class="form-control" rows="6" placeholder='{"compensation_mode":"fio_b_partial","fio_b_percent_tariff":30}'></textarea>
                </div>
                <button class="btn btn-default" id="fv-save-snapshot"><?php echo app_lang('fv_save_snapshot'); ?></button>
            </div>

            <div class="card p20 mtop20">
                <h4><?php echo app_lang('fv_proposal_pdf'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <label><?php echo app_lang('fv_select_kit'); ?></label>
                        <select id="fv-proposal-kit" class="form-control">
                            <option value=""><?php echo app_lang('choose'); ?></option>
                            <?php if (!empty($kits)) { ?>
                                <?php foreach ($kits as $kit) { ?>
                                    <option value="<?php echo $kit->id; ?>"><?php echo esc($kit->name); ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6 mtop10">
                        <label class="d-block">&nbsp;</label>
                        <button class="btn btn-primary" id="fv-generate-pdf"><?php echo app_lang('fv_generate_pdf'); ?></button>
                    </div>
                </div>

                <?php if (!empty($proposals)) { ?>
                    <div class="table-responsive mtop15">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo app_lang('id'); ?></th>
                                    <th><?php echo app_lang('created_at'); ?></th>
                                    <th><?php echo app_lang('fv_total_value'); ?></th>
                                    <th><?php echo app_lang('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($proposals as $proposal) { ?>
                                <tr>
                                    <td><?php echo $proposal->id; ?></td>
                                    <td><?php echo format_to_datetime($proposal->created_at); ?></td>
                                    <td><?php echo $proposal->total_value !== null ? to_currency($proposal->total_value) : '-'; ?></td>
                                    <td>
                                        <?php echo anchor(get_uri('fotovoltaico/proposals/download/' . $proposal->id), app_lang('download_pdf'), array('class' => 'btn btn-sm btn-outline-secondary')); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $("#fv-recalc-btn").on("click", function () {
            var irradiation = $("#fv-input-irradiation").val().split(",").map(function (v) {
                return parseFloat(v.replace(",", ".").trim()) || 0;
            });
            if (irradiation.length !== 12) {
                appAlert.error("<?php echo app_lang('fv_irradiation_12'); ?>");
                return;
            }

            var payload = {
                system_power_kwp: $("#fv-input-power").val(),
                losses_percent: $("#fv-input-losses").val(),
                tariff_growth_percent_year: $("#fv-input-tariff-growth").val(),
                degradation_percent_year: $("#fv-input-degradation").val(),
                investment_value: $("#fv-input-investment").val(),
                opex_year: $("#fv-input-opex").val(),
                discount_rate_percent: $("#fv-input-discount").val(),
                irradiation_monthly: irradiation,
                tariff_snapshot: JSON.stringify(fvBuildTariffSnapshot())
            };

            $.post("<?php echo get_uri('fotovoltaico/projects/' . $project->id . '/calculate'); ?>", payload, function (result) {
                if (result && result.success) {
                    $("#fv-result-generation").text(result.generation_annual.toFixed(2) + " kWh");
                    $("#fv-result-savings").text(window.to_currency ? to_currency(result.savings_year1) : result.savings_year1);
                    $("#fv-result-payback").text(result.payback.years + "a " + result.payback.months + "m");
                    $("#fv-result-irr").text(result.irr.toFixed(2) + "%");
                    $("#fv-result-npv").text(window.to_currency ? to_currency(result.npv) : result.npv);
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        $("#fv-reg-profile").on("change", function () {
            var rules = $(this).find(":selected").data("rules");
            if (rules) {
                $("#fv-reg-snapshot").val(rules);
            }
        });

        $("#fv-save-snapshot").on("click", function () {
            var snapshot = $("#fv-reg-snapshot").val();
            var profile_id = $("#fv-reg-profile").val();
            $.post("<?php echo get_uri('fotovoltaico/projects/' . $project->id . '/regulatory_snapshot_save'); ?>", {
                profile_id: profile_id,
                snapshot_json: snapshot
            }, function (result) {
                if (result && result.success) {
                    appAlert.success(result.message);
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        $("#fv-generate-pdf").on("click", function () {
            var kitId = $("#fv-proposal-kit").val();
            $.post("<?php echo get_uri('fotovoltaico/projects/' . $project->id . '/proposal_generate'); ?>", {
                kit_id: kitId
            }, function (result) {
                if (result && result.success) {
                    appAlert.success("<?php echo app_lang('fv_pdf_generated'); ?>");
                    location.reload();
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        function fvFormatNumber(value) {
            var num = parseFloat(value || 0);
            if (isNaN(num)) {
                num = 0;
            }
            return num;
        }

        function fvUpdateTariffDisplays() {
            var mode = $("#fv-input-tariff-mode").val();
            var te = fvFormatNumber($("#fv-input-te").val());
            var tusd = fvFormatNumber($("#fv-input-tusd").val());
            var flags = fvFormatNumber($("#fv-input-flags").val());
            var total = mode === "components" ? (te + tusd + flags) : fvFormatNumber($("#fv-input-tariff-total").val());
            $("#fv-tariff-total-display").text(total.toFixed(4));
            $("#fv-tariff-breakdown-display").text("TE " + te.toFixed(4) + " | TUSD " + tusd.toFixed(4) + " | " + "<?php echo app_lang('fv_tariff_flags'); ?>" + " " + flags.toFixed(4));
        }

        function fvBuildTariffSnapshot() {
            var mode = $("#fv-input-tariff-mode").val();
            var manual = $("#fv-input-tariff-manual").is(":checked");
            var te = fvFormatNumber($("#fv-input-te").val());
            var tusd = fvFormatNumber($("#fv-input-tusd").val());
            var flags = fvFormatNumber($("#fv-input-flags").val());
            var total = mode === "components" ? (te + tusd + flags) : fvFormatNumber($("#fv-input-tariff-total").val());
            var utilityId = $("#fv-input-utility").val();
            var tariffId = $("#fv-input-tariff-id").val();
            var tariffLabel = $("#fv-input-tariff-id option:selected").text();

            var snapshot = {
                tariff_mode: mode,
                tariff_te: te,
                tariff_tusd: tusd,
                tariff_flags: flags,
                tariff_value: total,
                tariff_manual: manual ? 1 : 0,
                utility_id: utilityId || null,
                tariff_id: tariffId || null,
                tariff_label: tariffLabel || null,
                tariff_date: $("#fv-input-tariff-date").val()
            };

            if (mode !== "components") {
                snapshot.tariff_te = 0;
                snapshot.tariff_tusd = 0;
                snapshot.tariff_flags = 0;
            }

            return snapshot;
        }

        function fvToggleTariffMode() {
            var mode = $("#fv-input-tariff-mode").val();
            if (mode === "components") {
                $(".fv-tariff-components").removeClass("d-none");
                $(".fv-tariff-total").addClass("d-none");
            } else {
                $(".fv-tariff-components").addClass("d-none");
                $(".fv-tariff-total").removeClass("d-none");
            }
            fvUpdateTariffDisplays();
        }

        function fvToggleManualTariff() {
            var manual = $("#fv-input-tariff-manual").is(":checked");
            $("#fv-input-utility").prop("disabled", manual);
            $("#fv-input-tariff-id").prop("disabled", manual);
            $("#fv-input-tariff-date").prop("disabled", manual);
            $("#fv-input-tariff-mode").prop("disabled", false);
            if (!manual) {
                fvLoadTariffs();
            }
        }

        function fvLoadTariffs() {
            var utilityId = $("#fv-input-utility").val();
            if (!utilityId) {
                $("#fv-input-tariff-id").html("<option value=''>" + "<?php echo app_lang('choose'); ?>" + "</option>");
                return;
            }
            var date = $("#fv-input-tariff-date").val();
            $.getJSON("<?php echo get_uri('fotovoltaico/api/tariffs'); ?>/" + utilityId, {date: date}, function (result) {
                var options = "<option value=''>" + "<?php echo app_lang('choose'); ?>" + "</option>";
                if (result && result.data) {
                    $.each(result.data, function (idx, item) {
                        options += "<option value='" + item.id + "' data-te='" + item.te_value + "' data-tusd='" + item.tusd_value + "' data-flags='" + item.flags_value + "' data-total='" + item.total_value + "'>" + item.label + "</option>";
                    });
                }
                $("#fv-input-tariff-id").html(options);
            });
        }

        $("#fv-input-utility, #fv-input-tariff-date").on("change", function () {
            if (!$("#fv-input-tariff-manual").is(":checked")) {
                fvLoadTariffs();
            }
        });

        $("#fv-input-tariff-id").on("change", function () {
            var option = $(this).find(":selected");
            if (!option.val()) {
                return;
            }
            $("#fv-input-tariff-mode").val("components");
            $("#fv-input-te").val(option.data("te"));
            $("#fv-input-tusd").val(option.data("tusd"));
            $("#fv-input-flags").val(option.data("flags"));
            fvToggleTariffMode();
        });

        $("#fv-input-tariff-mode").on("change", fvToggleTariffMode);
        $("#fv-input-te, #fv-input-tusd, #fv-input-flags, #fv-input-tariff-total").on("input", fvUpdateTariffDisplays);
        $("#fv-input-tariff-manual").on("change", fvToggleManualTariff);

        $("#fv-save-tariff").on("click", function () {
            var snapshot = fvBuildTariffSnapshot();
            $.post("<?php echo get_uri('fotovoltaico/projects/' . $project->id . '/tariff_snapshot_save'); ?>", {
                utility_id: snapshot.utility_id,
                tariff_id: snapshot.tariff_id,
                snapshot_json: JSON.stringify(snapshot)
            }, function (result) {
                if (result && result.success) {
                    appAlert.success("<?php echo app_lang('fv_tariff_snapshot_saved'); ?>");
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        (function fvInitTariffState() {
            var existing = <?php echo json_encode($tariff_snapshot ?? []); ?>;
            if (existing && Object.keys(existing).length) {
                $("#fv-input-tariff-mode").val(existing.tariff_mode || "components");
                $("#fv-input-te").val(existing.tariff_te || 0);
                $("#fv-input-tusd").val(existing.tariff_tusd || 0);
                $("#fv-input-flags").val(existing.tariff_flags || 0);
                $("#fv-input-tariff-total").val(existing.tariff_value || 0);
                if (existing.utility_id) {
                    $("#fv-input-utility").val(existing.utility_id);
                    fvLoadTariffs();
                    setTimeout(function () {
                        $("#fv-input-tariff-id").val(existing.tariff_id || "");
                    }, 500);
                }
                if (existing.tariff_manual) {
                    $("#fv-input-tariff-manual").prop("checked", true);
                }
            }
            fvToggleTariffMode();
            fvToggleManualTariff();
            fvUpdateTariffDisplays();
        })();
    });
</script>
