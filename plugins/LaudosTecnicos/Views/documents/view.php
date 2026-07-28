<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><?php echo $laudo->laudo_number; ?></h4>
                <small class="text-muted"><?php echo $laudo->title; ?></small>
            </div>
            <div class="btn-group">
                <a href="<?php echo get_uri('laudo_documents/config/' . $laudo->id); ?>" class="btn btn-default">
                    <i data-feather="settings" class="icon-16"></i> Configurar
                </a>
                <a href="<?php echo get_uri('laudo_documents/share/' . $laudo->id); ?>" class="btn btn-default">
                    <i data-feather="share-2" class="icon-16"></i> Compartilhar
                </a>
                <button class="btn btn-primary" onclick="generatePDF()">
                    <i data-feather="file-text" class="icon-16"></i> Gerar PDF
                </button>
                <?php if ($version && $version->pdf_file): ?>
                <a href="<?php echo get_uri('laudo_documents/download_pdf/' . $laudo->id); ?>" class="btn btn-success">
                    <i data-feather="download" class="icon-16"></i> Baixar PDF
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Pré-visualização</h5>
                </div>
                <div class="card-body">
                    <iframe src="<?php echo get_uri('laudo_documents/render_html/' . $laudo->id); ?>" 
                            style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generatePDF() {
    appAlert.confirm('Gerar PDF deste laudo?', function() {
        $.ajax({
            url: '<?php echo get_uri("laudo_documents/generate_pdf/" . $laudo->id); ?>',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    appAlert.success('PDF gerado com sucesso');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    appAlert.error(response.message);
                }
            }
        });
    });
}
</script>