<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_kit_builder'); ?>: <?php echo esc($kit->name); ?></h1>
                <div class="title-button-group">
                    <a href="<?php echo get_uri('fotovoltaico/kits'); ?>" class="btn btn-default">
                        <i data-feather="arrow-left"></i> <?php echo app_lang('back'); ?>
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card p20">
                        <h4><?php echo app_lang('fv_add_item'); ?></h4>

                        <div class="form-group">
                            <label><?php echo app_lang('fv_item_type'); ?></label>
                            <select id="fv-item-type" class="form-control">
                                <option value="product"><?php echo app_lang('fv_item_product'); ?></option>
                                <option value="custom"><?php echo app_lang('fv_item_custom'); ?></option>
                            </select>
                        </div>

                        <div id="fv-product-fields">
                            <div class="form-group">
                                <label><?php echo app_lang('type'); ?></label>
                                <select id="fv-product-type" class="form-control">
                                    <option value=""><?php echo app_lang('all'); ?></option>
                                    <option value="module">module</option>
                                    <option value="inverter">inverter</option>
                                    <option value="service">service</option>
                                    <option value="structure">structure</option>
                                    <option value="stringbox">stringbox</option>
                                    <option value="cable">cable</option>
                                    <option value="other">other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><?php echo app_lang('fv_product'); ?></label>
                                <select id="fv-product-search" class="form-control"></select>
                            </div>

                            <div class="form-group">
                                <label><?php echo app_lang('qty'); ?></label>
                                <input type="text" id="fv-product-qty" class="form-control" value="1" />
                            </div>
                        </div>

                        <div id="fv-custom-fields" style="display:none;">
                            <div class="form-group">
                                <label><?php echo app_lang('name'); ?></label>
                                <input type="text" id="fv-custom-name" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label><?php echo app_lang('description'); ?></label>
                                <input type="text" id="fv-custom-description" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label><?php echo app_lang('unit'); ?></label>
                                <input type="text" id="fv-custom-unit" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label><?php echo app_lang('qty'); ?></label>
                                <input type="text" id="fv-custom-qty" class="form-control" value="1" />
                            </div>
                            <div class="form-group">
                                <label><?php echo app_lang('cost'); ?></label>
                                <input type="text" id="fv-custom-cost" class="form-control" value="0" />
                            </div>
                            <div class="form-group">
                                <label><?php echo app_lang('price'); ?></label>
                                <input type="text" id="fv-custom-price" class="form-control" value="0" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="fv-item-optional" value="1" />
                                <?php echo app_lang('optional'); ?>
                            </label>
                        </div>

                        <button type="button" class="btn btn-primary" id="fv-add-item-btn">
                            <i data-feather="plus-circle"></i> <?php echo app_lang('add'); ?>
                        </button>
                    </div>

                    <div class="card p20">
                        <h4><?php echo app_lang('fv_kit_summary'); ?></h4>
                        <div id="fv-kit-summary">
                            <p><?php echo app_lang('fv_power_kwp'); ?>: <span data-field="power_kwp">0</span></p>
                            <p><?php echo app_lang('fv_module_count'); ?>: <span data-field="module_count">0</span></p>
                            <p><?php echo app_lang('fv_inverters'); ?>: <span data-field="inverters">-</span></p>
                            <p><?php echo app_lang('cost'); ?>: <span data-field="cost_total">0</span></p>
                            <p><?php echo app_lang('price'); ?>: <span data-field="price_total">0</span></p>
                            <p><?php echo app_lang('fv_markup_real'); ?>: <span data-field="markup_percent">0</span>%</p>
                            <p><?php echo app_lang('fv_default_losses'); ?>: <?php echo number_format((float)($kit->default_losses_percent ?? 0), 2, ',', '.'); ?>%</p>
                        </div>
                    </div>

                    <div class="card p20 mtop20">
                        <h4><?php echo app_lang('fv_electrical_validation'); ?></h4>
                        <div id="fv-electrical-result">
                            <p><strong><?php echo app_lang('status'); ?>:</strong> <span data-field="status">-</span></p>
                            <p><strong><?php echo app_lang('fv_suggestion'); ?>:</strong> <span data-field="suggestion">-</span></p>
                            <p><strong><?php echo app_lang('fv_mppt_distribution'); ?>:</strong> <span data-field="distribution">-</span></p>
                            <div data-field="messages"></div>
                        </div>
                        <button type="button" class="btn btn-default" id="fv-validate-btn">
                            <i data-feather="activity"></i> <?php echo app_lang('fv_validate_now'); ?>
                        </button>
                        <button type="button" class="btn btn-primary" id="fv-save-suggestion-btn">
                            <i data-feather="save"></i> <?php echo app_lang('fv_save_suggestion'); ?>
                        </button>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card p20">
                        <h4><?php echo app_lang('fv_kit_items'); ?></h4>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="fv-kit-items-table">
                                <thead>
                                <tr>
                                    <th><?php echo app_lang('fv_order'); ?></th>
                                    <th><?php echo app_lang('fv_item_type'); ?></th>
                                    <th><?php echo app_lang('fv_product_name'); ?></th>
                                    <th><?php echo app_lang('qty'); ?></th>
                                    <th><?php echo app_lang('unit'); ?></th>
                                    <th><?php echo app_lang('cost'); ?></th>
                                    <th><?php echo app_lang('price'); ?></th>
                                    <th><?php echo app_lang('fv_total_cost'); ?></th>
                                    <th><?php echo app_lang('fv_total_price'); ?></th>
                                    <th><?php echo app_lang('optional'); ?></th>
                                    <th><?php echo app_lang('actions'); ?></th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <small class="text-muted"><?php echo app_lang('fv_drag_reorder'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var kitId = <?php echo (int)$kit->id; ?>;

        function loadItems() {
            $.getJSON("<?php echo get_uri('fotovoltaico/kits/items'); ?>/" + kitId, function (result) {
                if (!result || !result.success) {
                    return;
                }
                renderItems(result.data.items || []);
                renderSummary(result.data.totals || {});
            });
        }

        function renderSummary(totals) {
            $("#fv-kit-summary [data-field='power_kwp']").text((totals.power_kwp || 0).toFixed(2) + " kWp");
            $("#fv-kit-summary [data-field='module_count']").text(totals.module_count || 0);
            $("#fv-kit-summary [data-field='inverters']").text((totals.inverters || []).join(", ") || "-");
            $("#fv-kit-summary [data-field='cost_total']").text(window.to_currency ? to_currency(totals.cost_total || 0) : (totals.cost_total || 0));
            $("#fv-kit-summary [data-field='price_total']").text(window.to_currency ? to_currency(totals.price_total || 0) : (totals.price_total || 0));
            $("#fv-kit-summary [data-field='markup_percent']").text((totals.markup_percent || 0).toFixed(2));
        }

        function renderItems(items) {
            var $tbody = $("#fv-kit-items-table tbody");
            $tbody.empty();
            items.forEach(function (item) {
                var itemType = item.item_type || "product";
                var name = itemType === "custom" ? item.name : (item.brand + " " + item.model);
                var productInactive = itemType === "product" && item.product_active === "0";
                if (productInactive) {
                    name += " <span class='badge bg-warning'><?php echo app_lang('fv_product_inactive'); ?></span>";
                }

                var unit = itemType === "custom" ? (item.unit || "") : "";
                var unitCost = itemType === "custom" ? parseFloat(item.cost || 0) : parseFloat(item.product_cost || 0);
                var unitPrice = itemType === "custom" ? parseFloat(item.price || 0) : parseFloat(item.product_price || 0);
                var qty = parseFloat(item.qty || 1);

                var row = "<tr data-id='" + item.id + "'>" +
                    "<td class='drag-handle'>☰</td>" +
                    "<td>" + itemType + "</td>" +
                    "<td>" + name + "</td>" +
                    "<td><input type='text' class='form-control form-control-sm js-qty' value='" + qty + "'></td>" +
                    "<td><input type='text' class='form-control form-control-sm js-unit' value='" + unit + "' " + (itemType === "product" ? "disabled" : "") + "></td>" +
                    "<td><input type='text' class='form-control form-control-sm js-cost' value='" + unitCost + "' " + (itemType === "product" ? "disabled" : "") + "></td>" +
                    "<td><input type='text' class='form-control form-control-sm js-price' value='" + unitPrice + "' " + (itemType === "product" ? "disabled" : "") + "></td>" +
                    "<td>" + (unitCost * qty).toFixed(2) + "</td>" +
                    "<td>" + (unitPrice * qty).toFixed(2) + "</td>" +
                    "<td><input type='checkbox' class='js-optional' " + (item.is_optional == 1 ? "checked" : "") + "></td>" +
                    "<td><button class='btn btn-sm btn-danger js-delete'><i data-feather='x' class='icon-16'></i></button></td>" +
                    "</tr>";
                $tbody.append(row);
            });
            feather.replace();
            enableSort();
        }

        function enableSort() {
            if ($.fn.sortable) {
                $("#fv-kit-items-table tbody").sortable({
                    handle: ".drag-handle",
                    update: function () {
                        var ids = [];
                        $("#fv-kit-items-table tbody tr").each(function () {
                            ids.push($(this).data("id"));
                        });
                        $.post("<?php echo get_uri('fotovoltaico/kits/items/reorder'); ?>", {kit_id: kitId, ordered_ids: ids}, function () {
                            loadItems();
                        });
                    }
                });
            }
        }

        function renderElectrical(result) {
            if (!result) {
                return;
            }
            $("#fv-electrical-result [data-field='status']").text(result.status || "-");
            if (result.suggestion) {
                $("#fv-electrical-result [data-field='suggestion']").text(result.suggestion.modules_in_series + "S x " + result.suggestion.strings_total + " strings");
                $("#fv-electrical-result [data-field='distribution']").text((result.suggestion.strings_per_mppt_distribution || []).join(", "));
            } else {
                $("#fv-electrical-result [data-field='suggestion']").text("-");
                $("#fv-electrical-result [data-field='distribution']").text("-");
            }
            var $msgs = $("#fv-electrical-result [data-field='messages']");
            $msgs.empty();
            (result.messages || []).forEach(function (m) {
                var cls = m.level === "error" ? "danger" : (m.level === "warning" ? "warning" : "info");
                $msgs.append("<div class='alert alert-" + cls + " p5 mtop5'>" + m.text + "</div>");
            });
        }

        $("#fv-item-type").on("change", function () {
            if ($(this).val() === "custom") {
                $("#fv-product-fields").hide();
                $("#fv-custom-fields").show();
            } else {
                $("#fv-custom-fields").hide();
                $("#fv-product-fields").show();
            }
        });

        $("#fv-product-search").select2({
            ajax: {
                url: "<?php echo get_uri('fotovoltaico/api/products'); ?>",
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        active: 1,
                        type: $("#fv-product-type").val()
                    };
                },
                processResults: function (data) {
                    var results = [];
                    if (data && data.data) {
                        data.data.forEach(function (item) {
                            results.push({id: item.id, text: item.label});
                        });
                    }
                    return {results: results};
                }
            },
            width: "100%"
        });

        $("#fv-add-item-btn").on("click", function () {
            var itemType = $("#fv-item-type").val();
            var payload = {kit_id: kitId, item_type: itemType, is_optional: $("#fv-item-optional").is(":checked") ? 1 : 0};

            if (itemType === "product") {
                payload.product_id = $("#fv-product-search").val();
                payload.qty = $("#fv-product-qty").val();
            } else {
                payload.name = $("#fv-custom-name").val();
                payload.description = $("#fv-custom-description").val();
                payload.unit = $("#fv-custom-unit").val();
                payload.qty = $("#fv-custom-qty").val();
                payload.cost = $("#fv-custom-cost").val();
                payload.price = $("#fv-custom-price").val();
            }

            $.post("<?php echo get_uri('fotovoltaico/kits/items/add'); ?>", payload, function (result) {
                if (result && result.success) {
                    loadItems();
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        $("#fv-kit-items-table").on("change", ".js-qty, .js-optional, .js-unit, .js-cost, .js-price", function () {
            var $row = $(this).closest("tr");
            var payload = {
                id: $row.data("id"),
                qty: $row.find(".js-qty").val(),
                is_optional: $row.find(".js-optional").is(":checked") ? 1 : 0,
                unit: $row.find(".js-unit").val(),
                cost: $row.find(".js-cost").val(),
                price: $row.find(".js-price").val()
            };
            $.post("<?php echo get_uri('fotovoltaico/kits/items/update'); ?>", payload, function () {
                loadItems();
            }, "json");
        });

        $("#fv-kit-items-table").on("click", ".js-delete", function () {
            var id = $(this).closest("tr").data("id");
            $.post("<?php echo get_uri('fotovoltaico/kits/items/delete'); ?>", {id: id}, function () {
                loadItems();
            }, "json");
        });

        $("#fv-validate-btn").on("click", function () {
            $.post("<?php echo get_uri('fotovoltaico/kits/validate_electrical'); ?>", {kit_id: kitId}, function (result) {
                if (result && result.success) {
                    renderElectrical(result.data);
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        $("#fv-save-suggestion-btn").on("click", function () {
            $.post("<?php echo get_uri('fotovoltaico/kits/validate_electrical'); ?>", {kit_id: kitId, save: 1}, function (result) {
                if (result && result.success) {
                    renderElectrical(result.data);
                    appAlert.success("<?php echo app_lang('record_saved'); ?>");
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            }, "json");
        });

        loadItems();
    });
</script>
