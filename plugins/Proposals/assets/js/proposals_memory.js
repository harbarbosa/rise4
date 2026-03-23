(function ($) {
    var config = window.proposalsMemoryConfig || null;
    if (!config || !config.proposalId) {
        return;
    }

    var state = {
        proposalId: config.proposalId,
        sections: config.sections || [],
        items: config.items || [],
        collapsedSections: {},
        selectedSectionId: null,
        lastOpenSelect: null,
        pendingCreate: false
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


    function getSectionsByParent(parentId) {
        parentId = parentId || null;
        return state.sections.filter(function (section) {
            var pid = section.parent_id ? parseInt(section.parent_id, 10) : null;
            return pid === parentId;
        }).sort(function (a, b) {
            return (a.sort || 0) - (b.sort || 0);
        });
    }

    function getItemsBySection(sectionId) {
        return state.items.filter(function (item) {
            return parseInt(item.section_id, 10) === sectionId && parseInt(item.deleted || 0, 10) !== 1;
        }).sort(function (a, b) {
            return (a.sort || 0) - (b.sort || 0);
        });
    }

    function render() {
        var $container = $("#proposal-memory-sections");
        $container.empty();

        var topSections = getSectionsByParent(null);
        if (!topSections.length) {
            $container.append("<div class='text-muted'>" + config.labels.noItems + "</div>");
            bindEvents();
            updateTotals();
            return;
        }

        topSections.forEach(function (section) {
            $container.append(renderSection(section));
        });

        bindEvents();
        updateTotals();
        updateNewItemButtons();
    }

    function renderSection(section) {
        var sectionId = parseInt(section.id, 10);
        var title = section.title || config.labels.section;
        var canManage = config.canManage;
        var isCollapsed = !!state.collapsedSections[sectionId];

        var $section = $("<div class='card mb10 proposal-section' data-id='" + sectionId + "'></div>");
        var $header = $("<div class='card-header d-flex align-items-center justify-content-between'></div>");
        var $left = $("<div class='d-flex align-items-center gap10'></div>");
        var $toggle = $("<button type='button' class='btn btn-default btn-sm toggle-section'><i data-feather='chevron-down' class='icon-16'></i></button>");
        $left.append($toggle);

        if (canManage) {
            var $titleWrap = $("<div class='d-flex align-items-center gap5 section-title-wrap'></div>");
            var $titleText = $("<span class='section-title-text'></span>").text(title);
            $titleText.data("base-title", title);
            var $input = $("<input type='text' class='form-control section-title hide' value='' />");
            $input.val(title);
            var $editBtn = $("<button type='button' class='btn btn-default btn-sm edit-section-title' title='" + (config.labels.edit || "Editar") + "'><i data-feather='edit-2' class='icon-16'></i></button>");
            $titleWrap.append($titleText).append($input).append($editBtn);
            $left.append($titleWrap);
        } else {
            $left.append("<strong>" + title + "</strong>");
        }

        var $right = $("<div class='d-flex align-items-center gap5'></div>");
        if (canManage) {
            $right.append("<button type='button' class='btn btn-default btn-sm add-subsection' title='" + config.labels.addSubSection + "'><i data-feather='plus' class='icon-16'></i></button>");
            $right.append("<button type='button' class='btn btn-default btn-sm add-item' title='" + config.labels.item + "'><i data-feather='plus-square' class='icon-16'></i></button>");
            $right.append("<button type='button' class='btn btn-default btn-sm move-up' title='" + config.labels.moveUp + "'><i data-feather='arrow-up' class='icon-16'></i></button>");
            $right.append("<button type='button' class='btn btn-default btn-sm move-down' title='" + config.labels.moveDown + "'><i data-feather='arrow-down' class='icon-16'></i></button>");
            $right.append("<button type='button' class='btn btn-danger btn-sm delete-section' title='" + config.labels.remove + "'><i data-feather='x' class='icon-16'></i></button>");
        }

        $header.append($left).append($right);
        $section.append($header);

        var $body = $("<div class='card-body'></div>");
        if (isCollapsed) {
            $body.addClass("hide");
        }
        var items = getItemsBySection(sectionId);
        var children = getSectionsByParent(sectionId);
        if (items.length) {
            var $table = $("<table class='table table-bordered proposal-items-table mb10'></table>");
            var $thead = $("<thead><tr>" +
                "<th style='width:40%'>" + config.labels.item + "</th>" +
                "<th style='width:10%' class='text-end'>" + config.labels.quantity + "</th>" +
                "<th style='width:14%' class='text-end'>" + config.labels.costUnit + "</th>" +
                "<th style='width:12%' class='text-end'>" + config.labels.markupPercent + "</th>" +
                "<th style='width:14%' class='text-end'>" + config.labels.saleUnit + "</th>" +
                "<th style='width:10%' class='text-end'>" + config.labels.total + "</th>" +
                "<th style='width:14%'></th>" +
                "</tr></thead>");
            $table.append($thead);
            var $tbody = $("<tbody></tbody>");
            items.forEach(function (item) {
                $tbody.append(renderItemRow(item));
            });
            $table.append($tbody);
            $body.append($table);
        } else if (!children.length) {
            $body.append("<div class='text-center text-muted mb10'>" + config.labels.noItems + "</div>");
        }

        var $subsections = $("<div class='proposal-subsections'></div>");
        children.forEach(function (child) {
            $subsections.append(renderSection(child));
        });
        $body.append($subsections);

        $body.append("<div class='text-end'><strong>" + config.labels.totalCost + ":</strong> <span class='section-cost-total'>0,00</span> | <strong>" + config.labels.total + ":</strong> <span class='section-total'>0,00</span></div>");
        $section.append($body);

        return $section;
    }

    function syncCollapsedSectionsFromDom() {
        $(".proposal-section").each(function () {
            var $section = $(this);
            var sectionId = parseInt($section.data("id"), 10);
            if (!sectionId) {
                return;
            }

            state.collapsedSections[sectionId] = $section.find(".card-body").first().hasClass("hide");
        });
    }

    function renderItemRow(item) {
        var itemId = parseInt(item.id, 10);
        var itemTitle = item.item_title || "";
        var itemType = item.item_type || "material";
        var selectedItemId = item.item_id ? String(item.item_id) : "";
        if (itemType === "service" && selectedItemId) {
            selectedItemId = "s-" + selectedItemId;
        }
        var qty = item.qty || 1;
        var costUnit = item.cost_unit || 0;
        var markup = item.markup_percent || 0;
        var saleUnit = item.sale_unit || 0;
        var total = item.total || 0;

        var optionsHtml = config.itemsOptionsHtml || "<option value=''>-</option>";
        var $row = $("<tr class='proposal-item-row' data-id='" + itemId + "'></tr>");
        var displayLabel = itemTitle || "";
        var selectHiddenClass = selectedItemId ? "style='display:none'" : "";
        var displayHiddenClass = selectedItemId ? "" : "style='display:none'";
        $row.append("<td>" +
            "<input type='hidden' class='item-id' value='" + selectedItemId + "' />" +
            "<input type='hidden' class='item-type' value='" + itemType + "' />" +
            "<div class='item-display-wrap d-flex align-items-center gap5' " + displayHiddenClass + ">" +
            "<span class='item-display-text'>" + displayLabel + "</span>" +
            "<button type='button' class='btn btn-default btn-sm item-edit' title='" + (config.labels.edit || "Editar") + "'><i data-feather='edit-2' class='icon-16'></i></button>" +
            "<button type='button' class='btn btn-default btn-sm item-create-inline hide' title='" + (config.labels.addItem || "Adicionar") + "'>" + (config.labels.addItem || "Adicionar") + "</button>" +
            "</div>" +
            "<div class='item-select-wrap' " + selectHiddenClass + ">" +
            "<select class='form-control item-select'>" + optionsHtml + "</select>" +
            "</div>" +
            "</td>");
        $row.append("<td><input type='number' step='0.01' class='form-control text-end item-qty' value='" + formatNumber2(qty) + "' /></td>");
        $row.append("<td><input type='number' step='0.01' class='form-control text-end item-cost' value='" + formatNumber2(costUnit) + "' /></td>");
        $row.append("<td><input type='number' step='0.01' class='form-control text-end item-markup' value='" + markup + "' /></td>");
        $row.append("<td><input type='number' step='0.01' class='form-control text-end item-sale' value='" + formatNumber2(saleUnit) + "' /></td>");
        var costTotal = qty * costUnit;
        $row.append("<td class='text-end'><span class='proposal-item-total' data-total='" + total + "' data-cost-total='" + costTotal + "'>" + formatMoney(total) + "</span></td>");
        $row.append("<td class='text-center'><button type='button' class='btn btn-default btn-sm item-move-up' title='" + config.labels.moveUp + "'><i data-feather='arrow-up' class='icon-16'></i></button> " +
            "<button type='button' class='btn btn-default btn-sm item-move-down' title='" + config.labels.moveDown + "'><i data-feather='arrow-down' class='icon-16'></i></button> " +
            "<button type='button' class='btn btn-danger btn-sm delete-item' title='" + config.labels.remove + "'><i data-feather='x' class='icon-16'></i></button></td>");

        if (selectedItemId) {
            $row.find(".item-select").val(selectedItemId);
        }

        return $row;
    }

    function bindEvents() {
        if (!config.canManage) {
            initSelect2();
            return;
        }

        $(".proposal-section").off("click").on("click", function (e) {
            if ($(e.target).closest(".section-title, .item-select, .item-qty, .item-cost, .item-markup, .item-sale").length) {
                return;
            }
            $(".proposal-section").removeClass("selected");
            $(this).addClass("selected");
            state.selectedSectionId = parseInt($(this).attr("data-id"), 10);
        });

        $(".toggle-section").off("click").on("click", function () {
            var $section = $(this).closest(".proposal-section");
            var sectionId = parseInt($section.data("id"), 10);
            var $body = $section.find(".card-body").first();

            $body.toggleClass("hide");
            state.collapsedSections[sectionId] = $body.hasClass("hide");
            feather.replace();
        });

        $(".section-title").off("blur").on("blur", function () {
            var $section = $(this).closest(".proposal-section");
            var id = $section.data("id");
            var title = $(this).val();
            appAjaxRequest({
                url: config.endpoints.updateSection,
                type: "POST",
                dataType: "json",
                data: {id: id, title: title},
                success: function (result) {
                    if (result && result.success) {
                        $section.find(".section-title-text").text(title);
                        $section.find(".section-title-text").data("base-title", title);
                        $section.find(".section-title").addClass("hide");
                        $section.find(".section-title-text").removeClass("hide");
                    } else {
                        appAlert.error(result.message || AppLanugage.somethingWentWrong);
                    }
                }
            });
        });
        $(".section-title").off("keydown").on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                $(this).blur();
            }
        });

        $(".edit-section-title, .section-title-text").off("click").on("click", function () {
            var $wrap = $(this).closest(".section-title-wrap");
            $wrap.find(".section-title-text").addClass("hide");
            $wrap.find(".section-title").removeClass("hide").focus().select();
        });

        $(".add-subsection").off("click").on("click", function (e) {
            e.preventDefault();
            var $section = $(this).closest(".proposal-section");
            var parentId = $section.data("id");
            var title = prompt(config.labels.subSection);
            if (!title) {
                return;
            }
            addSection(title, parentId);
        });

        $(".add-item").off("click").on("click", function (e) {
            e.preventDefault();
            var $section = $(this).closest(".proposal-section");
            var sectionId = $section.data("id");
            addItem(sectionId);
        });

        $(".move-up, .move-down").off("click").on("click", function () {
            var $section = $(this).closest(".proposal-section");
            var $target = $(this).hasClass("move-up") ? $section.prev(".proposal-section") : $section.next(".proposal-section");
            if ($target.length) {
                if ($(this).hasClass("move-up")) {
                    $section.insertBefore($target);
                } else {
                    $section.insertAfter($target);
                }
                reorderSections($section.parent());
                feather.replace();
            }
        });

        $(".delete-section").off("click").on("click", function () {
            if (!confirm(config.labels.confirmDelete)) {
                return;
            }
            var $section = $(this).closest(".proposal-section");
            var id = $section.data("id");
            appAjaxRequest({
                url: config.endpoints.deleteSection,
                type: "POST",
                dataType: "json",
                data: {id: id},
                success: function (result) {
                    if (result && result.success) {
                        $section.remove();
                        updateTotals();
                        refreshDashboard();
                    } else {
                        appAlert.error(result.message || AppLanugage.somethingWentWrong);
                    }
                }
            });
        });

        $("#proposal-memory").find("[data-action='add-section']").off("click").on("click", function (e) {
            e.preventDefault();
            var title = prompt(config.labels.section);
            if (!title) {
                return;
            }
            addSection(title, null);
        });

        $("#proposal-memory").find("[data-action='add-item']").off("click").on("click", function (e) {
            e.preventDefault();
            if (!state.selectedSectionId) {
                appAlert.error(config.labels.selectSectionFirst);
                return;
            }
            addItem(state.selectedSectionId);
        });

        initSelect2();
        bindItemEvents();
    }

    function initSelect2() {
        $(".item-select").each(function () {
            var $select = $(this);
            if (!$select.is("select")) {
                return;
            }
            if ($select.data("select2")) {
                var currentVal = $select.val();
                $select.select2("destroy");
                $select.val(currentVal);
            }
            $select.select2({
                width: "100%",
                placeholder: config.labels.selectItem || "-"
            }).on("select2:open", function () {
                state.lastOpenSelect = $select;
                $select.data("last-term", "");
                state.pendingCreate = false;
                setTimeout(function () {
                    var $search = $(".select2-container--open .select2-search__field");
                    if ($search.length) {
                        $search.off("keyup.proposalsMemory input.proposalsMemory").on("keyup.proposalsMemory input.proposalsMemory", function () {
                            $select.data("last-term", $(this).val() || "");
                        });
                    }
                }, 0);
            }).on("select2:closing", function () {
                var term = "";
                var select2 = $select.data("select2");
                if (select2 && select2.dropdown && select2.dropdown.$search && select2.dropdown.$search.length) {
                    term = select2.dropdown.$search.val() || "";
                } else {
                    term = $(".select2-container--open .select2-search__field").val() || "";
                }
                $select.data("last-term", term);
            }).on("select2:select", function () {
                var $row = $select.closest("tr");
                var $selected = $select.find("option:selected");
                $row.find(".item-id").val($select.val() || "");
                $row.find(".item-type").val($selected.data("type") || "material");
                var selectedText = $selected.text();
                var rate = $selected.data("rate");
                var sale = $selected.data("sale");
                if (rate !== undefined && rate !== null && rate !== "") {
                    $row.find(".item-cost").val(formatNumber2(rate));
                }
                if (sale !== undefined && sale !== null && sale !== "") {
                    $row.find(".item-sale").val(formatNumber2(sale));
                }
                if (selectedText && selectedText !== "-") {
                    $row.find(".item-display-text").text(selectedText);
                    $row.find(".item-select-wrap").hide();
                    $row.find(".item-display-wrap").show();
                }
                updateRowTotals($row, sale !== undefined && sale !== null && sale !== "" ? "item-sale" : "item-select");
                saveItemRow($row);
            }).change(function () {
                var $row = $select.closest("tr");
                var $selected = $select.find("option:selected");
                $row.find(".item-id").val($select.val() || "");
                $row.find(".item-type").val($selected.data("type") || "material");
                var selectedText = $selected.text();
                var rate = $selected.data("rate");
                var sale = $selected.data("sale");
                if (rate !== undefined && rate !== null && rate !== "") {
                    $row.find(".item-cost").val(formatNumber2(rate));
                }
                if (sale !== undefined && sale !== null && sale !== "") {
                    $row.find(".item-sale").val(formatNumber2(sale));
                }
                if (selectedText && selectedText !== "-") {
                    $row.find(".item-display-text").text(selectedText);
                    $row.find(".item-select-wrap").hide();
                    $row.find(".item-display-wrap").show();
                }
                updateRowTotals($row, sale !== undefined && sale !== null && sale !== "" ? "item-sale" : "item-select");
                saveItemRow($row);
            }).on("select2:close", function () {
                var $row = $select.closest("tr");
                var currentId = $row.find(".item-id").val() || "";
                if (currentId) {
                    $row.find(".item-select-wrap").hide();
                    $row.find(".item-display-wrap").show();
                }
                var term = ($select.data("last-term") || "").toString().trim();
                if (!currentId && term.length >= 2 && !state.pendingCreate) {
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
                $row.removeData("item-prev-id");
            });
        });
        $(document).off("mousedown.proposalsMemorySelect").on("mousedown.proposalsMemorySelect", function (e) {
            if (!state.lastOpenSelect || !state.lastOpenSelect.data("select2")) {
                return;
            }
            if ($(e.target).closest(".select2-container, .select2-dropdown").length) {
                return;
            }
            var $select = state.lastOpenSelect;
            var term = ($select.data("last-term") || "").toString().trim();
            if (!term) {
                var $search = $(".select2-container--open .select2-search__field");
                if ($search.length) {
                    term = ($search.val() || "").toString().trim();
                }
            }
            if (!term || term.length < 2 || state.pendingCreate) {
                return;
            }
            var currentId = $select.closest("tr").find(".item-id").val() || "";
            if (currentId) {
                return;
            }
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
                openNewItemInput($select.closest("tr"), term);
            }
        });
        feather.replace();
    }

    function updateNewItemButtons() {
        var $buttons = $("#proposal-memory-sections .item-create-inline");
        $buttons.addClass("hide");
        var $targetRow = null;
        $("#proposal-memory-sections .proposal-item-row").each(function () {
            var $row = $(this);
            var currentId = ($row.find(".item-id").val() || "").toString().trim();
            if (!currentId) {
                $targetRow = $row;
            }
        });
        if (!$targetRow || !$targetRow.length) {
            return;
        }
        $targetRow.find(".item-create-inline").removeClass("hide");
    }

    function bindItemEvents() {
        $("#proposal-memory-sections").off("click", ".item-create-inline").on("click", ".item-create-inline", function (e) {
            e.preventDefault();
            var $row = $(this).closest("tr");
            var term = "";
            var $search = $(".select2-container--open .select2-search__field");
            if ($search.length) {
                term = ($search.val() || "").toString().trim();
            }
            openNewItemInput($row, term);
        });

        $("#proposal-memory-sections").off("click", ".item-new-save").on("click", ".item-new-save", function () {
            var $row = $(this).closest("tr");
            var title = ($row.find(".item-new-title").val() || "").toString().trim();
            if (!title) {
                appAlert.error(config.labels.selectItem || AppLanugage.fieldRequired);
                return;
            }
            var costUnit = parseNumber($row.find(".item-cost").val());
            var markup = parseNumber($row.find(".item-markup").val());
            var saleUnit = parseNumber($row.find(".item-sale").val());
            if (!saleUnit && costUnit) {
                saleUnit = costUnit * (1 + (markup / 100));
                $row.find(".item-sale").val(formatNumber2(saleUnit));
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
                    restoreItemSelect($row, String(data.id), true);
                    $row.find(".item-id").val(data.id);
                    $row.find(".item-type").val("material");
                    $row.find(".item-display-text").text(data.title || title);
                    $row.find(".item-display-wrap").show();
                    $row.find(".item-select-wrap").hide();
                    $row.find(".item-edit, .item-create-inline").removeClass("hide");
                    $row.removeClass("is-new-item");
                    state.pendingCreate = false;
                    saveItemRow($row);
                }
            });
        });

        $("#proposal-memory-sections").off("click", ".item-new-cancel").on("click", ".item-new-cancel", function () {
            var $row = $(this).closest("tr");
            restoreItemSelect($row);
            $row.removeClass("is-new-item");
            state.pendingCreate = false;
        });

        $("#proposal-memory-sections").off("keydown", ".item-new-title").on("keydown", ".item-new-title", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                $(this).closest("tr").find(".item-new-save").trigger("click");
            } else if (e.key === "Escape") {
                e.preventDefault();
                $(this).closest("tr").find(".item-new-cancel").trigger("click");
            }
        });

        $(".item-qty, .item-cost, .item-markup, .item-sale").off("change blur").on("change blur", function () {
            var $row = $(this).closest("tr");
            if ($(this).hasClass("item-qty") || $(this).hasClass("item-cost") || $(this).hasClass("item-sale")) {
                $(this).val(formatNumber2($(this).val()));
            }
            updateRowTotals($row, $(this).attr("class"));
            if ($row.hasClass("is-new-item") && !$row.find(".item-id").val()) {
                return;
            }
            saveItemRow($row);
        });
        $(".item-edit, .item-display-text").off("click").on("click", function () {
            var $row = $(this).closest("tr");
            $row.data("item-prev-id", $row.find(".item-id").val() || "");
            $row.find(".item-display-wrap").hide();
            $row.find(".item-select-wrap").show();
            $row.find(".item-select").select2("open");
        });

        $(".delete-item").off("click").on("click", function () {
            if (!confirm(config.labels.confirmDelete)) {
                return;
            }
            var $row = $(this).closest("tr");
            var id = $row.data("id");
            if (parseInt(id, 10) < 0) {
                removeItemState(id);
                $row.remove();
                updateTotals();
                return;
            }
            appAjaxRequest({
                url: config.endpoints.deleteItem,
                type: "POST",
                dataType: "json",
                data: {id: id},
                success: function (result) {
                    if (result && result.success) {
                        removeItemState(id);
                        $row.remove();
                        updateTotals();
                        refreshDashboard();
                    } else {
                        appAlert.error(result.message || AppLanugage.somethingWentWrong);
                    }
                }
            });
        });

        $(".item-move-up, .item-move-down").off("click").on("click", function () {
            var $row = $(this).closest("tr");
            var $target = $(this).hasClass("item-move-up") ? $row.prev(".proposal-item-row") : $row.next(".proposal-item-row");
            if ($target.length) {
                if ($(this).hasClass("item-move-up")) {
                    $row.insertBefore($target);
                } else {
                    $row.insertAfter($target);
                }
                reorderItems($row.closest("tbody"));
                feather.replace();
            }
        });
    }

    function updateRowTotals($row, sourceClass) {
        var qty = parseNumber($row.find(".item-qty").val());
        var cost = parseNumber($row.find(".item-cost").val());
        var markup = parseNumber($row.find(".item-markup").val());
        var sale = parseNumber($row.find(".item-sale").val());
        var source = sourceClass || "";

        if (source.indexOf("item-cost") !== -1 || source.indexOf("item-markup") !== -1) {
            if (cost > 0) {
                sale = cost * (1 + (markup / 100));
                $row.find(".item-sale").val(sale.toFixed(2));
            }
        } else if (source.indexOf("item-sale") !== -1) {
            if (cost > 0) {
                markup = ((sale / cost) - 1) * 100;
                $row.find(".item-markup").val(markup.toFixed(2));
            }
            $row.find(".item-sale").val(sale.toFixed(2));
        } else if (!sale && cost) {
            sale = cost * (1 + (markup / 100));
            $row.find(".item-sale").val(sale.toFixed(2));
        }

        var total = qty * sale;
        var costTotal = qty * cost;
        $row.find(".proposal-item-total").attr("data-total", total).text(formatMoney(total));
        $row.find(".proposal-item-total").attr("data-cost-total", costTotal);
        updateTotals();
    }

    function updateTotals() {
        $(".proposal-section").each(function () {
            var total = 0;
            var costTotal = 0;
            $(this).find(".proposal-item-row").each(function () {
                var $row = $(this);
                var qty = parseNumber($row.find(".item-qty").val());
                var cost = parseNumber($row.find(".item-cost").val());
                var lineTotal = parseNumber($row.find(".proposal-item-total").attr("data-total"));
                var lineCostTotal = qty * cost;
                $row.find(".proposal-item-total").attr("data-cost-total", lineCostTotal);
                total += lineTotal;
                costTotal += lineCostTotal;
            });
            var $titleText = $(this).find(".section-title-text");
            var baseTitle = $titleText.data("base-title");
            if (!baseTitle) {
                baseTitle = $titleText.text().trim();
                var splitIndex = baseTitle.indexOf(" - ");
                if (splitIndex > 0) {
                    baseTitle = baseTitle.substring(0, splitIndex).trim();
                }
                $titleText.data("base-title", baseTitle);
            }
            $titleText.text(baseTitle);

            $(this).find(".section-total").text(formatMoney(total));
            $(this).find(".section-cost-total").text(formatMoney(costTotal));
        });

        var general = 0;
        var generalCost = 0;
        $("#proposal-memory-sections").find(".proposal-item-row").each(function () {
            var $row = $(this);
            var qty = parseNumber($row.find(".item-qty").val());
            var cost = parseNumber($row.find(".item-cost").val());
            general += parseNumber($row.find(".proposal-item-total").attr("data-total"));
            generalCost += qty * cost;
        });
        $("#proposal-memory-total").text(formatMoney(general));
        $("#proposal-memory-total-cost").text(formatMoney(generalCost));
    }

    function syncStateFromDom() {
        $(".proposal-item-row").each(function () {
            var $row = $(this);
            var id = parseInt($row.data("id"), 10);
            if (!id) {
                return;
            }
            var sectionId = parseInt($row.closest(".proposal-section").data("id"), 10) || null;
            var itemId = $row.find(".item-id").val() || $row.find(".item-select").val() || "";
            var itemType = $row.find(".item-type").val() || ($row.find(".item-select option:selected").data("type") || "material");
            var itemTitle = $row.find(".item-display-text").text().trim() || $row.find(".item-select option:selected").text() || "";
            var qty = parseNumber($row.find(".item-qty").val());
            var costUnit = parseNumber($row.find(".item-cost").val());
            var markup = parseNumber($row.find(".item-markup").val());
            var saleUnit = parseNumber($row.find(".item-sale").val());
            var total = qty * saleUnit;

            for (var i = 0; i < state.items.length; i++) {
                if (parseInt(state.items[i].id, 10) === id) {
                    state.items[i] = $.extend({}, state.items[i], {
                        section_id: sectionId,
                        item_id: itemId,
                        item_type: itemType,
                        item_title: itemTitle,
                        qty: qty,
                        cost_unit: costUnit,
                        markup_percent: markup,
                        sale_unit: saleUnit,
                        total: total
                    });
                    break;
                }
            }
        });
    }

    function getSelectHtml() {
        return "<select class='form-control item-select'>" + (config.itemsOptionsHtml || "<option value=''>-</option>") + "</select>";
    }

    function restoreItemSelect($row, selectedId, silent) {
        var $wrap = $row.find(".item-select-wrap");
        $wrap.html(getSelectHtml());
        var $select = $wrap.find(".item-select");
        initSelect2($select);
        $row.find(".item-edit, .item-create-inline").removeClass("hide");
        if (selectedId) {
            $select.val(selectedId);
            if (!silent) {
                $select.trigger("change");
            }
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
        $row.find(".item-edit, .item-create-inline").addClass("hide");
        $display.hide();
        $wrap.show();
        if ($wrap.find(".item-new-wrap").length) {
            $wrap.find(".item-new-title").val(term || "").focus().select();
            return;
        }
        var titleValue = escapeHtml(term || "");
        var saveLabel = (config.labels && config.labels.save) ? config.labels.save : "Salvar";
        var cancelLabel = (config.labels && config.labels.cancel) ? config.labels.cancel : "Cancelar";
        var html = "<div class='item-new-wrap d-flex align-items-center gap5'>" +
            "<input type='text' class='form-control item-new-title' value='" + titleValue + "' />" +
            "<button type='button' class='btn btn-primary btn-sm item-new-save'>" + saveLabel + "</button>" +
            "<button type='button' class='btn btn-default btn-sm item-new-cancel'>" + cancelLabel + "</button>" +
            "</div>";
        $wrap.data("prev-select-html", $wrap.html());
        $wrap.html(html);
        $row.addClass("is-new-item");
        $wrap.find(".item-new-title").focus().select();
    }

    function refreshDashboard() {
        if (!config.endpoints || !config.endpoints.dashboardData) {
            return;
        }
        appAjaxRequest({
            url: config.endpoints.dashboardData,
            type: "POST",
            dataType: "json",
            data: {proposal_id: state.proposalId},
            success: function (result) {
                if (!result || !result.success || !result.data) {
                    return;
                }
                var data = result.data;
                $("#proposal-dash-cost-material").text(data.total_cost_material || "-");
                $("#proposal-dash-cost-service").text(data.total_cost_service || "-");
                $("#proposal-dash-total-sale").text(data.total_sale || "-");
                $("#proposal-dash-taxes").text(data.taxes_total || "-");
                $("#proposal-dash-commission").text(data.commission_total || "-");
                $("#proposal-dash-gross-profit").text(data.gross_profit || "-");
                $("#proposal-dash-net-profit").text(data.net_profit || "-");
                $("#proposal-dash-markup").text(data.markup_avg || "-");
                $("#proposal-dash-status").html(data.status || "-");
                $("#proposal-dash-updated").text(data.updated_at || "-");
                $("#proposal-dash-created-by").text(data.created_by || "-");
                updateBreakdownBar(data);
            }
        });
    }

    function updateBreakdownBar(data) {
        var $box = $(".proposal-breakdown");
        if (!$box.length || !data) {
            return;
        }
        var total = parseNumber(data.total_sale_n || 0);
        var cost = parseNumber(data.total_cost_material_n || 0) + parseNumber(data.total_cost_service_n || 0);
        var tax = parseNumber(data.taxes_total_n || 0);
        var commission = parseNumber(data.commission_total_n || 0);
        var profit = parseNumber(data.net_profit_n || 0);
        if (total <= 0) {
            $box.find(".proposal-breakdown-bar span").css("width", "0%");
            return;
        }
        var pctCost = (cost / total) * 100;
        var pctTax = (tax / total) * 100;
        var pctCommission = (commission / total) * 100;
        var pctProfit = (profit / total) * 100;

        $box.find(".breakdown-cost")
            .css("width", pctCost + "%")
            .attr("title", formatNumber2(pctCost) + "% | " + formatMoney(cost));
        $box.find(".breakdown-tax")
            .css("width", pctTax + "%")
            .attr("title", formatNumber2(pctTax) + "% | " + formatMoney(tax));
        $box.find(".breakdown-commission")
            .css("width", pctCommission + "%")
            .attr("title", formatNumber2(pctCommission) + "% | " + formatMoney(commission));
        $box.find(".breakdown-profit")
            .css("width", pctProfit + "%")
            .attr("title", formatNumber2(pctProfit) + "% | " + formatMoney(profit));
    }

    function addSection(title, parentId) {
        syncCollapsedSectionsFromDom();
        appAjaxRequest({
            url: config.endpoints.addSection,
            type: "POST",
            dataType: "json",
            data: {
                proposal_id: state.proposalId,
                title: title,
                parent_id: parentId || ""
            },
            success: function (result) {
                if (result && result.success && result.data) {
                    state.sections.push(result.data);
                    render();
                    refreshDashboard();
                } else {
                    appAlert.error(result.message || AppLanugage.somethingWentWrong);
                }
            }
        });
    }

    function addItem(sectionId) {
        syncCollapsedSectionsFromDom();
        syncStateFromDom();
        var tempId = -1 * (new Date().getTime());
        state.items.push({
            id: tempId,
            section_id: sectionId,
            qty: 1,
            cost_unit: 0,
            markup_percent: 0,
            sale_unit: 0,
            total: 0,
            item_type: "material",
            item_title: ""
        });
        render();
    }

    function saveItemRow($row) {
        var id = $row.data("id");
        var currentItem = getItemState(id) || {};
        var itemId = $row.find(".item-id").val() || $row.find(".item-select").val();
        var selectedText = $row.find(".item-select option:selected").text() || $row.find(".item-display-text").text() || "";
        var itemType = $row.find(".item-type").val() || ($row.find(".item-select option:selected").data("type") || "material");
        if (!itemId) {
            return;
        }
        var sectionId = $row.closest(".proposal-section").data("id");
        var showInProposal = currentItem.show_in_proposal ? 1 : 0;
        var showValues = currentItem.show_values_in_proposal ? 1 : 0;
        var inMemory = currentItem.in_memory !== undefined ? (currentItem.in_memory ? 1 : 0) : 1;
        var payload = {
            id: id,
            item_id: itemId,
            description: selectedText && selectedText !== "-" ? selectedText : "",
            item_type: itemType,
            qty: $row.find(".item-qty").val(),
            cost_unit: $row.find(".item-cost").val(),
            markup_percent: $row.find(".item-markup").val(),
            sale_unit: $row.find(".item-sale").val(),
            show_in_proposal: showInProposal,
            show_values_in_proposal: showValues,
            in_memory: inMemory
        };

        if (parseInt(id, 10) < 0) {
            payload.id = "";
            payload.proposal_id = state.proposalId;
            payload.section_id = sectionId;
            appAjaxRequest({
                url: config.endpoints.addItem,
                type: "POST",
                dataType: "json",
                data: payload,
                success: function (result) {
                    if (result && result.success && result.data) {
                        var newId = result.data.id;
                        $row.attr("data-id", newId).data("id", newId);
                        updateItemState(id, result.data);
                        updateTotals();
                        refreshDashboard();
                    } else if (result && result.message) {
                        appAlert.error(result.message);
                    }
                }
            });
            return;
        }

        appAjaxRequest({
            url: config.endpoints.updateItem,
            type: "POST",
            dataType: "json",
            data: payload,
            success: function (result) {
                if (result && result.success) {
                    if (result.data) {
                        result.data.id = id;
                        if (selectedText && selectedText !== "-") {
                            result.data.item_title = selectedText;
                        }
                        updateItemState(id, result.data);
                    }
                    updateTotals();
                    refreshDashboard();
                } else if (result && result.message) {
                    appAlert.error(result.message);
                }
            }
        });
    }

    function updateItemState(id, data) {
        for (var i = 0; i < state.items.length; i++) {
            if (parseInt(state.items[i].id, 10) === parseInt(id, 10)) {
                state.items[i] = $.extend({}, state.items[i], data);
                break;
            }
        }
    }

    function getItemState(id) {
        var targetId = parseInt(id, 10);
        for (var i = 0; i < state.items.length; i++) {
            if (parseInt(state.items[i].id, 10) === targetId) {
                return state.items[i];
            }
        }
        return null;
    }

    function removeItemState(id) {
        var targetId = parseInt(id, 10);
        state.items = state.items.filter(function (item) {
            return parseInt(item.id, 10) !== targetId;
        });
    }

    function reorderSections($container) {
        var order = [];
        $container.children(".proposal-section").each(function () {
            order.push($(this).data("id"));
        });
        appAjaxRequest({
            url: config.endpoints.reorder,
            type: "POST",
            dataType: "json",
            data: {type: "section", order: order}
        });
    }

    function reorderItems($tbody) {
        var order = [];
        $tbody.find(".proposal-item-row").each(function () {
            var id = $(this).data("id");
            if (parseInt(id, 10) > 0) {
                order.push(id);
            }
        });
        if (!order.length) {
            return;
        }
        appAjaxRequest({
            url: config.endpoints.reorder,
            type: "POST",
            dataType: "json",
            data: {type: "item", order: order}
        });
    }

    render();
})(jQuery);
