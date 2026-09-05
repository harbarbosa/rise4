(function ($) {
    'use strict';

    function formatDateText(text) {
        var value = String(text || '').trim();
        var match = value.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/);
        if (!match) {
            return text;
        }

        var formatted = match[3] + '/' + match[2] + '/' + match[1];
        if (match[4] && match[5]) {
            formatted += ' ' + match[4] + ':' + match[5];
        }
        return formatted;
    }

    function formatFrotaTableDates(table) {
        $(table).find('tbody td').each(function () {
            var $cell = $(this);
            if ($cell.children().length) {
                return;
            }
            var original = $cell.text();
            var formatted = formatDateText(original);
            if (formatted !== original) {
                $cell.text(formatted);
            }
        });
    }

    function formatAllFrotaTables() {
        $('[id^="frota-"][id$="-table"]').each(function () {
            formatFrotaTableDates(this);
        });
    }

    $(document).on('draw.dt', '[id^="frota-"][id$="-table"]', function () {
        formatFrotaTableDates(this);
    });

    $(document).ajaxComplete(function () {
        window.setTimeout(formatAllFrotaTables, 0);
    });

    $(formatAllFrotaTables);
})(jQuery);
