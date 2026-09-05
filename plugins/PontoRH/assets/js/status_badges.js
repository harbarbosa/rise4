(function () {
    'use strict';

    var statusMap = {
        'completo': 'pontorh-status-complete',
        'incompleto': 'pontorh-status-incomplete',
        'inconsistente': 'pontorh-status-inconsistent',
        'fora do local': 'pontorh-status-outside',
        'sem foto': 'pontorh-status-no-photo',
        'ajuste solicitado': 'pontorh-status-adjustment',
        'aguardando justificativa': 'pontorh-status-awaiting',
        'tratado manualmente': 'pontorh-status-treated',
        'fechado': 'pontorh-status-closed',
        'pendente': 'pontorh-status-pending',
        'aprovado': 'pontorh-status-approved',
        'rejeitado': 'pontorh-status-rejected'
    };

    function ensureStyles() {
        if (document.getElementById('pontorh-status-styles')) {
            return;
        }
        var style = document.createElement('style');
        style.id = 'pontorh-status-styles';
        style.textContent = [
            '.pontorh-status-badge{display:inline-flex;align-items:center;padding:5px 9px;border-radius:6px;font-size:12px;font-weight:600;line-height:1.2;border:0!important}',
            '.pontorh-status-complete{background:#198754!important;color:#fff!important}',
            '.pontorh-status-incomplete{background:#dc3545!important;color:#fff!important}',
            '.pontorh-status-inconsistent{background:#fd7e14!important;color:#fff!important}',
            '.pontorh-status-outside{background:#ffc107!important;color:#4b3d00!important}',
            '.pontorh-status-no-photo{background:#6f42c1!important;color:#fff!important}',
            '.pontorh-status-adjustment{background:#0d6efd!important;color:#fff!important}',
            '.pontorh-status-awaiting{background:#0dcaf0!important;color:#17333b!important}',
            '.pontorh-status-treated{background:#20c997!important;color:#123f34!important}',
            '.pontorh-status-closed{background:#343a40!important;color:#fff!important}',
            '.pontorh-status-pending{background:#6c757d!important;color:#fff!important}',
            '.pontorh-status-approved{background:#198754!important;color:#fff!important}',
            '.pontorh-status-rejected{background:#b02a37!important;color:#fff!important}'
        ].join('');
        document.head.appendChild(style);
    }

    function normalizeText(value) {
        return (value || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function translateApproved(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        var nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }
        nodes.forEach(function (node) {
            if (normalizeText(node.nodeValue) === 'approved') {
                node.nodeValue = node.nodeValue.replace(/approved/i, 'Aprovado');
            }
        });
    }

    function paintStatuses(root) {
        var scope = root || document;
        translateApproved(scope);
        scope.querySelectorAll('.badge').forEach(function (badge) {
            var text = normalizeText(badge.textContent);
            var cssClass = statusMap[text];
            if (!cssClass) {
                return;
            }
            Object.keys(statusMap).forEach(function (key) {
                badge.classList.remove(statusMap[key]);
            });
            badge.classList.add('pontorh-status-badge', cssClass);
        });
    }

    function init() {
        ensureStyles();
        paintStatuses(document);
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        paintStatuses(node);
                    }
                });
            });
        });
        observer.observe(document.body, {childList: true, subtree: true});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
