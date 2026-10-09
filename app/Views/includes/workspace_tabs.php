<?php
$workspace_user_id = isset($login_user->id) ? (int)$login_user->id : 0;
$workspace_home_url = $workspace_home_url ?? get_uri('dashboard');
?>
<style>
    #app-workspace-tabs {
        display: flex;
        align-items: flex-end;
        gap: 3px;
        min-height: 43px;
        padding: 7px 10px 0;
        background: #f1f3f5;
        border-bottom: 1px solid #dfe3e7;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
    }

    .app-workspace-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-width: 135px;
        max-width: 240px;
        height: 35px;
        padding: 0 9px 0 12px;
        border: 1px solid #d7dce1;
        border-bottom: 0;
        border-radius: 7px 7px 0 0;
        background: #e5e8eb;
        color: #495057;
        cursor: pointer;
        flex: 0 0 auto;
        user-select: none;
    }

    .app-workspace-tab:hover {
        background: #f8f9fa;
    }

    .app-workspace-tab:not(.app-workspace-home-tab) {
        cursor: grab;
    }

    .app-workspace-tab:not(.app-workspace-home-tab):active {
        cursor: grabbing;
    }

    .app-workspace-tab.app-workspace-tab-dragging {
        opacity: .45;
    }

    .app-workspace-tab.app-workspace-tab-drop-before {
        box-shadow: -3px 0 0 #1f78d1;
    }

    .app-workspace-tab.app-workspace-tab-drop-after {
        box-shadow: 3px 0 0 #1f78d1;
    }

    .app-workspace-tab.active {
        background: #fff;
        color: #212529;
        border-color: #cfd5db;
        font-weight: 600;
    }

    .app-workspace-tab-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .app-workspace-tab-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #6c757d;
        font-size: 17px;
        line-height: 1;
    }

    .app-workspace-tab-close:hover {
        background: #d6d9dc;
        color: #dc3545;
    }

    #app-workspace-panels {
        display: none;
        width: 100%;
        background: #fff;
    }

    .app-workspace-panel {
        display: none;
        width: 100%;
    }

    .app-workspace-panel.active {
        display: block;
    }

    .app-workspace-frame {
        display: block;
        width: 100%;
        height: calc(100vh - 106px);
        min-height: 520px;
        border: 0;
        background: #fff;
    }

    .app-workspace-tab-loading .app-workspace-tab-title::after {
        content: " …";
        color: #6c757d;
    }

    @media (max-width: 767px) {
        #app-workspace-tabs {
            padding-left: 5px;
            padding-right: 5px;
        }

        .app-workspace-tab {
            min-width: 120px;
            max-width: 185px;
        }

        .app-workspace-frame {
            height: calc(100vh - 145px);
            min-height: 420px;
        }
    }
</style>

<div id="app-workspace-tabs" aria-label="Abas do sistema">
    <div class="app-workspace-tab active app-workspace-home-tab" data-tab-id="workspace-home" title="Página inicial">
        <i data-feather="home" class="icon-14"></i>
        <span class="app-workspace-tab-title">Início</span>
    </div>
</div>
<div id="app-workspace-panels"></div>

<script type="text/javascript">
(function () {
    "use strict";

    var storageKey = "rise_workspace_tabs_<?php echo $workspace_user_id; ?>";
    var activeStorageKey = storageKey + "_active";
    var homeTabId = "workspace-home";
    var homeUrl = <?php echo json_encode($workspace_home_url); ?>;
    var $tabs;
    var $panels;
    var $baseContent;
    var tabsState = [];

    function safeUrl(rawUrl) {
        try {
            var url = new URL(rawUrl, window.location.href);
            if (url.origin !== window.location.origin) {
                return null;
            }
            if (!/^https?:$/.test(url.protocol)) {
                return null;
            }
            url.hash = "";
            url.searchParams.delete("_workspace_tab");
            return url.toString();
        } catch (e) {
            return null;
        }
    }

    function frameUrl(rawUrl) {
        var url = new URL(rawUrl, window.location.href);
        url.searchParams.set("_workspace_tab", "1");
        return url.toString();
    }

    function tabIdForUrl(url) {
        var value = url.replace(window.location.origin, "").replace(/[^a-zA-Z0-9]/g, "_");
        var hash = 0;
        for (var i = 0; i < url.length; i++) {
            hash = ((hash << 5) - hash) + url.charCodeAt(i);
            hash |= 0;
        }
        return "workspace-tab-" + Math.abs(hash) + "-" + value.substring(0, 35);
    }

    function saveState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(tabsState));
            var active = $tabs.find(".app-workspace-tab.active").attr("data-tab-id") || homeTabId;
            localStorage.setItem(activeStorageKey, active);
        } catch (e) {
            // Storage can be disabled without affecting navigation.
        }
    }

    function activateTab(tabId) {
        if (tabId === homeTabId) {
            var currentHome = safeUrl(window.location.href);
            var configuredHome = safeUrl(homeUrl);
            if (configuredHome && currentHome !== configuredHome) {
                try {
                    localStorage.setItem(activeStorageKey, homeTabId);
                } catch (e) {
                    // Continue navigation when storage is unavailable.
                }
                window.location.href = configuredHome;
                return;
            }
        }

        $tabs.find(".app-workspace-tab").removeClass("active");
        $tabs.find('[data-tab-id="' + tabId + '"]').addClass("active");
        $panels.find(".app-workspace-panel").removeClass("active");

        if (tabId === homeTabId) {
            $panels.hide();
            $baseContent.show();
        } else {
            $baseContent.hide();
            $panels.show();
            $panels.find('[data-tab-id="' + tabId + '"]').addClass("active");
        }

        saveState();
    }

    function addTab(url, title, activate) {
        var normalizedUrl = safeUrl(url);
        if (!normalizedUrl) {
            return false;
        }

        var existing = tabsState.find(function (tab) {
            return tab.url === normalizedUrl;
        });
        if (existing) {
            if (title && existing.title !== title) {
                existing.title = title;
                $tabs.find('[data-tab-id="' + existing.id + '"] .app-workspace-tab-title').text(title);
            }
            if (activate !== false) {
                activateTab(existing.id);
            }
            return true;
        }

        var tab = {
            id: tabIdForUrl(normalizedUrl),
            url: normalizedUrl,
            title: title || "Nova aba"
        };
        tabsState.push(tab);

        var $tab = $('<div class="app-workspace-tab app-workspace-tab-loading" role="tab" draggable="true"></div>')
            .attr("data-tab-id", tab.id)
            .attr("title", tab.title + " — arraste para alterar a ordem");
        $tab.append($('<i data-feather="file" class="icon-14"></i>'));
        $tab.append($('<span class="app-workspace-tab-title"></span>').text(tab.title));
        $tab.append($('<button type="button" class="app-workspace-tab-close" aria-label="Fechar aba">&times;</button>'));
        $tabs.append($tab);

        var $panel = $('<div class="app-workspace-panel"></div>').attr("data-tab-id", tab.id);
        var $frame = $('<iframe class="app-workspace-frame" loading="eager"></iframe>')
            .attr("name", tab.id)
            .attr("title", tab.title)
            .attr("src", frameUrl(normalizedUrl));

        $frame.on("load", function () {
            $tab.removeClass("app-workspace-tab-loading");
            try {
                var currentUrl = safeUrl(this.contentWindow.location.href);
                if (currentUrl) {
                    tab.url = currentUrl;
                }
            } catch (e) {
                // Same-origin pages are expected; keep the previous URL otherwise.
            }
            saveState();
        });

        $panel.append($frame);
        $panels.append($panel);

        if (window.feather) {
            window.feather.replace();
        }

        if (activate !== false) {
            activateTab(tab.id);
        } else {
            saveState();
        }
        return true;
    }

    function syncTabOrder() {
        var order = [];
        $tabs.find(".app-workspace-tab:not(.app-workspace-home-tab)").each(function () {
            order.push($(this).attr("data-tab-id"));
        });

        tabsState.sort(function (a, b) {
            return order.indexOf(a.id) - order.indexOf(b.id);
        });
        saveState();
    }

    function clearDropIndicators() {
        $tabs.find(".app-workspace-tab")
            .removeClass("app-workspace-tab-drop-before app-workspace-tab-drop-after");
    }

    function closeTab(tabId) {
        var index = tabsState.findIndex(function (tab) {
            return tab.id === tabId;
        });
        if (index === -1) {
            return;
        }

        var wasActive = $tabs.find('[data-tab-id="' + tabId + '"]').hasClass("active");
        tabsState.splice(index, 1);
        $tabs.find('[data-tab-id="' + tabId + '"]').remove();
        $panels.find('[data-tab-id="' + tabId + '"]').remove();

        if (wasActive) {
            var next = tabsState[index] || tabsState[index - 1];
            activateTab(next ? next.id : homeTabId);
        } else {
            saveState();
        }
    }

    function restoreTabs() {
        var stored = [];
        try {
            stored = JSON.parse(localStorage.getItem(storageKey) || "[]");
        } catch (e) {
            stored = [];
        }

        if (!Array.isArray(stored)) {
            stored = [];
        }

        stored.slice(0, 15).forEach(function (tab) {
            if (tab && tab.url) {
                addTab(tab.url, tab.title || "Nova aba", false);
            }
        });

        var active = localStorage.getItem(activeStorageKey) || homeTabId;
        if (active !== homeTabId && !$tabs.find('[data-tab-id="' + active + '"]').length) {
            active = homeTabId;
        }
        activateTab(active);
    }

    $(document).ready(function () {
        $tabs = $("#app-workspace-tabs");
        $panels = $("#app-workspace-panels");
        $baseContent = $(".workspace-base-content");

        var draggedTabId = null;

        $tabs.on("dragstart", ".app-workspace-tab:not(.app-workspace-home-tab)", function (event) {
            draggedTabId = $(this).attr("data-tab-id");
            $(this).addClass("app-workspace-tab-dragging");
            var originalEvent = event.originalEvent;
            if (originalEvent && originalEvent.dataTransfer) {
                originalEvent.dataTransfer.effectAllowed = "move";
                originalEvent.dataTransfer.setData("text/plain", draggedTabId);
            }
        });

        $tabs.on("dragover", ".app-workspace-tab:not(.app-workspace-home-tab)", function (event) {
            if (!draggedTabId || $(this).attr("data-tab-id") === draggedTabId) {
                return;
            }
            event.preventDefault();
            clearDropIndicators();

            var originalEvent = event.originalEvent;
            var pointerX = originalEvent ? originalEvent.clientX : 0;
            var bounds = this.getBoundingClientRect();
            var insertBefore = pointerX < bounds.left + (bounds.width / 2);
            $(this).addClass(insertBefore ? "app-workspace-tab-drop-before" : "app-workspace-tab-drop-after");

            if (originalEvent && originalEvent.dataTransfer) {
                originalEvent.dataTransfer.dropEffect = "move";
            }
        });

        $tabs.on("drop", ".app-workspace-tab:not(.app-workspace-home-tab)", function (event) {
            event.preventDefault();
            if (!draggedTabId) {
                return;
            }

            var $dragged = $tabs.find('[data-tab-id="' + draggedTabId + '"]');
            var $target = $(this);
            if (!$dragged.length || $dragged.is($target)) {
                return;
            }

            if ($target.hasClass("app-workspace-tab-drop-before")) {
                $dragged.insertBefore($target);
            } else {
                $dragged.insertAfter($target);
            }

            clearDropIndicators();
            syncTabOrder();
        });

        $tabs.on("dragend", ".app-workspace-tab:not(.app-workspace-home-tab)", function () {
            $(this).removeClass("app-workspace-tab-dragging");
            clearDropIndicators();
            draggedTabId = null;
        });

        $tabs.on("click", ".app-workspace-tab", function (event) {
            if ($(event.target).closest(".app-workspace-tab-close").length) {
                return;
            }
            activateTab($(this).attr("data-tab-id"));
        });

        $tabs.on("click", ".app-workspace-tab-close", function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeTab($(this).closest(".app-workspace-tab").attr("data-tab-id"));
        });

        $(document).on("click", ".sidebar a[href]", function (event) {
            var $link = $(this);
            var href = $link.attr("href");

            if (!href || href === "#" || href.indexOf("javascript:") === 0 ||
                $link.attr("target") === "_blank" ||
                $link.attr("download") ||
                $link.attr("data-bs-toggle") ||
                $link.attr("data-toggle") ||
                $link.hasClass("sidebar-toggle-btn") ||
                event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
                return;
            }

            var normalizedUrl = safeUrl(href);
            if (!normalizedUrl) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            var title = $.trim($link.find(".menu-text").text()) ||
                $.trim($link.clone().find("i,svg").remove().end().text()) ||
                "Nova aba";
            addTab(normalizedUrl, title, true);

            if ($(window).width() < 768) {
                $("body").removeClass("sidebar-open");
            }
        });

        restoreTabs();
    });
})();
</script>
