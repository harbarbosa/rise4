<?php
$inspection = $inspection;
?>

<div id="page-content" class="page-wrapper clearfix">
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><?php echo $inspection->code; ?></h4>
                <small class="text-muted"><?php echo $inspection->laudo_title ?? $inspection->laudo_number; ?></small>
            </div>
            <div>
                <span class="badge bg-<?php echo $this->_get_status_color($inspection->status); ?> fs-6">
                    <?php echo $inspection->status; ?>
                </span>
                <?php echo modal_anchor(get_uri("laudo_inspections/form/" . $inspection->id), "<i data-feather='edit-2' class='icon-16'></i>", array("class" => "btn btn-default btn-sm", "title" => "Editar")); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informações Principais -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Informações</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="40%">Data</th><td><?php echo $inspection->scheduled_date; ?></td></tr>
                        <tr><th>Horário</th><td><?php echo $inspection->scheduled_time ?? '-'; ?></td></tr>
                        <tr><th>Duração</th><td><?php echo ($inspection->duration_minutes ?? 120) . ' min'; ?></td></tr>
                        <tr><th>Tipo</th><td><?php echo $inspection->inspection_type ?? '-'; ?></td></tr>
                        <tr><th>Local</th><td><?php echo $inspection->location ?? '-'; ?></td></tr>
                        <tr><th>Endereço</th><td><?php echo $inspection->address ?? '-'; ?></td></tr>
                        <tr><th>Responsável</th><td><?php echo $inspection->responsible_name ?? '-'; ?></td></tr>
                        <tr><th>Veículo</th><td><?php echo $inspection->vehicle ?? '-'; ?></td></tr>
                    </table>
                    
                    <?php if ($inspection->observations): ?>
                    <div class="alert alert-info mt-2">
                        <strong>Observações:</strong><br>
                        <?php echo nl2br($inspection->observations); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ações / Check-in -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Ações</h5>
                </div>
                <div class="card-body">
                    <?php if ($inspection->status === 'scheduled' || $inspection->status === 'confirmed'): ?>
                    <button type="button" class="btn btn-primary btn-block mb-2" onclick="checkin()">
                        <i data-feather="map-pin" class="icon-16"></i> Check-in
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($inspection->status === 'iniciated'): ?>
                    <button type="button" class="btn btn-warning btn-block mb-2" onclick="pause()">
                        <i data-feather="pause" class="icon-16"></i> Pausar
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($inspection->status === 'paused'): ?>
                    <button type="button" class="btn btn-success btn-block mb-2" onclick="resume()">
                        <i data-feather="play" class="icon-16"></i> Retomar
                    </button>
                    <?php endif; ?>
                    
                    <?php if (in_array($inspection->status, ['iniciated', 'paused'])): ?>
                    <button type="button" class="btn btn-success btn-block mb-2" onclick="checkout()">
                        <i data-feather="check-circle" class="icon-16"></i> Finalizar
                    </button>
                    <button type="button" class="btn btn-danger btn-block" onclick="markUnproductive()">
                        <i data-feather="alert-triangle" class="icon-16"></i> Improdutiva
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!$inspection->checkin_at): ?>
                    <div class="alert alert-warning mt-3">
                        <i data-feather="info" class="icon-16"></i>
                        Realize o check-in para iniciar a inspeção.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mt-3">
                        <strong>Check-in:</strong> <?php echo $inspection->checkin_at; ?><br>
                        <strong>Local:</strong> <?php echo $inspection->checkin_lat ? $inspection->checkin_lat . ', ' . $inspection->checkin_lng : 'Sem GPS'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Fotografias -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Fotografias (<?php echo count($photos); ?>)</h5>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('photo-input').click()">
                <i data-feather="camera" class="icon-16"></i> Adicionar Foto
            </button>
            <input type="file" id="photo-input" accept="image/*" style="display: none;" multiple onchange="uploadPhotos()">
        </div>
        <div class="card-body">
            <?php if (empty($photos)): ?>
            <div class="text-center text-muted py-4">
                <i data-feather="image" class="icon-32"></i>
                <p>Nenhuma fotografia</p>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($photos as $photo): ?>
                <div class="col-md-3 mb-2">
                    <div class="card">
                        <img src="<?php echo base_url($photo->original_file); ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                        <div class="card-body p-2">
                            <small><?php echo $photo->caption ?? $photo->taken_at; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Checklists -->
    <div class="card mt-3">
        <div class="card-header">
            <h5>Checklists</h5>
        </div>
        <div class="card-body">
            <?php if (empty($answers)): ?>
            <div class="text-center text-muted py-4">
                <i data-feather="check-square" class="icon-32"></i>
                <p>Nenhum checklist回答</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Questão</th>
                            <th>Resposta</th>
                            <th>Severidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($answers as $answer): ?>
                        <tr>
                            <td><?php echo $answer->group_name ?? '-'; ?></td>
                            <td><?php echo $answer->question; ?></td>
                            <td>
                                <?php if ($answer->response === 'Conforme'): ?>
                                <span class="badge bg-success">Conforme</span>
                                <?php elseif ($answer->response === 'Não conforme'): ?>
                                <span class="badge bg-danger">Não conforme</span>
                                <?php elseif ($answer->response === 'N/A'): ?>
                                <span class="badge bg-secondary">N/A</span>
                                <?php else: ?>
                                <?php echo $answer->response; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($answer->severity === 'critical'): ?>
                                <span class="badge bg-danger">Crítica</span>
                                <?php elseif ($answer->severity === 'high'): ?>
                                <span class="badge bg-warning">Alta</span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($answer->severity); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function get_status_color(status) {
    var colors = {
        'planned': 'secondary',
        'scheduled': 'info',
        'confirmed': 'info',
        'on_route': 'warning',
        'iniciated': 'primary',
        'paused': 'warning',
        'completed': 'success',
        'unproductive': 'danger',
        'reagendada': 'warning',
        'canceled': 'dark'
    };
    return colors[status] || 'secondary';
}

function checkin() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            $.ajax({
                url: '<?php echo get_uri("laudo_inspections/checkin/" . $inspection->id); ?>',
                type: 'POST',
                data: {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: pos.coords.accuracy
                },
                success: function() {
                    location.reload();
                }
            });
        }, function(err) {
            alert('Erro ao obter localização: ' + err.message);
        });
    } else {
        alert('Geolocalização não suportada');
    }
}

function pause() {
    $.ajax({
        url: '<?php echo get_uri("laudo_inspections/update_status/" . $inspection->id); ?>',
        type: 'POST',
        data: { status: 'paused' },
        success: function() { location.reload(); }
    });
}

function resume() {
    $.ajax({
        url: '<?php echo get_uri("laudo_inspections/update_status/" . $inspection->id); ?>',
        type: 'POST',
        data: { status: 'resumed' },
        success: function() { location.reload(); }
    });
}

function checkout() {
    if (confirm('Finalizar inspeção?')) {
        $.ajax({
            url: '<?php echo get_uri("laudo_inspections/checkout/" . $inspection->id); ?>',
            type: 'POST',
            success: function() { location.reload(); }
        });
    }
}

function markUnproductive() {
    alert('Funcionalidade em desenvolvimento');
}

function uploadPhotos() {
    var input = document.getElementById('photo-input');
    var files = input.files;
    
    for (var i = 0; i < files.length; i++) {
        var formData = new FormData();
        formData.append('photo', files[i]);
        
        $.ajax({
            url: '<?php echo get_uri("laudo_inspections/upload_photo/" . $inspection->laudo_id . "/" . $inspection->id); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                location.reload();
            }
        });
    }
}
</script>