<?php
$vehicleOptions[''] = '- Selecione -';
$existingPhotos = [];
if (!empty($model_info->photo_url)) {
    $decoded = json_decode($model_info->photo_url, true);
    if (is_array($decoded)) {
        $existingPhotos = $decoded;
    } else {
        $existingPhotos = [(string)$model_info->photo_url];
    }
}
echo form_open(get_uri('frota/ocorrencias/salvar'), ['id' => 'frota-issue-form', 'class' => 'general-form', 'role' => 'form']);
?>
<div class="modal-body clearfix"><div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />

    <div class="form-group"><div class="row"><label for="vehicle_id" class="col-md-3">Veículo</label><div class="col-md-9">
        <?php echo form_dropdown('vehicle_id', $vehicleOptions, [$model_info->vehicle_id ?? ''], "class='select2 validate-hidden' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "' id='vehicle_id'"); ?>
    </div></div></div>

    <div class="form-group"><div class="row"><label for="title" class="col-md-3"><?php echo app_lang('title'); ?></label><div class="col-md-9">
        <?php echo form_input(['id'=>'title','name'=>'title','value'=>$model_info->title ?? '','class'=>'form-control','placeholder'=>app_lang('title'),'data-rule-required'=>true,'data-msg-required'=>app_lang('field_required')]); ?>
    </div></div></div>

    <div class="form-group"><div class="row"><label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label><div class="col-md-9">
        <?php echo form_textarea(['id'=>'description','name'=>'description','value'=>$model_info->description ?? '','class'=>'form-control','placeholder'=>app_lang('description'),'rows'=>5,'data-rule-required'=>true,'data-msg-required'=>app_lang('field_required')]); ?>
    </div></div></div>

    <div class="form-group"><div class="row"><label for="severity" class="col-md-3">Gravidade</label><div class="col-md-9">
        <?php echo form_dropdown('severity', ['low'=>'Baixa','medium'=>'Média','high'=>'Alta','critical'=>'Crítica'], [$model_info->severity ?? 'medium'], "class='select2' id='severity'"); ?>
    </div></div></div>

    <div class="form-group"><div class="row"><label for="odometer" class="col-md-3">KM</label><div class="col-md-9">
        <?php echo form_input(['id'=>'odometer','name'=>'odometer','value'=>$model_info->odometer ?? '','class'=>'form-control frota-km-mask','placeholder'=>'111.111.111','inputmode'=>'numeric']); ?>
    </div></div></div>

    <div class="form-group"><div class="row"><label for="issue_photos" class="col-md-3">Fotos</label><div class="col-md-9">
        <input type="file" id="issue_photos" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
        <div class="text-off small mt-1">Você pode selecionar uma ou mais fotos. Formatos: JPG, PNG ou WEBP.</div>
        <div id="issue-photo-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
        <?php if ($existingPhotos) { ?>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach ($existingPhotos as $photo) { if (!$photo) continue; ?>
                    <a href="<?php echo base_url(ltrim($photo, '/')); ?>" target="_blank" title="Abrir foto">
                        <img src="<?php echo base_url(ltrim($photo, '/')); ?>" alt="Foto da ocorrência" style="width:80px;height:80px;object-fit:cover;border-radius:4px;">
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div></div></div>
</div></div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
$(document).ready(function(){
    if(window.FrotaUI){window.FrotaUI.prepareMasks('#frota-issue-form');}

    $('#issue_photos').on('change', function(){
        var preview = $('#issue-photo-preview').empty();
        Array.from(this.files || []).forEach(function(file){
            if(!file.type.match(/^image\//)) return;
            var img = $('<img>').css({width:'72px',height:'72px',objectFit:'cover',borderRadius:'4px'});
            img.attr('src', URL.createObjectURL(file));
            preview.append(img);
        });
    });

    $("#frota-issue-form").appForm({
        onSuccess:function(result){
            var input = document.getElementById('issue_photos');
            var files = input && input.files ? input.files : [];
            if(!files.length || !result.id){ location.reload(); return; }
            var data = new FormData();
            data.append('issue_id', result.id);
            Array.from(files).forEach(function(file){ data.append('photos[]', file); });
            $.ajax({
                url: <?php echo json_encode(get_uri('frota/ocorrencias/upload_fotos')); ?>,
                type: 'POST', data: data, processData: false, contentType: false, dataType: 'json'
            }).always(function(){ location.reload(); });
        }
    });
    $("#frota-issue-form .select2").select2();
});
</script>
