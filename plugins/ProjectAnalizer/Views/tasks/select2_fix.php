<?php
// ProjectAnalizer task resource Select2 compatibility helper.
?>
<script type="text/javascript">
(function ($) {
    "use strict";
    window.ProjectAnalizerTaskResourceSelect = {
        init: function ($element) {
            if (!$element || !$element.length || typeof $.fn.select2 !== "function" || $element.data("select2")) {
                return;
            }
            $element.select2({width: "100%"});
        },
        initAll: function ($scope) {
            var self = this;
            ($scope || $(document)).find("select.material-select, select.tool-select, select.labor-profile-select").each(function () {
                self.init($(this));
            });
        }
    };
})(jQuery);
</script>
