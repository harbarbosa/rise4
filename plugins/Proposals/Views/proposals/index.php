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
                <div class="filter-bar mb15">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="kanban-search" placeholder="Buscar propostas...">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary" id="kanban-filter-btn">Filtrar</button>
                            <button type="button" class="btn btn-default" id="kanban-filter-clear">Limpar</button>
                        </div>
                    </div>
                </div>
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
                                <div class="kanban-column-heading">
                                    <span class="kanban-column-title"><?php echo $status_name; ?></span>
                                    <span class="kanban-count badge" data-status-id="<?php echo $status_id; ?>">0</span>
                                </div>
                                <div class="kanban-column-total" data-status-id="<?php echo $status_id; ?>">R$ 0,00</div>
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
        
        // Carregar o Kanban no primeiro clique. O layout usa Bootstrap 4/5
        // em instalações diferentes, por isso não dependemos apenas de
        // shown.bs.tab.
        var kanbanLoading = false;
        var kanbanDragging = false;

        // Filtros do Kanban
        $('#kanban-filter-btn').on('click', function() {
            loadKanbanBoard();
        });
        
        $('#kanban-filter-clear').on('click', function() {
            $('#kanban-search').val('');
            loadKanbanBoard();
        });
        
        $('#kanban-search').on('keypress', function(e) {
            if (e.which === 13) {
                loadKanbanBoard();
            }
        });

        function switchProposalTab(target) {
            $('.nav-tabs .nav-link').removeClass('active');
            $('.tab-pane').removeClass('active show');
            $('.nav-tabs a[href="' + target + '"]').addClass('active');
            $(target).addClass('active show');
        }

        $('a[href="#kanban-view"]').on('click', function(e) {
            e.preventDefault();
            switchProposalTab('#kanban-view');
            loadKanbanBoard();
        });

        $('a[href="#list-view"]').on('click', function(e) {
            e.preventDefault();
            switchProposalTab('#list-view');
        });
        
        function loadKanbanBoard() {
            if (kanbanLoading) {
                return;
            }
            kanbanLoading = true;
            
            var search = $('#kanban-search').val() || '';
            
            $.ajax({
                url: '<?php echo_uri("propostas/kanban_data") ?>',
                type: 'GET',
                data: {search: search},
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        // Atualizar contadores
                        $.each(response.data.counts, function(status_id, count) {
                            $('.kanban-count[data-status-id="' + status_id + '"]').text(count);
                        });
                        $.each(response.data.totals_formatted || {}, function(status_id, total) {
                            $('.kanban-column-total[data-status-id="' + status_id + '"]').text(total);
                        });
                        
                        // Limpar todas as colunas antes de renderizar.
                        $('.kanban-column-content').empty();

                        // Renderizar cards em cada coluna
                        $.each(response.data.proposals, function(status_id, proposals) {
                            var $column = $('#kanban-status-' + status_id);
                            if (!$column.length) {
                                return;
                            }
                            $column.empty();
                            
                            proposals.forEach(function(proposal) {
                                var cardHtml = '<div class="kanban-card" data-proposal-id="' + proposal.id + '">' +
                                    '<div class="kanban-card-title">' + (proposal.title || 'Sem título') + '</div>' +
                                    '<div class="kanban-card-client">' + (proposal.client_name || 'Sem cliente') + '</div>' +
                                    '<div class="kanban-card-value">' + (proposal.total_sale_formatted || 'R$ 0,00') + '</div>' +
                                    '</div>';
                                $column.append($(cardHtml).attr('data-total-value', proposal.total_sale || 0));
                            });
                            
                        });

                        // Drag and drop nativo, sem depender do jquery-ui.
                        $('.kanban-card').attr('draggable', 'true')
                            .off('dragstart.proposals dragend.proposals')
                            .on('dragstart.proposals', function(event) {
                                kanbanDragging = true;
                                event.originalEvent.dataTransfer.setData('text/plain', $(this).attr('data-proposal-id'));
                                event.originalEvent.dataTransfer.effectAllowed = 'move';
                                $(this).addClass('kanban-card-dragging');
                            })
                            .on('dragend.proposals', function() {
                                $(this).removeClass('kanban-card-dragging');
                                window.setTimeout(function() { kanbanDragging = false; }, 0);
                            });

                        $('.kanban-column-content')
                            .off('dragover.proposals dragleave.proposals drop.proposals')
                            .on('dragover.proposals', function(event) {
                                event.preventDefault();
                                event.originalEvent.dataTransfer.dropEffect = 'move';
                                $(this).addClass('kanban-column-drag-over');
                            })
                            .on('dragleave.proposals', function() {
                                $(this).removeClass('kanban-column-drag-over');
                            })
                            .on('drop.proposals', function(event) {
                                event.preventDefault();
                                var $column = $(this);
                                $column.removeClass('kanban-column-drag-over');
                                var proposalId = event.originalEvent.dataTransfer.getData('text/plain');
                                var $card = $('.kanban-card[data-proposal-id="' + proposalId + '"]');
                                if (!$card.length || $card.parent().is($column)) {
                                    return;
                                }
                                $column.append($card);
                                var newStatusId = String($column.attr('id')).replace('kanban-status-', '');
                                updateProposalStatus(proposalId, newStatusId);
                            });

                        function updateKanbanCounts() {
                            $('.kanban-column').each(function() {
                                var statusId = $(this).data('status-id');
                                $('.kanban-count[data-status-id="' + statusId + '"]').text($(this).find('.kanban-card').length);
                            });
                        }

                        updateKanbanCounts();
                        /*
                         * Os handlers acima são reanexados após cada carga para
                         * contemplar cards novos vindos do AJAX.
                         */
                    }
                    kanbanLoading = false;
                },
                error: function() {
                    kanbanLoading = false;
                    appAlert.error('<?php echo app_lang("error_occurred"); ?>');
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
                        loadKanbanBoard();
                    } else {
                        // Recalcula contagem e valor total das duas colunas após o movimento.
                        loadKanbanBoard();
                    }
                },
                error: function() {
                    appAlert.error('<?php echo app_lang("error_occurred"); ?>');
                    loadKanbanBoard();
                }
            });
        }
        
        // Abrir proposta ao clicar no card
        $(document).on('click', '.kanban-card', function() {
            if (kanbanDragging) {
                return;
            }
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
        height: 620px;
        background: #f5f5f5;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .kanban-column-header {
        padding: 12px;
        border-radius: 8px 8px 0 0;
        color: white;
        font-weight: 600;
    }
    .kanban-column-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .kanban-column-total {
        margin-top: 6px;
        font-size: 16px;
        font-weight: 700;
        white-space: nowrap;
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
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
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
    .kanban-card-dragging {
        opacity: 0.45;
    }
    .kanban-column-drag-over {
        background: #e8f2ff;
        outline: 2px dashed #2d9cdb;
        outline-offset: -2px;
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
