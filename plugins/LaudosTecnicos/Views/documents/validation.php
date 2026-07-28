<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Laudo - <?php echo $laudo->laudo_number ?? ''; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .validation-card { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .valid-icon { font-size: 60px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="validation-card p-5">
            <div class="text-center">
                <?php if ($is_valid): ?>
                <div class="valid-icon text-success">✓</div>
                <h2 class="text-success mt-3">Documento Autêntico</h2>
                <p class="text-muted">Este documento foi verificado e é válido</p>
                <?php else: ?>
                <div class="valid-icon text-danger">✗</div>
                <h2 class="text-danger mt-3">Documento Inválido</h2>
                <p class="text-muted">Este documento não pôde ser verificado</p>
                <?php endif; ?>
            </div>
            
            <hr>
            
            <table class="table table-borderless">
                <tr>
                    <td><strong>Número:</strong></td>
                    <td><?php echo $laudo->laudo_number ?? '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Título:</strong></td>
                    <td><?php echo $laudo->title ?? '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Cliente:</strong></td>
                    <td><?php echo $laudo->company_name ?? '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Versão:</strong></td>
                    <td><?php echo $version ? $version->version . '-' . $version->revision : '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Data de Emissão:</strong></td>
                    <td><?php echo $version && $version->published_at ? date('d/m/Y H:i', strtotime($version->published_at)) : '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <?php if ($is_valid): ?>
                        <span class="badge bg-success">Publicado</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Rascunho</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($version && $version->document_hash): ?>
                <tr>
                    <td><strong>Hash:</strong></td>
                    <td><code class="small"><?php echo $version->document_hash; ?></code></td>
                </tr>
                <?php endif; ?>
            </table>
            
            <hr>
            
            <p class="text-muted small text-center">
                Documento emitido pelo sistema RISE CRM - Laudos Técnicos<br>
                Para verificar a autenticidade, compare o hash deste documento com o original.
            </p>
        </div>
    </div>
</body>
</html>