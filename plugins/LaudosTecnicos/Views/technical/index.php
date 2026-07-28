<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><i data-feather="check-square" class="icon-20"></i> Checklists</h4>
                </div>
                <div class="card-body">
                    <p>Biblioteca de checklists técnicos com versionamento, grupos e itens configuráveis.</p>
                    <a href="<?php echo get_uri('laudo_technical/checklists'); ?>" class="btn btn-primary">Gerenciar Checklists</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><i data-feather="activity" class="icon-20"></i> Tipos de Medição</h4>
                </div>
                <div class="card-body">
                    <p>Cadastro de tipos de medição com classificação automática (Conforme/Atenção/Não conforme).</p>
                    <a href="<?php echo get_uri('laudo_technical/measurement_types'); ?>" class="btn btn-primary">Gerenciar Tipos</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><i data-feather="tool" class="icon-20"></i> Equipamentos</h4>
                </div>
                <div class="card-body">
                    <p>Controle de equipamentos de medição com alertas de calibração.</p>
                    <a href="<?php echo get_uri('laudo_technical/equipment'); ?>" class="btn btn-primary">Gerenciar Equipamentos</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><i data-feather="book-open" class="icon-20"></i> Normas Técnicas</h4>
                </div>
                <div class="card-body">
                    <p>Biblioteca de normas técnicas vinculadas a tipos de laudo.</p>
                    <a href="<?php echo get_uri('laudo_technical/standards'); ?>" class="btn btn-primary">Gerenciar Normas</a>
                </div>
            </div>
        </div>
    </div>
</div>