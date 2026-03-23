(function ($) {
    var config = window.proposalsDocumentConfig || null;
    if (!config || !config.proposalId) {
        return;
    }

    function getDisplayMode() {
        return $("input[name='display_mode']:checked").val() || "detailed";
    }

    function getPayload() {
        return {
            proposal_id: config.proposalId,
            display_mode: getDisplayMode(),
            description: $("#proposal-document-description").val(),
            payment_terms: $("#proposal-document-payment").val(),
            observations: $("#proposal-document-observations").val(),
            validity_days: $("#proposal-document-validity").val()
        };
    }

    function renderPreview(html) {
        $("#proposal-document-preview").html(html || "");
    }

    function refreshPreview() {
        if (window.appLoader && appLoader.show) {
            appLoader.show();
        }
        appAjaxRequest({
            url: config.endpoints.preview,
            type: "POST",
            dataType: "json",
            data: getPayload(),
            success: function (result) {
                if (result && result.success) {
                    renderPreview(result.html);
                } else if (result && result.message) {
                    appAlert.error(result.message);
                }
                if (window.appLoader && appLoader.hide) {
                    appLoader.hide();
                }
            }
        });
    }

    function saveDocument() {
        if (window.appLoader && appLoader.show) {
            appLoader.show();
        }
        appAjaxRequest({
            url: config.endpoints.save,
            type: "POST",
            dataType: "json",
            data: getPayload(),
            success: function (result) {
                if (result && result.success) {
                    renderPreview(result.html);
                    appAlert.success(result.message || config.labels.saved);
                } else if (result && result.message) {
                    appAlert.error(result.message);
                } else {
                    appAlert.error(config.labels.error);
                }
                if (window.appLoader && appLoader.hide) {
                    appLoader.hide();
                }
            }
        });
    }

    function openDocumentWindow(autoPrint) {
        var html = $("#proposal-document-preview").html() || "";
        var win = window.open("", "_blank");
        if (!win) {
            return;
        }
        var title = (config && config.filename) ? config.filename : "Proposta";
        win.document.open();
        win.document.write("<!doctype html><html><head><meta charset='utf-8'><title>" + title + "</title></head><body>" + html + "</body></html>");
        win.document.close();
        if (autoPrint) {
            win.focus();
            win.print();
        }
    }

    $(document).on("change", "#proposal-document input[name='display_mode']", function () {
        refreshPreview();
    });

    $(document).on("click", "#proposal-document-save", function () {
        saveDocument();
    });

    $(document).on("click", "#proposal-document-print", function () {
        openDocumentWindow(true);
    });

    $(document).on("click", "#proposal-document-pdf", function () {
        if (config.endpoints && config.endpoints.downloadPdf) {
            window.location.href = config.endpoints.downloadPdf;
            return;
        }
        openDocumentWindow(true);
    });

})(jQuery);
