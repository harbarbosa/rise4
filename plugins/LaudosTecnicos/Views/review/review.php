<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><?php echo $laudo->laudo_number; ?></h4>
                <small class="text-muted"><?php echo $laudo->title; ?></small>
            </div>
            <div>
                <span class="badge bg-<?php echo $this->_get_review_color($laudo->review_status); ?> fs-6">
                    Revisão: <?php echo $laudo->review_status; ?>
                </span>
                <span class="badge bg-<?php echo $this->_get_approval_color($laudo->approval_status); ?> fs-6">
                    Aprovação: <?php echo $laudo->approval_status; ?>
                </span>
                <span class="badge bg-info fs-6">
                    Versão <?php echo $laudo->current_version; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Seções do Laudo -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Conteúdo do Laudo</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sections)): ?>
                    <p class="text-muted">Nenhuma seção encontrada</p>
                    <?php else: ?>
                    <?php foreach ($sections as $section): ?>
                    <div class="card mb-3" id="section-<?php echo $section->id; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo $section->name; ?></h6>
                            <div>
                                <button class="btn btn-xs btn-outline-primary" onclick="addComment(<?php echo $laudo->id; ?>, <?php echo $section->id; ?>, null)">
                                    <i data-feather="message-square" class="icon-14"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php 
                            $value = $section->value ?? '-';
                            if ($section->field_type === 'checkbox') {
                                echo $value ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>';
                            } elseif ($section->field_type === 'select' || $section->field_type === 'radio') {
                                echo '<span class="badge bg-info">' . $value . '</span>';
                            } else {
                                echo nl2br($value);
                            }
                            ?>
                        </div>
                        
                        <!-- Comentários desta seção -->
                        <?php 
                        $section_comments = array_filter($comments, function($c) use ($section) { 
                            return $c->section_id == $section->id; 
                        });
                        ?>
                        <?php if (!empty($section_comments)): ?>
                        <div class="card-footer bg-light">
                            <small class="text-muted">Comentários:</small>
                            <?php foreach ($section_comments as $comment): ?>
                            <div class="alert alert-<?php echo $comment->status === 'resolved' ? 'success' : 'warning'; ?> py-2 mb-1">
                                <strong><?php echo $comment->author_name; ?>:</strong> <?php echo $comment->text; ?>
                                <?php if ($comment->status === 'open'): ?>
                                <button class="btn btn-xs btn-success float-end" onclick="resolveComment(<?php echo $comment->id; ?>)">Resolver</button>
                                <?php else: ?>
                                <br><small>Resolvido: <?php echo $comment->response; ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Painel Lateral -->
        <div class="col-md-4">
            <!-- Ações -->
            <div class="card">
                <div class="card-header">
                    <h5>Ações</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="createVersion()">
                            <i data-feather="save" class="icon-16"></i> Criar Versão
                        </button>
                        
                        <?php if ($laudo->review_status !== 'completed'): ?>
                        <button class="btn btn-success" onclick="finishReview()">
                            <i data-feather="check-circle" class="icon-16"></i> Finalizar Revisão
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-primary" onclick="showApprovalModal()">
                            <i data-feather="award" class="icon-16"></i> Aprovar
                        </button>
                        
                        <button class="btn btn-outline-danger" onclick="showRejectModal()">
                            <i data-feather="x-circle" class="icon-16"></i> Rejeitar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pendências -->
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pendências</h5>
                    <span class="badge bg-danger"><?php echo $open_pendencies; ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($pendencies)): ?>
                    <p class="text-muted">Nenhuma pendência</p>
                    <?php else: ?>
                    <?php foreach ($pendencies as $p): ?>
                    <div class="alert alert-<?php echo $p->status === 'resolved' ? 'success' : 'warning'; ?> py-2">
                        <strong><?php echo ucfirst($p->type); ?>:</strong> <?php echo substr($p->description, 0, 50); ?>...
                        <?php if ($p->status === 'pending'): ?>
                        <button class="btn btn-xs btn-success mt-1" onclick="resolvePendency(<?php echo $p->id; ?>)">Resolver</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="addPendency()">
                        <i data-feather="plus" class="icon-14"></i> Adicionar Pendência
                    </button>
                </div>
            </div>

            <!-- Versões -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Versões</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($versions)): ?>
                    <p class="text-muted">Nenhuma versão</p>
                    <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($versions as $v): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="<?php echo get_uri('laudo_review/view_version/' . $v->id); ?>">
                                Rev. <?php echo str_pad($v->version, 2, '0', STR_PAD_LEFT); ?>-<?php echo str_pad($v->revision, 2, '0', STR_PAD_LEFT); ?>
                            </a>
                            <span class="badge bg-<?php echo $v->status === 'published' ? 'success' : 'secondary'; ?>">
                                <?php echo $v->status; ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assinaturas -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Assinaturas</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-sm btn-primary w-100" onclick="showSignatureModal()">
                        <i data-feather="edit-3" class="icon-14"></i> Assinar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modais -->
<!-- Comentário -->
<div class="modal fade" id="comment-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Comentário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="comment_laudo_id" />
                <input type="hidden" id="comment_section_id" />
                <input type="hidden" id="comment_field_name" />
                
                <div class="form-group mb-2">
                    <label>Prioridade</label>
                    <select id="comment_priority" class="form-control">
                        <option value="low">Baixa</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">Alta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Comentário</label>
                    <textarea id="comment_text" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveComment()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Pendência -->
<div class="modal fade" id="pendency-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Pendência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label>Tipo</label>
                    <select id="pendency_type" class="form-control">
                        <option value="correction">Correção</option>
                        <option value="information">Informação</option>
                        <option value="blocking">Bloqueante</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea id="pendency_description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="savePendency()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Aprovação -->
<div class="modal fade" id="approval-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aprovar Laudo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Comentário (opcional)</label>
                    <textarea id="approval_comment" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="approveLaudo()">Confirmar Aprovação</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejeição -->
<div class="modal fade" id="reject-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejeitar Laudo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Motivo</label>
                    <textarea id="reject_comment" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="rejectLaudo()">Confirmar Rejeição</button>
            </div>
        </div>
    </div>
</div>

<!-- Assinatura -->
<div class="modal fade" id="signature-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assinar Laudo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-2">
                            <label>Nome</label>
                            <input type="text" id="signer_name" class="form-control" />
                        </div>
                        <div class="form-group mb-2">
                            <label>Documento (CPF)</label>
                            <input type="text" id="signer_document" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label>Função</label>
                            <select id="signer_role" class="form-control">
                                <option value="technical_responsible">Responsável Técnico</option>
                                <option value="inspector">Inspetor</option>
                                <option value="reviewer">Revisor</option>
                                <option value="approver">Aprovador</option>
                                <option value="client">Cliente</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Assinatura</label>
                            <canvas id="signature-canvas" class="border w-100" style="height: 150px;"></canvas>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="clearSignature()">Limpar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveSignature()">Assinar</button>
            </div>
        </div>
    </div>
</div>

<script>
var laudoId = <?php echo $laudo->id; ?>;
var signatureCanvas, signatureCtx;

function addComment(laudo_id, section_id, field_name) {
    $('#comment_laudo_id').val(laudo_id);
    $('#comment_section_id').val(section_id || '');
    $('#comment_field_name').val(field_name || '');
    $('#comment-modal').modal('show');
}

function saveComment() {
    $.ajax({
        url: '<?php echo get_uri("laudo_review/add_comment"); ?>',
        type: 'POST',
        data: {
            laudo_id: $('#comment_laudo_id').val(),
            section_id: $('#comment_section_id').val(),
            field_name: $('#comment_field_name').val(),
            text: $('#comment_text').val(),
            priority: $('#comment_priority').val()
        },
        success: function() {
            $('#comment-modal').modal('hide');
            location.reload();
        }
    });
}

function resolveComment(id) {
    var response = prompt('Resposta:');
    if (response !== null) {
        $.ajax({
            url: '<?php echo get_uri("laudo_review/resolve_comment/"); ?>' + id,
            type: 'POST',
            data: { response: response },
            success: function() {
                location.reload();
            }
        });
    }
}

function addPendency() {
    $('#pendency-modal').modal('show');
}

function savePendency() {
    $.ajax({
        url: '<?php echo get_uri("laudo_review/add_pendency"); ?>',
        type: 'POST',
        data: {
            laudo_id: laudoId,
            type: $('#pendency_type').val(),
            description: $('#pendency_description').val()
        },
        success: function() {
            $('#pendency-modal').modal('hide');
            location.reload();
        }
    });
}

function resolvePendency(id) {
    if (confirm('Marcar como resolvido?')) {
        $.ajax({
            url: '<?php echo get_uri("laudo_review/resolve_pendency/"); ?>' + id,
            type: 'POST',
            success: function() {
                location.reload();
            }
        });
    }
}

function finishReview() {
    if (confirm('Finalizar revisão? Pendências e comentários devem estar resolvidos.')) {
        $.ajax({
            url: '<?php echo get_uri("laudo_review/finish_review/"); ?>' + laudoId,
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    appAlert.success('Revisão finalizada');
                    location.reload();
                } else {
                    appAlert.error(response.message);
                }
            }
        });
    }
}

function createVersion() {
    var reason = prompt('Motivo da nova versão:');
    if (reason) {
        $.ajax({
            url: '<?php echo get_uri("laudo_review/create_version/"); ?>' + laudoId,
            type: 'POST',
            data: { reason: reason },
            success: function(response) {
                if (response.success) {
                    appAlert.success('Versão criada');
                    location.reload();
                }
            }
        });
    }
}

function showApprovalModal() {
    $('#approval-modal').modal('show');
}

function approveLaudo() {
    $.ajax({
        url: '<?php echo get_uri("laudo_review/approve/"); ?>' + laudoId,
        type: 'POST',
        data: { comment: $('#approval_comment').val() },
        success: function(response) {
            if (response.success) {
                appAlert.success('Laudo aprovado');
                $('#approval-modal').modal('hide');
                location.reload();
            } else {
                appAlert.error(response.message);
            }
        }
    });
}

function showRejectModal() {
    $('#reject-modal').modal('show');
}

function rejectLaudo() {
    $.ajax({
        url: '<?php echo get_uri("laudo_review/reject_approval/"); ?>' + laudoId,
        type: 'POST',
        data: { comment: $('#reject_comment').val() },
        success: function() {
            appAlert.success('Laudo rejeitado');
            $('#reject-modal').modal('hide');
            location.reload();
        }
    });
}

function showSignatureModal() {
    $('#signature-modal').modal('show');
    initSignatureCanvas();
}

function initSignatureCanvas() {
    signatureCanvas = document.getElementById('signature-canvas');
    signatureCtx = signatureCanvas.getContext('2d');
    signatureCtx.fillStyle = '#fff';
    signatureCtx.fillRect(0, 0, signatureCanvas.width, signatureCanvas.height);
    
    signatureCanvas.addEventListener('mousedown', startDraw);
    signatureCanvas.addEventListener('mousemove', draw);
    signatureCanvas.addEventListener('mouseup', stopDraw);
    signatureCanvas.addEventListener('touchstart', startDrawTouch);
    signatureCanvas.addEventListener('touchmove', drawTouch);
    signatureCanvas.addEventListener('touchend', stopDraw);
}

var isDrawing = false;
var lastX, lastY;

function startDraw(e) {
    isDrawing = true;
    lastX = e.offsetX;
    lastY = e.offsetY;
}

function draw(e) {
    if (!isDrawing) return;
    signatureCtx.beginPath();
    signatureCtx.moveTo(lastX, lastY);
    signatureCtx.lineTo(e.offsetX, e.offsetY);
    signatureCtx.stroke();
    lastX = e.offsetX;
    lastY = e.offsetY;
}

function stopDraw() {
    isDrawing = false;
}

function startDrawTouch(e) {
    e.preventDefault();
    var touch = e.touches[0];
    var rect = signatureCanvas.getBoundingClientRect();
    isDrawing = true;
    lastX = touch.clientX - rect.left;
    lastY = touch.clientY - rect.top;
}

function drawTouch(e) {
    e.preventDefault();
    if (!isDrawing) return;
    var touch = e.touches[0];
    var rect = signatureCanvas.getBoundingClientRect();
    signatureCtx.beginPath();
    signatureCtx.moveTo(lastX, lastY);
    signatureCtx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
    signatureCtx.stroke();
    lastX = touch.clientX - rect.left;
    lastY = touch.clientY - rect.top;
}

function clearSignature() {
    signatureCtx.fillStyle = '#fff';
    signatureCtx.fillRect(0, 0, signatureCanvas.width, signatureCanvas.height);
}

function saveSignature() {
    var signatureData = signatureCanvas.toDataURL('image/png');
    
    $.ajax({
        url: '<?php echo get_uri("laudo_review/sign/"); ?>' + laudoId,
        type: 'POST',
        data: {
            signer_name: $('#signer_name').val(),
            signer_document: $('#signer_document').val(),
            signer_role: $('#signer_role').val(),
            signature_data: signatureData
        },
        success: function() {
            appAlert.success('Assinatura registrada');
            $('#signature-modal').modal('hide');
        }
    });
}

function get_review_color(status) {
    var colors = { 'not_started': 'secondary', 'in_progress': 'info', 'completed': 'success' };
    return colors[status] || 'secondary';
}

function get_approval_color(status) {
    var colors = { 'pending': 'warning', 'approved': 'success', 'rejected': 'danger' };
    return colors[status] || 'secondary';
}
</script>