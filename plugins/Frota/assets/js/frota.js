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
            if (pos >= expected.length) {
                return;
            }
            var ok = expected[pos] === 'L' ? /[A-Z]/.test(ch) : /[0-9]/.test(ch);
            if (ok) {
                accepted.push(ch);
                pos++;
            }
        });

        var out = accepted.slice(0, 3).join('');
        if (accepted.length > 3) {
            out += '-' + accepted.slice(3).join('');
        }
        return out;
    }

    function prepareKmInput($input) {
        if ($input.data('frota-mask-ready')) {
            return;
        }

        var fieldName = $input.attr('name');
        if (!fieldName) {
            return;
        }

        var raw = rawKmFromStored($input.val());
        var hiddenId = ($input.attr('id') || fieldName) + '_raw';
        var $hidden = $('<input>', {
            type: 'hidden',
            id: hiddenId,
            name: fieldName,
            value: raw
        });

        $input.attr('name', fieldName + '_formatted');
        $input.attr({
            inputmode: 'numeric',
            autocomplete: 'off',
            maxlength: 15
        });
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
        if (hiddenId) {
            $('#' + hiddenId).val(raw);
        }
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
        var cursor = this.selectionStart;
        var oldLength = this.value.length;
        this.value = formatPlate(this.value);
        var diff = this.value.length - oldLength;
        try { this.setSelectionRange(cursor + diff, cursor + diff); } catch (e) {}
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
        if (response && response.data && $.isArray(response.data)) {
            return response.data;
        }
        return [];
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

        if (!$brand.length || !$model.length || !options.brandsUrl || !options.modelsUrl) {
            return;
        }

        $brand.select2({
            width: '100%',
            placeholder: 'Selecione ou digite a marca',
            allowClear: true,
            tags: true
        });

        $model.select2({
            width: '100%',
            placeholder: 'Selecione ou digite o modelo',
            allowClear: true,
            tags: true
        });

        function setManualBrand(name) {
            if (!name) return;
            var value = 'manual:' + name;
            $brand.append(new Option(name, value, true, true));
            $brand.val(value).trigger('change.select2');
            $makeHidden.val(name);
        }

        function loadModels(brandCode, selectedModel) {
            $model.prop('disabled', true);
            showFipeMessage($model, brandCode ? 'Carregando modelos...' : 'Digite o modelo manualmente.');

            var current = selectedModel || $model.val() || '';
            $model.empty().append(new Option('', '', false, false));
            if (current) {
                $model.append(new Option(current, current, true, true));
            }

            if (!brandCode || String(brandCode).indexOf('manual:') === 0) {
                $model.prop('disabled', false).trigger('change.select2');
                showFipeMessage($model, 'Marca sem código FIPE. Você pode digitar o modelo manualmente.');
                return;
            }

            $.ajax({
                url: options.modelsUrl,
                type: 'GET',
                dataType: 'json',
                data: {tipo: type, marca: brandCode}
            }).done(function (response) {
                var rows = normalizeResponse(response);
                rows.forEach(function (item) {
                    var text = item.text || item.id;
                    if (text && !$model.find('option').filter(function(){ return $(this).val() === String(item.id); }).length) {
                        $model.append(new Option(text, item.id, false, false));
                    }
                });
                if (current) {
                    $model.val(current);
                }
                showFipeMessage($model, rows.length ? '' : 'Nenhum modelo retornado. Você pode digitar manualmente.');
            }).fail(function (xhr) {
                var message = 'Não foi possível carregar os modelos. Você pode digitar manualmente.';
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
                $brand.append(new Option(text, code, false, false));
                if (currentMake && text.toLowerCase() === currentMake.toLowerCase()) {
                    matchedCode = code;
                }
            });

            if (currentMake) {
                if (matchedCode) {
                    $brand.val(matchedCode).trigger('change.select2');
                    $makeHidden.val(currentMake);
                    loadModels(matchedCode, currentModel);
                } else {
                    setManualBrand(currentMake);
                    loadModels('', currentModel);
                }
            }
            showFipeMessage($brand, rows.length ? '' : 'Nenhuma marca retornada. Você pode digitar manualmente.');
        }).fail(function (xhr) {
            setManualBrand(currentMake);
            var message = 'Não foi possível carregar as marcas. Você pode digitar manualmente.';
            if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
            showFipeMessage($brand, message);
        });

        $brand.on('change.frotaFipe', function () {
            var value = String($(this).val() || '');
            var text = value.indexOf('manual:') === 0
                ? value.substring(7)
                : ($brand.find('option:selected').text() || '');
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
