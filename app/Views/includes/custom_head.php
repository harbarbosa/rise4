<?php

// add your custom header here.
?>
<script>
    (function () {
        function lockBootstrapModalDefaults() {
            if (!window.jQuery || !jQuery.fn || !jQuery.fn.modal || !jQuery.fn.modal.Constructor) {
                return;
            }

            var ctor = jQuery.fn.modal.Constructor;

            if (ctor.DEFAULTS) {
                ctor.DEFAULTS.backdrop = "static";
                ctor.DEFAULTS.keyboard = false;
            }

            if (ctor.Default) {
                ctor.Default.backdrop = "static";
                ctor.Default.keyboard = false;
            }
        }

        lockBootstrapModalDefaults();

        document.addEventListener("show.bs.modal", function (event) {
            if (!window.bootstrap || !bootstrap.Modal) {
                return;
            }

            var modalEl = event.target;
            var instance = bootstrap.Modal.getInstance(modalEl);

            if (instance && instance._config) {
                instance._config.backdrop = "static";
                instance._config.keyboard = false;
                return;
            }

            bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: "static",
                keyboard: false
            });
        });

        document.addEventListener("keyup", function (event) {
            if ((event.key === "Escape" || event.keyCode === 27) && document.body.classList.contains("app-modal-open")) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    })();
</script>
