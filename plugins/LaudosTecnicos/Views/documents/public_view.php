<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $laudo->laudo_number; ?> - Laudo Técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .doc-container { max-width: 900px; margin: 20px auto; background: white; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .doc-header { border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .doc-section { margin: 30px 0; page-break-inside: avoid; }
        .doc-section h3 { color: #007bff; border-bottom: 1px solid #007bff; padding-bottom: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="doc-container">
        <div class="doc-header">
            <h1>Laudo Técnico</h1>
            <h3><?php echo $laudo->title; ?></h3>
            <p><strong>Nº:</strong> <?php echo $laudo->laudo_number; ?></p>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Cliente:</strong> <?php echo $laudo->company_name; ?></p>
                <p><strong>Local:</strong> <?php echo $laudo->location; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($laudo->created_at)); ?></p>
                <p><strong>Status:</strong> <span class="badge bg-success"><?php echo $laudo->status; ?></span></p>
            </div>
        </div>
        
        <?php 
        // Carregar seções
        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo->id);
        
        foreach ($sections as $section): 
        ?>
        <div class="doc-section">
            <h3><?php echo $section->name; ?></h3>
            <div><?php echo nl2br($section->value ?? '-'); ?></div>
        </div>
        <?php endforeach; ?>
        
        <?php if ($allow_download): ?>
        <div class="text-center mt-5">
            <a href="<?php echo get_uri('laudo_documents/download_pdf/' . $laudo->id); ?>" class="btn btn-primary">
                <i data-feather="download"></i> Baixar PDF
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script>feather.replace();</script>
</body>
</html>