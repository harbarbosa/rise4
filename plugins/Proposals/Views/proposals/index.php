<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('proposals_menu'); ?></h1>
            <div class="title-button-group">
                <?php if (isset($can_manage) && $can_manage) { ?>
                    <?php echo modal_anchor(get_uri('propostas/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('proposals_add'), array("class" => "btn btn-default", "title" => app_lang('proposals_add'))); ?>
                <?php } ?>
            </div>
        </div>
        
        <!-- Abas de Visualização -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#list-view" aria-controls="list-view" role="tab" data-toggle="tab" data-bs-toggle="tab"><?php echo app_lang('proposals_list_view'); ?></a></li>
            <li role="presentation"><a href="#kanban-view" aria-controls="kanban-view" role="tab" data-toggle="tab" data-bs-toggle="tab"><?php echo app_lang('proposals_kanban_view'); ?></a></li>
        </ul>
        
        <div class="tab-content">
            <!-- Visualização em Lista -->
            <div role="tabpanel" class="tab-pane active" id="list-view">
                <div class="table-responsive">
                    <table id="proposals-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
            
            <!-- Visualização Kanban -->
            <div role="tabpanel" class="tab-pane" id="kanban-view">
                <div id="proposals-kanban" class="kanban-wrapper">
                    <div class="kanban-scroll-holder">
                        <?php 
                        $statuses = isset($statuses_kanban) ? $statuses_kanban : array();
                        foreach ($statuses as $status) {
                            $status_id = $status->id ?? 0;
                            $status_name = $status->name ?? 'Sem status';
                            $status_color = $status->color ?? '#666';
                        ?>
                        <div class="kanban-column" data-status-id="<?php echo $status_id; ?>">
                            <div class="kanban-column-header" style="background-color: <?php echo $status_color; ?>">
                                <span class="kanban-column-title"><?php echo $status_name; ?></span>
                                <span class="kanban-count badge" data-status-id="<?php echo $status_id; ?>">0</span>
                            </div>
                            <div class="kanban-column-content" id="kanban-status-<?php echo $status_id; ?>">
                                <!-- Itens serão carregados via AJAX -->
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        // Tabela padrão
        $("#proposals-table").appTable({
            source: '<?php echo_uri("propostas/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w150", options: <?php echo $statuses_dropdown; ?>}
            ],
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('proposals_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('proposals_title'); ?>"},
                {title: "<?php echo app_lang('client'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<?php echo app_lang('proposals_total'); ?>"},
                {title: "<?php echo app_lang('last_activity'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
        
        // Carregar Kanban ao clicar na aba
        $('a[href="#kanban-view"]').on('shown.bs.tab', function() {
            loadKanbanBoard();
        });
        
        function loadKanbanBoard() {
            $.ajax({
                url: '<?php echo_uri("propostas/kanban_data") ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        // Atualizar contadores
                        $.each(response.data.counts, function(status_id, count) {
                            $('.kanban-count[data-status-id="' + status_id + '"]').text(count);
                        });
                        
                        // Renderizar cards em cada coluna
                        $.each(response.data.proposals, function(status_id, proposals) {
                            var $column = $('#kanban-status-' + status_id);
                            $column.empty();
                            
                            proposals.forEach(function(proposal) {
                                var cardHtml = '<div class="kanban-card" data-proposal-id="' + proposal.id + '">' +
                                    '<div class="kanban-card-title">' + (proposal.title || 'Sem título') + '</div>' +
                                    '<div class="kanban-card-client">' + (proposal.client_name || 'Sem cliente') + '</div>' +
                                    '<div class="kanban-card-value">' + (proposal.total_sale_formatted || 'R$ 0,00') + '</div>' +
                                    '</div>';
                                $column.append(cardHtml);
                            });
                            
                            // Tornar sortable
                            $column.sortable({
                                connectWith: '.kanban-column-content',
                                placeholder: 'kanban-card-placeholder',
                                update: function(event, ui) {
                                    var newStatusId = $(ui.item).parent().attr('id').replace('kanban-status-', '');
                                    var proposalId = $(ui.item).attr('data-proposal-id');
                                    updateProposalStatus(proposalId, newStatusId);
                                }
                            });
                        });
                    }
                }
            });
        }
        
        function updateProposalStatus(proposalId, newStatusId) {
            $.ajax({
                url: '<?php echo_uri("propostas/update_status") ?>',
                type: 'POST',
                data: {
                    id: proposalId,
                    status: newStatusId
                },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        appAlert.error('<?php echo app_lang("error_occurred"); ?>');
                    }
                }
            });
        }
        
        // Abrir proposta ao clicar no card
        $(document).on('click', '.kanban-card', function() {
            var proposalId = $(this).attr('data-proposal-id');
            window.location.href = '<?php echo_uri("propostas/view/"); ?>' + proposalId;
        });
    });
</script>

<style type="text/css">
    .kanban-wrapper {
        padding: 15px;
        overflow-x: auto;
    }
    .kanban-scroll-holder {
        display: flex;
        gap: 15px;
        min-height: 500px;
    }
    .kanban-column {
        min-width: 280px;
        max-width: 280px;
        background: #f5f5f5;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
    }
    .kanban-column-header {
        padding: 12px;
        border-radius: 8px 8px 0 0;
        color: white;
        font-weight: 600;
    }
    .kanban-column-title {
        font-size: 14px;
    }
    .kanban-count {
        float: right;
        background: rgba(255,255,255,0.3);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
    }
    .kanban-column-content {
        padding: 10px;
        flex: 1;
        min-height: 200px;
    }
    .kanban-card {
        background: white;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: box-shadow 0.2s;
    }
    .kanban-card:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }
    .kanban-card-title {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 14px;
    }
    .kanban-card-client {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    .kanban-card-value {
        font-size: 13px;
        color: #2dce89;
        font-weight: 600;
    }
    .kanban-card-placeholder {
        background: #e0e0e0;
        border: 2px dashed #999;
        border-radius: 6px;
        min-height: 80px;
    }
</style>