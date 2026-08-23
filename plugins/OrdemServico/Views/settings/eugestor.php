<div id="page-content" class="page-wrapper clearfix">
  <div class="card">
    <div class="page-title clearfix">
      <h1>Integração EuGestor</h1>
      <div class="title-button-group">
        <button type="button" class="btn btn-default" id="eugestor-test"><i data-feather="wifi" class="icon-16"></i> Testar conexão</button>
        <button type="button" class="btn btn-primary" id="eugestor-sync"><i data-feather="refresh-cw" class="icon-16"></i> Sincronizar OS abertas</button>
      </div>
    </div>
    <div class="alert alert-info">A integração é somente de leitura: nenhuma OS será alterada, cancelada ou fechada no EuGestor.</div>
    <?php echo form_open(get_uri('ordemservico/eugestor_save'), ['id' => 'eugestor-form', 'class' => 'general-form']); ?>
      <div class="form-group">
        <label class="form-check form-switch">
          <input type="checkbox" class="form-check-input" name="enabled" value="1" <?php echo !empty($enabled) ? 'checked' : ''; ?>>
          <span class="form-check-label">Habilitar integração</span>
        </label>
      </div>
      <div class="row">
        <div class="col-md-6 form-group">
          <label>E-mail cadastrado no EuGestor</label>
          <input type="email" name="username" value="<?php echo esc($username ?? ''); ?>" class="form-control" autocomplete="username" required>
        </div>
        <div class="col-md-6 form-group">
          <label>Senha do EuGestor</label>
          <input type="password" name="password" value="" class="form-control" autocomplete="new-password" placeholder="<?php echo !empty($has_password) ? 'Senha já cadastrada — deixe vazio para manter' : 'Informe a senha'; ?>">
          <small class="text-off">A senha é criptografada no banco e nunca é exibida novamente.</small>
        </div>
        <div class="col-md-6 form-group">
          <label>Domínio do EuGestor</label>
          <input type="text" name="domain" value="<?php echo esc($domain ?? 'portal'); ?>" class="form-control" placeholder="portal">
          <small class="text-off">Normalmente é o domínio da empresa no EuGestor. Se não souber, mantenha o valor padrão.</small>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Salvar configuração</button>
    <?php echo form_close(); ?>
    <hr>
    <div class="row">
      <div class="col-md-6"><strong>Última sincronização:</strong> <?php echo esc($last_sync_at ?: 'Nunca'); ?></div>
      <div class="col-md-6"><strong>Resultado:</strong> <span id="eugestor-result-summary"><?php echo !empty($last_sync_result) ? esc(json_encode($last_sync_result, JSON_UNESCAPED_UNICODE)) : 'Nenhum resultado'; ?></span></div>
    </div>
  </div>
</div>
<script>
$(function(){
  $('#eugestor-form').appForm({isModal:false, onSuccess:function(result){ if(result && result.success){ appAlert.success(result.message); } }});
  $('#eugestor-test').on('click', function(){
    appLoader.show();
    $.post('<?php echo get_uri('ordemservico/eugestor_test_connection'); ?>').done(function(r){
      if(r && r.success){ appAlert.success(r.message); } else { appAlert.error((r && r.message) || 'Falha na conexão.'); }
    }).fail(function(xhr){ appAlert.error((xhr.responseJSON && xhr.responseJSON.message) || 'Falha na conexão.'); }).always(function(){ appLoader.hide(); });
  });
  $('#eugestor-sync').on('click', function(){
    appLoader.show();
    $.post('<?php echo get_uri('ordemservico/eugestor_sync'); ?>').done(function(r){
      if(r && r.success){ appAlert.success(r.message); $('#eugestor-result-summary').text(JSON.stringify(r.data)); }
      else { appAlert.error((r && r.message) || 'A sincronização terminou com erros.'); if(r && r.data){ $('#eugestor-result-summary').text(JSON.stringify(r.data)); } }
    }).fail(function(xhr){ appAlert.error((xhr.responseJSON && xhr.responseJSON.message) || 'Falha na sincronização.'); }).always(function(){ appLoader.hide(); });
  });
});
</script>
