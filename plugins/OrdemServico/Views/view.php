<div id="page-content" class="page-wrapper clearfix">
  <div class="row">
    <div class="col-sm-12">
      <div class="page-title clearfix">
        <h4><?php echo app_lang('os_menu_title'); ?></h4>
        <div class="title-button-group">
          <?php echo js_anchor("<i data-feather='check-circle' class='icon-16'></i> Fechar OS", [
            'class' => 'btn btn-success',
            'title' => 'Fechar OS',
            'data-action-url' => get_uri('ordemservico/close'),
            'data-id' => $os->id,
            'data-action' => 'delete-confirmation' // reuse confirmation dialog
          ]); ?>

          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo app_lang('actions'); ?>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li><?php echo modal_anchor(get_uri("ordemservico/modal_form"), "<i data-feather='edit' class='icon-16'></i> Editar", ["title" => "Editar OS", "data-post-id" => $os->id]); ?></li>
              <li><?php echo js_anchor("<i data-feather='x' class='icon-16'></i> ".app_lang('delete'), ["data-id"=>$os->id, "data-action-url"=>get_uri("ordemservico/delete"), "data-action"=>"delete-confirmation"]); ?></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="card p15">
        <div class="clearfix">
          <div class="float-start">
            <h3 class="mb0">OS - <?php 
              $code = str_pad((string)$os->id, 4, '0', STR_PAD_LEFT);
              echo substr($code,0,3)."".substr($code,3,3);
            ?></h3>
            <div class="text-off">
              <?php echo isset($client->company_name) ? esc($client->company_name) : '-'; ?>
            </div>

            <div>
              </h3><?php echo $os->titulo;?></h3>
            </div>

          </div>
          <div class="float-end">
            <?php 
              $status = $os->status ?: 'aberta';
              $cls = 'badge bg-secondary';
              if ($status === 'aberta') { $cls = 'badge bg-warning'; }
              else if ($status === 'fechada') { $cls = 'badge bg-success'; }
              else if ($status === 'em_andamento') { $cls = 'badge bg-info'; }
            ?>
            <span class="<?php echo $cls; ?>"><?php echo ucfirst(str_replace('_',' ', $status)); ?></span>
            <div class="mt5 text-off small">
              <?php 
                $who = trim(($creator->first_name ?? '').' '.($creator->last_name ?? '')) ?: 'Usuário';
                $createdAt = '';
                if (!empty($os->created_at)) {
                    $createdAt = format_to_datetime($os->created_at);
                } elseif (!empty($os->data_abertura)) {
                    // fallback to opening date if created_at is missing on legacy schema
                    $createdAt = format_to_date($os->data_abertura, false);
                }
                echo "Aberta por ".esc($who).( $createdAt ? (" em ".$createdAt) : '' );
              ?>
            </div>
            <div class="mt5">
              <span class="text-off">Tipo:</span> <?php echo esc($os->tipo_title ?? ''); ?>
            </div>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs bg-white title" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-resumo" role="tab">Resumo</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-servicos" role="tab">Serviços</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-produtos" role="tab">Produtos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-atendimentos" role="tab">Atendimentos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-arquivos" role="tab">Arquivos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-fechamento" role="tab">Fechamento/Cobrança</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-adiantamentos" role="tab">Adiantamentos</a></li>
      
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-historico" role="tab">Histórico</a></li>
      </ul>

      <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade show active" id="tab-resumo">
          <div class="row">
            <div class="col-md-8">
              <div class="card p15 mb15">
                <h4 class="mb15">Dados Básicos</h4>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Cliente / Local</div>
                  <div class="col-sm-8">
                    <div><?php echo esc($client->company_name ?? ''); ?></div>
                    <div class="text-off small">
                      <?php
                        $addr = trim(($client->address ?? '').' '.($client->city ?? '').' '.($client->state ?? '').' '.($client->zip ?? ''));
                        echo esc($addr ?: '');
                      ?>
                      <?php if(!empty($addr)) { ?>
                        <a class="ms10" target="_blank" href="https://maps.google.com/?q=<?php echo urlencode($addr); ?>">Ver no mapa</a>
                      <?php } ?>
                    </div>
                  </div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Contato Principal</div>
                  <div class="col-sm-8">-
                    <a class="ms10" href="#">WhatsApp</a>
                  </div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Tipo</div>
                  <div class="col-sm-8"><?php echo esc($os->tipo_title ?? ''); ?></div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Equipe Técnica</div>
                  <div class="col-sm-8"><?php echo trim(($tech->first_name ?? '').' '.($tech->last_name ?? '')); ?></div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Agendamento</div>
                  <div class="col-sm-8">-</div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Motivo / Defeito</div>
                  <div class="col-sm-8"><?php echo esc($os->motivo_title ?? ''); ?></div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Vendedor</div>
                  <div class="col-sm-8">-</div>
                </div>
                <div class="row mb10">
                  <div class="col-sm-4 text-off">Contrato</div>
                  <div class="col-sm-8">
                    <?php $contractId = isset($os->contract_id) ? (int)$os->contract_id : 0; echo $contractId ? ('#'.$contractId) : 'Nenhum contrato vinculado'; ?>
                  </div>
                </div>
              </div>

              <div class="card p15" id="os-comments-section">
                <h4 class="mb15"><?php echo app_lang('comments'); ?></h4>

                <div id="os-comment-form-container">
                  <?php echo form_open(get_uri('ordemservico/comment_save'), ['id' => 'os-comment-form', 'class' => 'general-form', 'role' => 'form']); ?>
                  <div class="d-flex b-b comment-form-container">
                    <div class="flex-shrink-0 d-none d-sm-block">
                      <div class="avatar avatar-sm pr15 d-table-cell">
                        <img src="<?php echo get_avatar($login_user->image ?? ''); ?>" alt="..." />
                      </div>
                    </div>
                    <div class="w-100">
                      <div class="post-dropzone mb-3 form-group">
                        <input type="hidden" name="os_id" value="<?php echo (int)$os->id; ?>">
                        <?php echo form_textarea(["id"=>"os_comment_description","name"=>"comment","class"=>"form-control comment_description","placeholder"=>app_lang('write_a_comment'),"data-rule-required"=>true,"data-msg-required"=>app_lang('field_required')]); ?>
                        <footer class="card-footer b-a clearfix">
                          <button class="btn btn-primary float-end" type="submit"><i data-feather="send" class='icon-16'></i> <?php echo app_lang("post_comment"); ?></button>
                        </footer>
                      </div>
                    </div>
                  </div>
                  <?php echo form_close(); ?>
                </div>
                <div id='os-comments-table'>
                <?php foreach (($comments ?? []) as $comment) { echo view('OrdemServico\\Views\\comments\\comment_row', ['comment' => $comment, 'login_user' => $login_user]); } ?>
              </div>
                      </div>
            </div>
            
           <div class="col-md-4">
            <!-- Card de valores -->
            <div class="card p15 mb15">
              <h4 class="mb15">Valores</h4>
              <div class="row mb10">
                <div class="col-8 text-off">Serviços</div>
                <div class="col-4 text-end"><span id="total-servicos">R$ 0,00</span></div>
              </div>
              <div class="row mb10">
                <div class="col-8 text-off">Produtos</div>
                <div class="col-4 text-end"><span id="total-produtos">R$ 0,00</span></div>
              </div>
              <div class="row mb10">
                <div class="col-8 text-off">Horas (Atendimentos)</div>
                <div class="col-4 text-end"><span id="resumo-horas-total">-</span></div>
              </div>
              <hr/>
              <div class="row">
                <div class="col-8"><strong>Total</strong></div>
                <div class="col-4 text-end"><span id="total-produtos-servicos">R$ 0,00</span></div>
              </div>
            </div>

            <!-- Card de horas gastas, agora logo abaixo -->
            <div class="card p15">
              <h4 class="mb15">Horas Atendimentos</h4>
              <div class="text-end">
                <span id="resumo-horas-caixa" class="h3">-</span>
              </div>
            </div>
          </div>

          </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="tab-servicos">
          <div class="card p15 mb15">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="mb0">Serviços</h4>
              <div>
                <?php echo modal_anchor(get_uri('ordemservico/os_services_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar Serviço", ["class" => "btn btn-default", "title" => "Adicionar Serviço", "data-post-os_id" => (int)$os->id]); ?>
              </div>
            </div>
          </div>

          <div class="card p15">
            <div class="table-responsive">
              <table id="os-services-table" class="display" width="100%"></table>
            </div>
          </div>

          <div class="card p15 mt15">
            <div class="row">
              <div class="col-md-12"><strong>Total Geral:</strong> <span id="total-geral">-</span></div>
            </div>
          </div>

          <script>
          // Totals helpers available globally for both tabs
          if (typeof window.__os_services_total_n === 'undefined') { window.__os_services_total_n = 0; }
          if (typeof window.__os_products_total_n === 'undefined') { window.__os_products_total_n = 0; }
          if (typeof window.formatBRL !== 'function') {
            window.formatBRL = function(n){
              try { return (new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'})).format(n||0); }
              catch(e){ return 'R$ ' + (parseFloat(n||0).toFixed(2)).replace('.', ','); }
            };
          }
          if (typeof window.updateResumoCombined !== 'function') {
            window.updateResumoCombined = function(){
              var s = parseFloat(window.__os_services_total_n || 0) || 0;
              var p = parseFloat(window.__os_products_total_n || 0) || 0;
              var total = s + p;
              $('#total-produtos-servicos').text(window.formatBRL(total));
            };
          }
          $(function(){
            function initOsItemsTables(){
              $("#os-services-table").appTable({
                source: '<?php echo_uri("ordemservico/os_services_list_data/".$os->id) ?>',
                order: [[0, 'asc']],
                columns: [
                  {title: 'Descrição'},
                  {title: 'Qtd', "class": "w80 text-end"},
                  {title: 'Unid', "class": "w80"},
                  {title: 'Vlr Unit', "class": "w120 text-end"},
                  {title: 'Desc', "class": "w120 text-end"},
                  {title: 'Total', "class": "w120 text-end"},
                  {title: 'Tipo', "class": "w120"},
                  {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
                ],
               
              });
            }

            function loadOsServiceTotals(){
              $.post('<?php echo get_uri('ordemservico/os_services_totals'); ?>', { os_id: <?php echo (int)$os->id; ?> })
                .done(function(res){
                  if(res){
                    var totalNum = parseFloat(res.total_geral || 0) || 0;
                    if(res.formatted && res.formatted.total_geral){
                      $('#total-geral').text(res.formatted.total_geral);
                      $('#total-servicos').text(res.formatted.total_geral);
                    } else {
                      var f = window.formatBRL(totalNum);
                      $('#total-geral').text(f);
                      $('#total-servicos').text(f);
                    }
                    window.__os_services_total_n = totalNum;
                    if (typeof window.updateResumoCombined === 'function') { window.updateResumoCombined(); }
                  }
                });
            }

            // expose for callbacks
            window.loadOsServiceTotals = loadOsServiceTotals;
            initOsItemsTables();
            loadOsServiceTotals();

            // Expose a targeted reload callback to avoid global AJAX loops
            window.reloadOsItems = function(){
              $("#os-services-table").appTable({reload: true});
              loadOsServiceTotals();
            };
          });
          </script>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="tab-produtos">
          <div class="card p15 mb15">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="mb0">Produtos</h4>
              <div>
                <?php echo modal_anchor(get_uri("ordemservico/os_products_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar Produto", ["class" => "btn btn-default", "title" => "Adicionar Produto", "data-post-os_id" => (int)$os->id]); ?>
              </div>
            </div>
          </div>
          <div class="card p15">
            <div class="table-responsive">
              <table id="os-products-table" class="display" width="100%"></table>
            </div>
          </div>
          <div class="card p15 mt15">
            <div class="row">
              <div class="col-md-12"><strong>Total Produtos:</strong> <span id="total-produtos-geral">-</span></div>
            </div>
          </div>
          <script>
          $(function(){
            $("#os-products-table").appTable({
              source: '<?php echo_uri("ordemservico/os_products_list_data/".$os->id) ?>',
              order: [[0, "asc"]],
              columns: [
                {title: "Descrição"},
                {title: "Qtd", "class": "w80 text-end"},
                {title: "Unid", "class": "w80"},
                {title: "Vlr Unit", "class": "w120 text-end"},
                {title: "Desc", "class": "w120 text-end"},
                {title: "Total", "class": "w120 text-end"},
                {title: "Tipo", "class": "w120"},
                {title: "<i data-feather=\"menu\" class=\"icon-16\"></i>", "class": "text-center option w100"}
              ],
              
            });
            window.loadOsProductTotals = function(){
              $.post('<?php echo get_uri('ordemservico/os_products_totals'); ?>', { os_id: <?php echo (int)$os->id; ?> })
                .done(function(res){
                  if(res){
                    var totalNum = parseFloat(res.total_geral || 0) || 0;
                    if(res.formatted && res.formatted.total_geral){
                      $("#total-produtos-geral").text(res.formatted.total_geral);
                      $("#total-produtos").text(res.formatted.total_geral);
                    } else {
                      var f = window.formatBRL(totalNum);
                      $("#total-produtos-geral").text(f);
                      $("#total-produtos").text(f);
                    }
                    window.__os_products_total_n = totalNum;
                    if (typeof window.updateResumoCombined === 'function') { window.updateResumoCombined(); }
                  }
                });
            };
            loadOsProductTotals();
          });
          </script>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="tab-atendimentos">
          <div class="card p15 mb15">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="mb0">Atendimentos</h4>
              <div>
                <?php echo modal_anchor(get_uri("ordemservico/os_atendimentos_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar Atendimento", ["class" => "btn btn-default", "title" => "Adicionar Atendimento", "data-post-os_id" => (int)$os->id]); ?>
              </div>
            </div>
          </div>
          <div class="card p15">
            <div class="table-responsive">
              <table id="os-atendimentos-table" class="display" width="100%"></table>
            </div>
          </div>
          <div class="card p15 mt15">
            <div class="row">
              <div class="col-md-12"><strong>Total de Horas:</strong> <span id="total-atendimentos-horas">-</span></div>
            </div>
          </div>
          <script>
          $(function(){
            $("#os-atendimentos-table").appTable({
              source: '<?php echo_uri("ordemservico/os_atendimentos_list_data/".$os->id) ?>',
              order: [[1, "desc"]],
              columns: [
                {title: "Equipe"},
                {title: "Início", "class": "w180"},
                {title: "Fim", "class": "w180"},
                {title: "Duração", "class": "w120 text-end"},
                {title: "Observação"},
                {title: "<i data-feather=\"menu\" class=\"icon-16\"></i>", "class": "text-center option w120"}
              ],
              onInitComplete: function(){ try { $('[data-bs-toggle="tooltip"]').tooltip(); } catch(e){} }
            });
            function loadOsAtendimentosTotals(){
              $.post('<?php echo get_uri('ordemservico/os_atendimentos_totals'); ?>', { os_id: <?php echo (int)$os->id; ?> })
                .done(function(res){
                  if(res){
                    var t = (res.formatted || '-');
                    $('#total-atendimentos-horas').text(t);
                    $('#resumo-horas-total').text(t);
                    $('#resumo-horas-caixa').text(t);
                  }
                });
            }
            window.reloadOsAtendimentos = function(){
              $("#os-atendimentos-table").appTable({reload: true});
              // re-init tooltips after the table re-renders
              setTimeout(function(){ try { $('[data-bs-toggle="tooltip"]').tooltip(); } catch(e){} }, 400);
              loadOsAtendimentosTotals();
            };
            loadOsAtendimentosTotals();
          });
          </script>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="tab-arquivos">
          <div class="card p15 mb15">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="mb0">Arquivos</h4>
              <div>
                <?php echo modal_anchor(get_uri("ordemservico/os_files_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar Arquivos", ["class" => "btn btn-default", "title" => "Enviar Arquivos", "data-post-os_id" => (int)$os->id]); ?>
              </div>
            </div>
          </div>
          <div class="card p15">
            <div class="table-responsive">
              <table id="os-files-table" class="display" width="100%"></table>
            </div>
          </div>
          <script>
          $(function(){
            $("#os-files-table").appTable({
              source: '<?php echo_uri("ordemservico/os_files_list_data/".$os->id) ?>',
              order: [[4, "desc"]],
              columns: [
                {title: "Arquivo"},
                {title: "Descrição"},
                {title: "Enviado por"},
                {title: "Tamanho", class: "w120"},
                {title: "Data", class: "w180"},
                {title: "<i data-feather=\"menu\" class=\"icon-16\"></i>", class: "text-center option w100"}
              ]
            });
            window.reloadOsFiles = function(){ $("#os-files-table").appTable({reload:true}); };
          });
          </script>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="tab-adiantamentos">
          <div class="card p15">Em breve</div>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="tab-fechamento">
          <div class="card p15">Em breve</div>
        </div>
       
        <div role="tabpanel" class="tab-pane fade" id="tab-historico">
          <div class="card p15">Em breve</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function reloadOsCommentsDiv() {
  var osId = <?php echo (int)$os->id; ?>;

  $("#os-comments-table").load("<?php echo get_uri('ordemservico/comments_reload'); ?>/" + osId, function() {
    // Opcional: rola até o final da lista ou mostra aviso visual
    console.log("Comentários atualizados com sucesso!");
  });
}

$(function(){
  window.loadOsComments = function(){
    $("#os-comments-table").appTable({
      source: '<?php echo_uri("ordemservico/comments_list_data") ?>',
      order: [[2, 'desc']],
      filterDropdown: [ ],
      columns: [
        {title: 'Autor'},
        {title: 'Comentário'},
        {title: 'Data'},
        {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
      ],
      serverSide: false,
      datatable: {
        "ajax": {
          "type": "POST",
          "data": function(d){ d.os_id = <?php echo (int)$os->id; ?>; }
        }
      }
    });
  };

  loadOsComments();

  // Inline add comment without modal
$("#os-comment-form").appForm({
  isModal: false, // <-- força não abrir modal automático
  onSuccess: function (result) {
    if (result && result.success) {
      $("#os-comment-form")[0].reset();
      
      appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>");
      $("#os-comments-table").append(result.data)
    }
  }
});



});


</script>
<script>
$(function(){
  window.loadOsComments = function(){
    $.post('<?php echo get_uri('ordemservico/comments_html'); ?>', { os_id: <?php echo (int)$os->id; ?> })
      .done(function(html){
        $('#os-comments-table').html(html);
        if (window.feather) { window.feather.replace(); }
      });
  };

  // Initial render and ensure refresh after add
  window.loadOsComments();
  $("#os-comment-form").appForm({
    onSuccess: function(result){
      if(result && result.success){
        $(".comment_description").val("");
        window.loadOsComments();
        appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>", {duration: 10000});
      }
    }
  });
});
</script>



