<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - Laudo Técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 100px 0; }
        .password-card { max-width: 400px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-card p-5">
            <h4 class="text-center mb-4">Laudo Protegido por Senha</h4>
            <p class="text-muted text-center">Este documento requer autenticação.</p>
            
            <form id="password-form">
                <input type="hidden" name="token" value="<?php echo $token; ?>" />
                
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="password" class="form-control" required />
                </div>
                
                <div class="alert alert-danger d-none" id="error-msg"></div>
                
                <button type="submit" class="btn btn-primary w-100">Acessar</button>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $('#password-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo get_uri("laudo_documents/verify_password/" . $token); ?>',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $('#error-msg').text(response.message).removeClass('d-none');
                }
            }
        });
    });
    </script>
</body>
</html>