(function ($) {
    'use strict';

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function formatThousands(raw) {
        raw = onlyDigits(raw).replace(/^0+(?=\d)/, '');
        return raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    }

    function formatPlate(value) {
        var chars = String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').split('');
        var expected = ['L', 'L', 'L', 'N', 'L', 'N', 'N'];
        var accepted = [], pos = 0;
        chars.forEach(function (ch) {
            if (pos >= expected.length) return;
            var ok = expected[pos] === 'L' ? /[A-Z]/.test(ch) : /[0-9]/.test(ch);
            if (ok) { accepted.push(ch); pos++; }
        });
        var out = accepted.slice(0, 3).join('');
        if (accepted.length > 3) out += '-' + accepted.slice(3).join('');
        return out;
    }

    function storedToDecimal(value, decimals) {
        var text = String(value == null ? '' : value).trim();
        if (!text) return '';
        if (/^-?\d+(\.\d+)?$/.test(text)) {
            var n = parseFloat(text);
            return isNaN(n) ? '' : n.toFixed(decimals).replace('.', ',');
        }
        return text;
    }

    function decimalDisplayToRaw(value, decimals) {
        var text = String(value || '').replace(/[^0-9,]/g, '');
        var parts = text.split(',');
        var integer = onlyDigits(parts[0] || '0').replace(/^0+(?=\d)/, '') || '0';
        var fraction = onlyDigits(parts.slice(1).join('')).slice(0, decimals);
        return fraction ? integer + '.' + fraction : integer;
    }

    function formatDecimalInput(value, decimals, currency) {
        var text = String(value || '').replace(/[^0-9,]/g, '');
        var parts = text.split(',');
        var integer = onlyDigits(parts[0] || '').replace(/^0+(?=\d)/, '');
        integer = integer ? integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        var fraction = onlyDigits(parts.slice(1).join('')).slice(0, decimals);
        var out = integer;
        if (text.indexOf(',') >= 0) out += ',' + fraction;
        if (currency && out) out = 'R$ ' + out;
        return out;
    }

    function prepareRawMask($input, type) {
        if ($input.data('frota-mask-ready')) return;
        var fieldName = $input.attr('name');
        if (!fieldName) return;
        var hiddenId = ($input.attr('id') || fieldName) + '_raw';
        var decimals = parseInt($input.data('decimals'), 10);
        if (isNaN(decimals)) decimals = type === 'km' ? 0 : 2;
        var raw;
        if (type === 'km') {
            var stored = String($input.val() == null ? '' : $input.val()).trim();
            raw = /^\d+\.\d+$/.test(stored) ? stored.split('.')[0] : onlyDigits(stored);
            $input.val(formatThousands(raw));
        } else {
            var display = storedToDecimal($input.val(), decimals);
            raw = display ? decimalDisplayToRaw(display, decimals) : '';
            $input.val(formatDecimalInput(display, decimals, type === 'currency'));
        }
        $input.attr('name', fieldName + '_formatted').attr({inputmode: 'decimal', autocomplete: 'off'});
        $input.after($('<input>', {type: 'hidden', id: hiddenId, name: fieldName, value: raw}));
        $input.data('frota-mask-ready', true).data('frota-hidden-id', hiddenId).data('frota-type', type).data('frota-decimals', decimals);
    }

    function syncRawMask($input) {
        var type = $input.data('frota-type');
        var decimals = parseInt($input.data('frota-decimals'), 10) || 0;
        var raw;
        if (type === 'km') {
            raw = onlyDigits($input.val());
            $input.val(formatThousands(raw));
        } else {
            var current = String($input.val() || '').replace(/^R\$\s*/, '');
            raw = decimalDisplayToRaw(current, decimals);
            $input.val(formatDecimalInput(current, decimals, type === 'currency'));
        }
        $('#' + $input.data('frota-hidden-id')).val(raw);
        $input.trigger('frota:masked');
    }

    function prepareMasks(context) {
        var $context = context ? $(context) : $(document);
        $context.find('.frota-plate-mask').each(function () {
            $(this).attr({maxlength: 8, autocomplete: 'off'}).val(formatPlate($(this).val()));
        });
        $context.find('.frota-year-mask').each(function () {
            $(this).attr({maxlength: 4, inputmode: 'numeric', autocomplete: 'off'}).val(onlyDigits($(this).val()).slice(0, 4));
        });
        $context.find('.frota-km-mask').each(function () { prepareRawMask($(this), 'km'); });
        $context.find('.frota-decimal-mask').each(function () { prepareRawMask($(this), 'decimal'); });
        $context.find('.frota-currency-mask').each(function () { prepareRawMask($(this), 'currency'); });
    }

    $(document).on('input', '.frota-plate-mask', function () { this.value = formatPlate(this.value); });
    $(document).on('input', '.frota-year-mask', function () { this.value = onlyDigits(this.value).slice(0, 4); });
    $(document).on('input', '.frota-km-mask,.frota-decimal-mask,.frota-currency-mask', function () { syncRawMask($(this)); });

    window.FrotaUI = window.FrotaUI || {};
    window.FrotaUI.prepareMasks = prepareMasks;
    window.FrotaUI.getRawValue = function (selector) {
        var $input = $(selector);
        var id = $input.data('frota-hidden-id');
        return parseFloat(id ? $('#' + id).val() : $input.val()) || 0;
    };
    window.FrotaUI.setCurrencyValue = function (selector, value) {
        var $input = $(selector);
        prepareRawMask($input, 'currency');
        var decimals = parseInt($input.data('frota-decimals'), 10) || 2;
        var raw = Number(value || 0).toFixed(decimals);
        $('#' + $input.data('frota-hidden-id')).val(raw);
        $input.val(formatDecimalInput(raw.replace('.', ','), decimals, true));
    };
    window.FrotaUI.initFuelingCalculation = function (formSelector) {
        var $form = $(formSelector);
        function calculate() {
            var liters = window.FrotaUI.getRawValue($form.find('#liters'));
            var price = window.FrotaUI.getRawValue($form.find('#unit_price'));
            window.FrotaUI.setCurrencyValue($form.find('#total_amount'), liters * price);
        }
        $form.on('input frota:masked', '#liters,#unit_price', calculate);
        if (!window.FrotaUI.getRawValue($form.find('#total_amount'))) calculate();
    };

    function showFipeMessage($target, message) {
        var $help = $target.closest('.form-group').find('.frota-fipe-help');
        if (!$help.length) {
            $help = $('<div class="frota-fipe-help text-off small mt-1"></div>');
            $target.closest('.col-md-9').append($help);
        }
        $help.text(message || '');
    }

    function normalizeResponse(response) { return response && $.isArray(response.data) ? response.data : []; }

    window.FrotaUI.initVehicleFipe = function (options) {
        options = options || {};
        var $brand = $(options.brandSelector || '#make_code');
        var $makeHidden = $(options.makeHiddenSelector || '#make');
        var $model = $(options.modelSelector || '#model');
        var currentMake = String(options.currentMake || $makeHidden.val() || '').trim();
        var currentModel = String(options.currentModel || $model.val() || '').trim();
        var type = options.type || 'carros';
        if (!$brand.length || !$model.length || !options.brandsUrl || !options.modelsUrl) return;

        $brand.select2({width:'100%', placeholder:'Selecione ou digite a marca', allowClear:true, tags:true});
        $model.select2({width:'100%', placeholder:'Selecione ou digite o modelo', allowClear:true, tags:true});

        function setManualBrand(name) {
            if (!name) return;
            var value = 'manual:' + name;
            $brand.append(new Option(name, value, true, true)).val(value).trigger('change.select2');
            $makeHidden.val(name);
        }
        function loadModels(brandCode, selectedModel) {
            var current = selectedModel || $model.val() || '';
            $model.prop('disabled', true).empty().append(new Option('', '', false, false));
            if (current) $model.append(new Option(current, current, true, true));
            if (!brandCode || String(brandCode).indexOf('manual:') === 0) {
                $model.prop('disabled', false).trigger('change.select2'); return;
            }
            $.getJSON(options.modelsUrl, {tipo:type, marca:brandCode}).done(function (response) {
                normalizeResponse(response).forEach(function (item) {
                    var text = item.text || item.id;
                    if (text) $model.append(new Option(text, item.id, false, false));
                });
                if (current) $model.val(current);
            }).fail(function(){ showFipeMessage($model, 'Não foi possível carregar os modelos. Você pode digitar manualmente.'); })
              .always(function(){ $model.prop('disabled', false).trigger('change.select2'); });
        }
        $.getJSON(options.brandsUrl, {tipo:type}).done(function (response) {
            var rows = normalizeResponse(response), matched = '';
            $brand.empty().append(new Option('', '', false, false));
            rows.forEach(function (item) {
                $brand.append(new Option(item.text, item.id, false, false));
                if (currentMake && String(item.text).toLowerCase() === currentMake.toLowerCase()) matched = String(item.id);
            });
            if (currentMake) {
                if (matched) { $brand.val(matched).trigger('change.select2'); $makeHidden.val(currentMake); loadModels(matched, currentModel); }
                else { setManualBrand(currentMake); loadModels('', currentModel); }
            }
        }).fail(function(){ setManualBrand(currentMake); showFipeMessage($brand, 'Não foi possível carregar as marcas. Você pode digitar manualmente.'); });
        $brand.on('change.frotaFipe', function () {
            var value = String($(this).val() || '');
            var text = value.indexOf('manual:') === 0 ? value.substring(7) : ($brand.find('option:selected').text() || '');
            $makeHidden.val(text); loadModels(value, '');
        });
    };

    $(function () { prepareMasks(document); });
    $(document).on('shown.bs.modal', function (event) { prepareMasks(event.target); });
})(jQuery);
