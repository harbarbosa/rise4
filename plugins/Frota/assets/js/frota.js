(function ($) {
    'use strict';

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function rawKmFromStored(value) {
        var text = String(value == null ? '' : value).trim();
        if (/^\d+\.\d+$/.test(text)) {
            text = text.split('.')[0];
        }
        return onlyDigits(text);
    }

    function formatThousands(raw) {
        raw = onlyDigits(raw).replace(/^0+(?=\d)/, '');
        return raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    }

    function formatPlate(value) {
        var chars = String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').split('');
        var expected = ['L', 'L', 'L', 'N', 'L', 'N', 'N'];
        var accepted = [];
        var pos = 0;

        chars.forEach(function (ch) {
            if (pos >= expected.length) return;
            var ok = expected[pos] === 'L' ? /[A-Z]/.test(ch) : /[0-9]/.test(ch);
            if (ok) {
                accepted.push(ch);
                pos++;
            }
        });

        var out = accepted.slice(0, 3).join('');
        if (accepted.length > 3) out += '-' + accepted.slice(3).join('');
        return out;
    }

    function prepareKmInput($input) {
        if ($input.data('frota-mask-ready')) return;
        var fieldName = $input.attr('name');
        if (!fieldName) return;

        var raw = rawKmFromStored($input.val());
        var hiddenId = ($input.attr('id') || fieldName) + '_raw';
        var $hidden = $('<input>', {type: 'hidden', id: hiddenId, name: fieldName, value: raw});

        $input.attr('name', fieldName + '_formatted');
        $input.attr({inputmode: 'numeric', autocomplete: 'off', maxlength: 15});
        $input.val(formatThousands(raw));
        $input.after($hidden);
        $input.data('frota-mask-ready', true);
        $input.data('frota-hidden-id', hiddenId);
    }

    function syncKmInput($input) {
        prepareKmInput($input);
        var raw = onlyDigits($input.val());
        var hiddenId = $input.data('frota-hidden-id');
        $input.val(formatThousands(raw));
        if (hiddenId) $('#' + hiddenId).val(raw);
    }

    function prepareMasks(context) {
        var $context = context ? $(context) : $(document);

        $context.find('.frota-plate-mask').each(function () {
            var $input = $(this);
            $input.attr({maxlength: 8, autocomplete: 'off'});
            $input.val(formatPlate($input.val()));
        });

        $context.find('.frota-year-mask').each(function () {
            var $input = $(this);
            $input.attr({maxlength: 4, inputmode: 'numeric', autocomplete: 'off'});
            $input.val(onlyDigits($input.val()).slice(0, 4));
        });

        $context.find('.frota-km-mask').each(function () {
            prepareKmInput($(this));
        });

        $context.find('.frota-number-mask').each(function () {
            var $input = $(this);
            $input.attr({inputmode: 'numeric', autocomplete: 'off'});
            $input.val(onlyDigits($input.val()));
        });
    }

    $(document).on('input', '.frota-plate-mask', function () {
        this.value = formatPlate(this.value);
    });

    $(document).on('input', '.frota-year-mask', function () {
        this.value = onlyDigits(this.value).slice(0, 4);
    });

    $(document).on('input', '.frota-km-mask', function () {
        syncKmInput($(this));
    });

    $(document).on('input', '.frota-number-mask', function () {
        this.value = onlyDigits(this.value);
    });

    function showFipeMessage($target, message) {
        var $help = $target.closest('.form-group').find('.frota-fipe-help');
        if (!$help.length) {
            $help = $('<div class="frota-fipe-help text-off small mt-1"></div>');
            $target.closest('.col-md-9').append($help);
        }
        $help.text(message || '');
    }

    function normalizeResponse(response) {
        return response && response.data && $.isArray(response.data) ? response.data : [];
    }

    function ensureOption($select, value, text, selected) {
        value = String(value || '');
        if (!value) return;
        var exists = false;
        $select.find('option').each(function () {
            if (String($(this).val()) === value) exists = true;
        });
        if (!exists) $select.append(new Option(text || value, value, !!selected, !!selected));
    }

    window.FrotaUI = window.FrotaUI || {};
    window.FrotaUI.prepareMasks = prepareMasks;

    window.FrotaUI.initVehicleFipe = function (options) {
        options = options || {};
        var $brand = $(options.brandSelector || '#make_code');
        var $makeHidden = $(options.makeHiddenSelector || '#make');
        var $model = $(options.modelSelector || '#model');
        var currentMake = String(options.currentMake || $makeHidden.val() || '').trim();
        var currentModel = String(options.currentModel || $model.val() || '').trim();
        var type = options.type || 'carros';

        if (!$brand.length || !$model.length || !options.brandsUrl || !options.modelsUrl) return;

        // RISE 3.9.5 usa uma versão do Select2 que não permite tags em <select>.
        $brand.select2({width: '100%', placeholder: 'Selecione a marca', allowClear: true});
        $model.select2({width: '100%', placeholder: 'Selecione o modelo', allowClear: true});

        function setStoredBrand(name) {
            if (!name) return;
            var value = 'stored:' + name;
            ensureOption($brand, value, name, true);
            $brand.val(value).trigger('change.select2');
            $makeHidden.val(name);
        }

        function loadModels(brandCode, selectedModel) {
            var current = selectedModel || '';
            $model.prop('disabled', true);
            $model.empty().append(new Option('', '', false, false));
            if (current) ensureOption($model, current, current, true);

            if (!brandCode || String(brandCode).indexOf('stored:') === 0) {
                $model.prop('disabled', false).trigger('change.select2');
                if (current) showFipeMessage($model, 'Modelo preservado do cadastro atual.');
                return;
            }

            showFipeMessage($model, 'Carregando modelos...');
            $.ajax({
                url: options.modelsUrl,
                type: 'GET',
                dataType: 'json',
                data: {tipo: type, marca: brandCode}
            }).done(function (response) {
                var rows = normalizeResponse(response);
                rows.forEach(function (item) {
                    var value = String(item.id || item.text || '');
                    var text = String(item.text || item.id || '');
                    if (value && text) ensureOption($model, value, text, false);
                });
                if (current) {
                    ensureOption($model, current, current, true);
                    $model.val(current);
                }
                showFipeMessage($model, rows.length ? '' : 'Nenhum modelo encontrado para esta marca.');
            }).fail(function (xhr) {
                var message = 'Não foi possível carregar os modelos agora.';
                if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                showFipeMessage($model, message);
            }).always(function () {
                $model.prop('disabled', false).trigger('change.select2');
            });
        }

        showFipeMessage($brand, 'Carregando marcas da FIPE...');
        $.ajax({
            url: options.brandsUrl,
            type: 'GET',
            dataType: 'json',
            data: {tipo: type}
        }).done(function (response) {
            var rows = normalizeResponse(response);
            var matchedCode = '';
            $brand.empty().append(new Option('', '', false, false));

            rows.forEach(function (item) {
                var code = String(item.id || '');
                var text = String(item.text || '');
                if (!code || !text) return;
                ensureOption($brand, code, text, false);
                if (currentMake && text.toLowerCase() === currentMake.toLowerCase()) matchedCode = code;
            });

            if (currentMake) {
                if (matchedCode) {
                    $brand.val(matchedCode).trigger('change.select2');
                    $makeHidden.val(currentMake);
                    loadModels(matchedCode, currentModel);
                } else {
                    setStoredBrand(currentMake);
                    loadModels('', currentModel);
                }
            }
            showFipeMessage($brand, rows.length ? '' : 'Nenhuma marca retornada pela FIPE.');
        }).fail(function (xhr) {
            if (currentMake) setStoredBrand(currentMake);
            var message = 'Não foi possível carregar as marcas agora.';
            if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
            showFipeMessage($brand, message);
        });

        $brand.off('change.frotaFipe').on('change.frotaFipe', function () {
            var value = String($(this).val() || '');
            var text = $brand.find('option:selected').text() || '';
            if (value.indexOf('stored:') === 0) text = value.substring(7);
            $makeHidden.val(text);
            loadModels(value, '');
        });
    };

    $(function () {
        prepareMasks(document);
    });

    $(document).on('shown.bs.modal', function (event) {
        prepareMasks(event.target);
    });
})(jQuery);
