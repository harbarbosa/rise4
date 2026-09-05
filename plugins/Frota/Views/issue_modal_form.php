<?php
$existingPhotos = [];
$photoValue = $model_info->photo_url ?? '';
if ($photoValue) {
    $decoded = json_decode($photoValue, true);
    if (is_array($decoded)) {
        $existingPhotos = array_values(array_filter($decoded));
    } else {
        $existingPhotos = [$photoValue];
    }
}
?>
<?php echo form_open(get_uri('frota/ocorrencias/salvar'), ['id' => 'frota-issue-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
        <input type="hidden" name="photo_url" id="photo_url" value="<?php echo esc(json_encode($existingPhotos)); ?>" />

        <div class="form-group"><div class="row"><label for="vehicle_id" class="col-md-3">Veículo</label><div class="col-md-9">
            <?php echo form_dropdown('vehicle_id', $vehicleOptions, [$model_info->vehicle_id ?? ''], "class='select2 validate-hidden' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "' id='vehicle_id'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="title" class="col-md-3"><?php echo app_lang('title'); ?></label><div class="col-md-9">
            <?php echo form_input(['id' => 'title', 'name' => 'title', 'value' => $model_info->title ?? '', 'class' => 'form-control', 'placeholder' => app_lang('title'), 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label><div class="col-md-9">
            <?php echo form_textarea(['id' => 'description', 'name' => 'description', 'value' => $model_info->description ?? '', 'class' => 'form-control', 'placeholder' => app_lang('description'), 'rows' => 5, 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="severity" class="col-md-3">Gravidade</label><div class="col-md-9">
            <?php echo form_dropdown('severity', ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'], [$model_info->severity ?? 'medium'], "class='select2' id='severity'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="odometer" class="col-md-3">KM</label><div class="col-md-9">
            <?php echo form_input(['id' => 'odometer', 'name' => 'odometer', 'value' => $model_info->odometer ?? '', 'class' => 'form-control frota-km-mask', 'placeholder' => 'Informe a quilometragem', 'inputmode' => 'numeric']); ?>
        </div></div></div>

        <div class="form-group">
            <div class="row">
                <label for="issue_photos" class="col-md-3">Fotos</label>
                <div class="col-md-9">
                    <input type="file" id="issue_photos" name="photos[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple />
                    <div class="text-off small mt-1">Você pode adicionar uma ou mais fotos. Formatos: JPG, PNG ou WEBP. Máximo de 10 MB por foto.</div>
                    <div id="issue-photo-preview" class="d-flex flex-wrap gap-2 mt-2">
                        <?php foreach ($existingPhotos as $photo) { ?>
                            <a href="<?php echo esc($photo); ?>" target="_blank" class="d-inline-block">
                                <img src="<?php echo esc($photo); ?>" alt="Foto da ocorrência" style="width:90px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #ddd;" />
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" id="save-issue-button" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
$(document).ready(function(){
    var $form = $("#frota-issue-form");
    var $photos = $("#issue_photos");
    var $preview = $("#issue-photo-preview");
    var existingPhotos = <?php echo json_encode($existingPhotos); ?>;

    if (window.FrotaUI) {
        window.FrotaUI.prepareMasks('#frota-issue-form');
    }
    $("#frota-issue-form .select2").select2();

    $photos.on('change', function () {
        $preview.find('.frota-new-photo-preview').remove();
        Array.prototype.forEach.call(this.files || [], function (file) {
            if (!file.type.match(/^image\//)) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                $('<div class="frota-new-photo-preview d-inline-block"></div>')
                    .append($('<img>', {
                        src: e.target.result,
                        alt: 'Nova foto',
                        css: {width:'90px', height:'90px', objectFit:'cover', borderRadius:'6px', border:'1px solid #ddd'}
                    }))
                    .appendTo($preview);
            };
            reader.readAsDataURL(file);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();

        if ($form.valid && !$form.valid()) {
            return;
        }

        var $button = $('#save-issue-button');
        $button.prop('disabled', true);

        function saveIssue(photoUrls) {
            $('#photo_url').val(JSON.stringify(photoUrls || []));
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (response && response.success) {
                    location.reload();
                } else {
                    appAlert.error((response && response.message) ? response.message : 'Não foi possível salvar a ocorrência.');
                    $button.prop('disabled', false);
                }
            }).fail(function (xhr) {
                var message = 'Não foi possível salvar a ocorrência.';
                if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                appAlert.error(message);
                $button.prop('disabled', false);
            });
        }

        if (!$photos[0].files || !$photos[0].files.length) {
            saveIssue(existingPhotos);
            return;
        }

        var uploadData = new FormData();
        Array.prototype.forEach.call($photos[0].files, function (file) {
            uploadData.append('photos[]', file);
        });

        $form.find('input[type="hidden"]').each(function () {
            var name = $(this).attr('name');
            if (name && name !== 'photo_url' && name !== 'id') {
                uploadData.append(name, $(this).val());
            }
        });

        $.ajax({
            url: <?php echo json_encode(get_uri('frota/ocorrencias/fotos/upload')); ?>,
            type: 'POST',
            data: uploadData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            var uploaded = (response && response.files) ? response.files : [];
            saveIssue(existingPhotos.concat(uploaded));
        }).fail(function (xhr) {
            var message = 'Não foi possível enviar as fotos.';
            if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
            appAlert.error(message);
            $button.prop('disabled', false);
        });
    });
});
</script>
