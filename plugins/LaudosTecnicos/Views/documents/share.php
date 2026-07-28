<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Compartilhar: <?php echo $laudo->laudo_number; ?></h4>
                <small class="text-muted"><?php echo $laudo->title; ?></small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5>Novo Compartilhamento</h5>
                </div>
                <div class="card-body">
                    <form id="share-form">
                        <input type="hidden" name="laudo_id" value="<?php echo $laudo->id; ?>" />
                        
                        <div class="form-group mb-2">
                            <label>Nome do Visitante</label>
                            <input type="text" name="visitor_name" class="form-control" placeholder="Nome de quem receberá" />
                        </div>
                        
                        <div class="form-group mb-2">
                            <label>E-mail do Visitante</label>
                            <input type="email" name="visitor_email" class="form-control" placeholder="E-mail para notificação" />
                        </div>
                        
                        <div class="form-group mb-2">
                            <label>Senha (opcional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Proteger com senha" />
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Expira em</label>
                                    <input type="date" name="expires_at" class="form-control" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Acessos máx.</label>
                                    <input type="number" name="max_accesses" class="form-control" placeholder="Sem limite" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="allow_download" class="form-check-input" id="allow_download" checked />
                            <label class="form-check-label" for="allow_download">Permitir download do PDF</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-feather="link" class="icon-16"></i> Criar Link
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5>Links Ativos</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($shares)): ?>
                    <p class="text-muted">Nenhum compartilhamento ativo</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Visitante</th>
                                <th>Criado em</th>
                                <th>Expira</th>
                                <th>Acessos</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shares as $share): ?>
                            <tr>
                                <td>
                                    <?php echo $share->visitor_name ?: 'Anônimo'; ?><br>
                                    <small class="text-muted"><?php echo $share->visitor_email ?: ''; ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($share->created_at)); ?></td>
                                <td><?php echo $share->expires_at ? date('d/m/Y', strtotime($share->expires_at)) : '-'; ?></td>
                                <td><?php echo $share->current_accesses; ?><?php echo $share->max_accesses ? '/' . $share->max_accesses : ''; ?></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary" onclick="copyLink('<?php echo base_url('laudo_documents/public_view/' . $share->token); ?>')">
                                        <i data-feather="copy" class="icon-14"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger" onclick="revokeShare(<?php echo $share->id; ?>)">
                                        <i data-feather="x" class="icon-14"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#share-form').submit(function(e) {
    e.preventDefault();
    var form = $(this);
    
    $.ajax({
        url: '<?php echo get_uri("laudo_documents/create_share"); ?>',
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                appAlert.success('Link criado: ' + response.share_url);
                copyLink(response.share_url);
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                appAlert.error(response.message);
            }
        }
    });
});

function copyLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        appAlert.success('Link copiado!');
    });
}

function revokeShare(id) {
    if (confirm('Revogar este link?')) {
        $.ajax({
            url: '<?php echo get_uri("laudo_documents/revoke_share/"); ?>' + id,
            type: 'POST',
            success: function() {
                location.reload();
            }
        });
    }
}
</script>