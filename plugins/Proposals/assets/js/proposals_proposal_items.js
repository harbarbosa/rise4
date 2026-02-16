(function ($) {
    var config = window.proposalsProposalItemsConfig || null;
    if (!config || !config.proposalId) {
        return;
    }

    var state = {
        proposalId: config.proposalId,
        items: config.items || [],
        pendingCreate: false,
        lastOpenSelect: null
    };

    function parseNumber(value) {
        if (value === null || value === undefined) {
            return 0;
        }
        if (typeof value === "number") {
            return isNaN(value) ? 0 : value;
        }
        var text = value.toString().trim();
        if (!text) {
            return 0;
        }
        text = text.replace(/[^\d,.\-]/g, "");
        var lastComma = text.lastIndexOf(",");
        var lastDot = text.lastIndexOf(".");
        if (lastComma > -1 && lastDot > -1) {
            if (lastComma > lastDot) {
                text = text.replace(/\./g, "");
                text = text.replace(",", ".");
            } else {
                text = text.replace(/,/g, "");
            }
        } else if (lastComma > -1) {
            text = text.replace(/\./g, "");
            text = text.replace(",", ".");
        } else {
            text = text.replace(/,/g, "");
        }
        var num = parseFloat(text);
        return isNaN(num) ? 0 : num;
    }

    function formatMoney(value) {
        if (typeof toCurrency === "function") {
            return toCurrency(value || 0);
        }
        return (value || 0).toFixed(2);
    }

    function formatNumber2(value) {
        return parseNumber(value).toFixed(2);
    }

    function escapeHtml(text) {
        return $("<div>").text(text || "").html();
    }

    function getItemTitle(item) {
        var title = item.description_override || item.item_title || "-";
        if (typeof title === "string") {
            title = title.replace(/^\s*\[servico\]\s*/i, "");
        }
        return title;
    }

    function getItemUnit(item) {
        if (item.item_unit) {
            return item.item_unit;
        }
        if (item.item_type === "service") {
            return "SERV";
        }
        return "UN";
    }

    function initItemSelect($select) {
        $select.select2({
            width: "100%",
            placeholder: config.labels.selectItem || "-"
        });
    }

    function buildRow(item) {
        var itemId = item.id ? parseInt(item.id, 10) : 0;
        var itemTitle = getItemTitle(item);
        var itemType = item.item_type || "material";
        var selectedItemId = item.item_id ? String(item.item_id) : "";
        if (itemType === "service" && selectedItemId) {
            selectedItemId = "s-" + selectedItemId;
        }

        var qty = item.qty || 1;
        var costUnit = item.cost_unit || 0;
        var saleUnit = item.sale_unit || 0;
        var total = item.total || (qty * saleUnit);

        var optionsHtml = config.itemsOptionsHtml || "<option value=''>-</option>";
        var $row = $("<tr class='proposal-item-row'></tr>");
        if (itemId) {
            $row.attr("data-id", itemId);
        }

        var displayHidden = selectedItemId ? "" : "style='display:none'";
        var selectHidden = selectedItemId ? "style='display:none'" : "";

        var $itemCell = $("<td></td>");
        var $displayWrap = $("<div class='item-display-wrap d-flex align-items-center gap5' " + displayHidden + "></div>");
        var $displayText = $("<span class='item-display-text'></span>").text(itemTitle);
        var $editBtn = $("<button type='button' class='btn btn-default btn-sm item-edit'><i data-feather='edit-2' class='icon-16'></i></button>");
        $displayWrap.append($displayText).append($editBtn);

        var $selectWrap = $("<div class='item-select-wrap' " + selectHidden + "></div>");
        var $select = $("<select class='form-control item-select w-100'></select>").html(optionsHtml);
        $selectWrap.append($select);

        $itemCell.append("<input type='hidden' class='item-id' value='" + selectedItemId + "' />");
        $itemCell.append("<input type='hidden' class='item-type' value='" + itemType + "' />");
        $itemCell.append("<input type='hidden' class='item-cost' value='" + formatNumber2(costUnit) + "' />");
        $itemCell.append($displayWrap).append($selectWrap);

        var $qty = $("<input type='number' step='0.01' class='form-control text-end item-qty' />").val(formatNumber2(qty));
        var $unit = $("<span class='item-unit-text'></span>").text(getItemUnit(item));
        var $sale = $("<input type='number' step='0.01' class='form-control text-end item-sale' />").val(formatNumber2(saleUnit));
        var $total = $("<span class='item-total-text'></span>").text(formatMoney(total));
        var $actions = $("<button type='button' class='btn btn-danger btn-sm item-remove'><i data-feather='x' class='icon-16'></i></button>");

        $row.append($itemCell);
        $row.append($("<td class='text-end'></td>").append($qty));
        $row.append($("<td></td>").append($unit));
        $row.append($("<td class='text-end'></td>").append($sale));
        $row.append($("<td class='text-end'></td>").append($total));
        $row.append($("<td class='text-center'></td>").append($actions));

        if (selectedItemId) {
            $select.val(selectedItemId);
        }
        initItemSelect($select);
        $select.on("select2:open", function () {
            state.lastOpenSelect = $select;
            $select.data("last-term", "");
            state.pendingCreate = false;
            setTimeout(function () {
                var $search = $(".select2-container--open .select2-search__field");
                if ($search.length) {
                    $search.off("keyup.proposalsItems").on("keyup.proposalsItems", function () {
                        $select.data("last-term", $(this).val() || "");
                    });
                }
            }, 0);
        });
        $select.on("select2:closing", function () {
            var term = "";
            var select2 = $select.data("select2");
            if (select2 && select2.dropdown && select2.dropdown.$search && select2.dropdown.$search.length) {
                term = select2.dropdown.$search.val() || "";
            } else {
                term = $(".select2-container--open .select2-search__field").val() || "";
            }
            $select.data("last-term", term);
        });
        $select.on("select2:select", function () {
            var $option = $select.find("option:selected");
            applySelectedItem($row, $option);
        });
        $select.on("change", function () {
            var $option = $select.find("option:selected");
            applySelectedItem($row, $option);
        });
        $select.on("select2:close", function () {
            if ($row.find(".item-id").val()) {
                $row.find(".item-select-wrap").hide();
                $row.find(".item-display-wrap").show();
            }
            var term = ($select.data("last-term") || "").toString().trim();
            if (!$row.find(".item-id").val() && term.length >= 2 && !state.pendingCreate) {
                var hasMatch = false;
                var termLower = term.toLowerCase();
                $select.find("option").each(function () {
                    var text = ($(this).text() || "").toLowerCase().trim();
                    if (text === termLower) {
                        hasMatch = true;
                        return false;
                    }
                });
                if (!hasMatch) {
                    state.pendingCreate = true;
                    openNewItemInput($row, term);
                }
            }
        });

        return $row;
    }

    function getSelectHtml() {
        return "<select class='form-control item-select w-100'>" + (config.itemsOptionsHtml || "<option value=''>-</option>") + "</select>";
    }

    function restoreItemSelect($row, selectedId) {
        var $wrap = $row.find(".item-select-wrap");
        $wrap.html(getSelectHtml());
        var $select = $wrap.find(".item-select");
        initItemSelect($select);
        if (selectedId) {
            $select.val(selectedId).trigger("change");
        }
    }

    function appendItemOption(id, title, rate, unit) {
        var optionHtml = "<option value='" + id + "' data-rate='" + rate + "' data-unit='" + escapeHtml(unit) + "' data-type='material'>" + escapeHtml(title) + "</option>";
        if (!config.itemsOptionsHtml) {
            config.itemsOptionsHtml = "<option value=''>-</option>";
        }
        if (config.itemsOptionsHtml.indexOf("value='" + id + "'") === -1) {
            config.itemsOptionsHtml += optionHtml;
        }
        $(".item-select").each(function () {
            if ($(this).find("option[value='" + id + "']").length) {
                return;
            }
            $(this).append(optionHtml);
        });
    }

    function openNewItemInput($row, term) {
        var $wrap = $row.find(".item-select-wrap");
        var $display = $row.find(".item-display-wrap");
        $display.hide();
        $wrap.show();
        if ($wrap.find(".item-new-wrap").length) {
            $wrap.find(".item-new-title").val(term || "").focus().select();
            return;
        }
        var titleValue = escapeHtml(term || "");
        var costValue = formatNumber2($row.find(".item-cost").val());
        var saleValue = formatNumber2($row.find(".item-sale").val());
        var saveLabel = (config.labels && config.labels.save) ? config.labels.save : "Salvar";
        var cancelLabel = (config.labels && config.labels.cancel) ? config.labels.cancel : "Cancelar";
        var html = "<div class='item-new-wrap d-flex align-items-center gap5'>" +
            "<input type='text' class='form-control item-new-title' value='" + titleValue + "' />" +
            "<input type='number' step='0.01' class='form-control item-new-cost text-end' value='" + costValue + "' />" +
            "<input type='number' step='0.01' class='form-control item-new-sale text-end' value='" + saleValue + "' />" +
            "<button type='button' class='btn btn-primary btn-sm item-new-save'>" + saveLabel + "</button>" +
            "<button type='button' class='btn btn-default btn-sm item-new-cancel'>" + cancelLabel + "</button>" +
            "</div>";
        $wrap.data("prev-select-html", $wrap.html());
        $wrap.html(html);
        $row.addClass("is-new-item");
        $wrap.find(".item-new-title").focus().select();
    }

    function render() {
        var $tbody = $("#proposal-proposal-items-table tbody");
        $tbody.empty();

        var items = state.items.filter(function (item) {
            return parseInt(item.deleted || 0, 10) !== 1;
        }).sort(function (a, b) {
            return (a.sort || 0) - (b.sort || 0);
        });

        if (!items.length) {
            $("#proposal-proposal-items-empty").removeClass("hide");
        } else {
            $("#proposal-proposal-items-empty").addClass("hide");
        }

        items.forEach(function (item) {
            $tbody.append(buildRow(item));
        });

        bindEvents();
        updateTotals();
    }

    function updateRowTotals($row) {
        var qty = parseNumber($row.find(".item-qty").val());
        var saleUnit = parseNumber($row.find(".item-sale").val());
        var total = qty * saleUnit;
        $row.find(".item-total-text").text(formatMoney(total));
        updateTotals();
    }

    function updateTotals() {
        var total = 0;
        $("#proposal-proposal-items-table tbody tr").each(function () {
            var $row = $(this);
            var qty = parseNumber($row.find(".item-qty").val());
            var saleUnit = parseNumber($row.find(".item-sale").val());
            total += qty * saleUnit;
        });
        $("#proposal-proposal-items-total").text(formatMoney(total));
    }

    function refreshDocumentPreview() {
        if (!window.proposalsDocumentConfig || !window.proposalsDocumentConfig.endpoints || !window.proposalsDocumentConfig.endpoints.preview) {
            return;
        }
        var payload = { proposal_id: state.proposalId };
        var $mode = $("input[name='display_mode']:checked");
        if ($mode.length) {
            payload.display_mode = $mode.val();
        }
        if ($("#proposal-document-description").length) {
            payload.description = $("#proposal-document-description").val();
        }
        if ($("#proposal-document-payment").length) {
            payload.payment_terms = $("#proposal-document-payment").val();
        }
        if ($("#proposal-document-observations").length) {
            payload.observations = $("#proposal-document-observations").val();
        }
        if ($("#proposal-document-validity").length) {
            payload.validity_days = $("#proposal-document-validity").val();
        }
        appAjaxRequest({
            url: window.proposalsDocumentConfig.endpoints.preview,
            type: "POST",
            dataType: "json",
            data: payload,
            success: function (result) {
                if (result && result.success && $("#proposal-document-preview").length) {
                    $("#proposal-document-preview").html(result.html || "");
                }
            }
        });
    }

    function parseMarkup(costUnit, saleUnit) {
        if (costUnit <= 0) {
            return 0;
        }
        return ((saleUnit / costUnit) - 1) * 100;
    }

    function saveRow($row) {
        if (!config.canManage) {
            return;
        }

        var id = parseInt($row.attr("data-id") || 0, 10);
        var itemId = $row.find(".item-id").val();
        var itemType = $row.find(".item-type").val() || "material";
        var description = $row.find(".item-display-text").text().trim();
        var qty = parseNumber($row.find(".item-qty").val());
        var saleUnit = parseNumber($row.find(".item-sale").val());
        var costUnit = parseNumber($row.find(".item-cost").val());
        var markup = parseMarkup(costUnit, saleUnit);

        if (!itemId) {
            return;
        }

        var payload = {
            proposal_id: state.proposalId,
            item_id: itemId,
            item_type: itemType,
            description: description,
            qty: qty,
            cost_unit: costUnit,
            markup_percent: markup,
            sale_unit: saleUnit,
            show_in_proposal: 1,
            show_values_in_proposal: 1,
            in_memory: 0
        };

        var url = config.endpoints.addItem;
        if (id) {
            payload.id = id;
            url = config.endpoints.updateItem;
        }

        $.ajax({
            url: url,
            type: "POST",
            dataType: "json",
            data: payload,
            success: function (res) {
                if (!res || !res.success) {
                    appAlert.error(res && res.message ? res.message : "");
                    return;
                }
                if (!id && res.data && res.data.id) {
                    $row.attr("data-id", res.data.id);
                    state.items.push(res.data);
                }
                refreshDocumentPreview();
            }
        });
    }

    function applySelectedItem($row, $option) {
        var itemId = $option.val();
        if (!itemId) {
            return;
        }

        var itemType = $option.data("type") || "material";
        var rate = parseNumber($option.data("rate"));
        var sale = $option.data("sale");
        var markupPercent = parseNumber(config.defaultMarkupPercent || 0);
        var saleUnit = sale !== undefined && sale !== null && sale !== "" ? parseNumber(sale) : (rate * (1 + (markupPercent / 100)));
        var unit = $option.data("unit");
        if (!unit) {
            unit = itemType === "service" ? "SERV" : "UN";
        }

        $row.find(".item-id").val(itemId);
        $row.find(".item-type").val(itemType);
        $row.find(".item-cost").val(formatNumber2(rate));
        $row.find(".item-sale").val(formatNumber2(saleUnit));
        $row.find(".item-unit-text").text(unit);
        $row.find(".item-display-text").text($option.text());

        updateRowTotals($row);
        saveRow($row);

        $row.find(".item-select-wrap").hide();
        $row.find(".item-display-wrap").show();
    }

    function bindEvents() {
        $("#proposal-proposal-items-table").off("click", ".item-new-save").on("click", ".item-new-save", function () {
            if (!config.canManage) {
                return;
            }
            var $row = $(this).closest("tr");
            var title = ($row.find(".item-new-title").val() || "").toString().trim();
            if (!title) {
                appAlert.error(config.labels.selectItem || "");
                return;
            }
            var costUnit = parseNumber($row.find(".item-new-cost").val());
            var saleUnit = parseNumber($row.find(".item-new-sale").val());
            if (!saleUnit && costUnit) {
                var markupPercent = parseNumber(config.defaultMarkupPercent || 0);
                saleUnit = costUnit * (1 + (markupPercent / 100));
            }
            var markupPercent = costUnit > 0 ? ((saleUnit / costUnit) - 1) * 100 : 0;
            var endpoint = config.endpoints && config.endpoints.createItemQuick ? config.endpoints.createItemQuick : "";
            if (!endpoint) {
                return;
            }
            appAjaxRequest({
                url: endpoint,
                type: "POST",
                dataType: "json",
                data: {
                    title: title,
                    rate: costUnit,
                    sale: saleUnit,
                    markup: markupPercent,
                    unit_type: "UN"
                },
                success: function (result) {
                    if (!result || !result.success || !result.data) {
                        appAlert.error(result && result.message ? result.message : "");
                        return;
                    }
                    var data = result.data;
                    appendItemOption(data.id, data.title || title, data.rate || costUnit, data.unit_type || "UN");
                    restoreItemSelect($row, String(data.id));
                    $row.find(".item-id").val(data.id);
                    $row.find(".item-type").val("material");
                    $row.find(".item-cost").val(formatNumber2(costUnit));
                    $row.find(".item-sale").val(formatNumber2(saleUnit));
                    $row.find(".item-display-text").text(data.title || title);
                    $row.find(".item-display-wrap").show();
                    $row.find(".item-select-wrap").hide();
                    $row.removeClass("is-new-item");
                    state.pendingCreate = false;
                    updateRowTotals($row);
                    saveRow($row);
                }
            });
        });

        $("#proposal-proposal-items-table").off("click", ".item-new-cancel").on("click", ".item-new-cancel", function () {
            var $row = $(this).closest("tr");
            restoreItemSelect($row);
            $row.removeClass("is-new-item");
            state.pendingCreate = false;
        });

        $("#proposal-proposal-items-table").off("keydown", ".item-new-title").on("keydown", ".item-new-title", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                $(this).closest("tr").find(".item-new-save").trigger("click");
            } else if (e.key === "Escape") {
                e.preventDefault();
                $(this).closest("tr").find(".item-new-cancel").trigger("click");
            }
        });

        $("#proposal-items-add-item").off("click").on("click", function (e) {
            e.preventDefault();
            if (!config.canManage) {
                return;
            }
            var newItem = {
                id: 0,
                item_id: "",
                item_type: "material",
                description_override: "",
                qty: 1,
                cost_unit: 0,
                sale_unit: 0,
                total: 0
            };
            var $row = buildRow(newItem);
            $("#proposal-proposal-items-table tbody").append($row);
            $row.find(".item-select-wrap").show();
            $row.find(".item-display-wrap").hide();
            $row.find(".item-select").select2("open");
            bindEvents();
            updateTotals();
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        });

        $("#proposal-items-copy-from-memory").off("click").on("click", function (e) {
            e.preventDefault();
            if (!config.canManage) {
                return;
            }
            $.ajax({
                url: config.endpoints.copyItems,
                type: "POST",
                dataType: "json",
                data: { proposal_id: state.proposalId },
                success: function (res) {
                    if (!res || !res.success) {
                        appAlert.error(res && res.message ? res.message : "");
                        return;
                    }
                    location.reload();
                }
            });
        });

        $("#proposal-proposal-items-table").off("click", ".item-edit").on("click", ".item-edit", function () {
            if (!config.canManage) {
                return;
            }
            var $row = $(this).closest("tr");
            $row.find(".item-select-wrap").show();
            $row.find(".item-display-wrap").hide();
            $row.find(".item-select").select2("open");
        });

        $("#proposal-proposal-items-table").off("input", ".item-qty, .item-sale").on("input", ".item-qty, .item-sale", function () {
            var $row = $(this).closest("tr");
            updateRowTotals($row);
        });

        $("#proposal-proposal-items-table").off("change", ".item-qty, .item-sale").on("change", ".item-qty, .item-sale", function () {
            var $row = $(this).closest("tr");
            saveRow($row);
        });

        $("#proposal-proposal-items-table").off("click", ".item-remove").on("click", ".item-remove", function () {
            if (!config.canManage) {
                return;
            }
            var $row = $(this).closest("tr");
            var id = parseInt($row.attr("data-id") || 0, 10);
            if (!id) {
                $row.remove();
                updateTotals();
                return;
            }
            if (config.labels.confirmDelete && !confirm(config.labels.confirmDelete)) {
                return;
            }
            $.ajax({
                url: config.endpoints.deleteItem,
                type: "POST",
                dataType: "json",
                data: { id: id },
            success: function (res) {
                if (!res || !res.success) {
                    appAlert.error(res && res.message ? res.message : "");
                    return;
                }
                $row.remove();
                updateTotals();
                refreshDocumentPreview();
            }
        });
    });

        if (typeof feather !== "undefined") {
            feather.replace();
        }
    }

    $(document).ready(function () {
        render();
    });
})(jQuery);
