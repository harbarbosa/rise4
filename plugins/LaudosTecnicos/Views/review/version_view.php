<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Versão <?php echo str_pad($version->version, 2, '0', STR_PAD_LEFT); ?>-<?php echo str_pad($version->revision, 2, '0', STR_PAD_LEFT); ?></h4>
                <small class="text-muted"><?php echo $version->reason; ?></small>
            </div>
            <div>
                <span class="badge bg-<?php echo $version->status === 'published' ? 'success' : 'secondary'; ?> fs-6">
                    <?php echo $version->status; ?>
                </span>
                <span class="badge bg-info fs-6">
                    Hash: <?php echo substr($version->document_hash ?? '', 0, 16); ?>...
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Informações</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="30%">Data de Criação</th><td><?php echo $version->created_at; ?></td></tr>
                        <tr><th>Publicação</th><td><?php echo $version->published_at ?? 'Não publicada'; ?></td></tr>
                        <tr><th>Hash do Documento</th><td><code><?php echo $version->document_hash; ?></code></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5>Conteúdo</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($content['sections'])): ?>
                    <?php foreach ($content['sections'] as $section): ?>
                    <div class="card mb-2">
                        <div class="card-header">
                            <h6 class="mb-0"><?php echo $section['name']; ?></h6>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br($section['value'] ?? '-'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-muted">Conteúdo não disponível</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>