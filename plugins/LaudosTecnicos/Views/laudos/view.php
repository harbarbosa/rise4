<?php
$laudo = $laudo;
?>

<div id="page-content" class="page-wrapper clearfix">
    <!-- Header -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">
                    <span class="badge bg-secondary"><?php echo $laudo->laudo_number; ?></span>
                    <?php echo $laudo->title; ?>
                    <?php if ($laudo->version > 1): ?>
                    <span class="badge bg-info">v<?php echo $laudo->version; ?></span>
                    <?php endif; ?>
                </h4>
                <small class="text-muted">
                    <?php echo $laudo->type_name ?? '-'; ?> | 
                    <?php echo $laudo->category_name ?? '-'; ?> | 
                    <?php echo $laudo->company_name ?? '-'; ?>
                </small>
            </div>
            <div>
                <span class="badge bg-<?php echo $this->_get_status_color($laudo->status); ?> fs-6">
                    <?php echo $laudo->status; ?>
                </span>
                <?php if ($can_edit): ?>
                <?php echo modal_anchor(get_uri("laudos_tecnicos/modal_form/" . $laudo->id), "<i data-feather='edit-2' class='icon-16'></i>", array("class" => "btn btn-default btn-sm", "title" => app_lang('edit'))); ?>
                <?php endif; ?>
                <?php if ($can_change_status && !empty($available_transitions)): ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                        <i data-feather="refresh-cw" class="icon-16"></i> Alterar Status
                    </button>
                    <div class="dropdown-menu">
                        <?php foreach ($available_transitions as $transition): ?>
                        <a class="dropdown-item" href="javascript:;" onclick="changeStatus('<?php echo $transition->to_status_code; ?>')">
                            <?php echo $transition->to_status_name; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <div class="card mt-3">
        <div class="card-body">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" href="#view-summary" data-toggle="tab"><?php echo app_lang('laudos_tab_summary'); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#view-technical" data-toggle="tab"><?php echo app_lang('laudos_tab_technical'); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#view-history" data-toggle="tab"><?php echo app_lang('laudos_tab_history'); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#view-files" data-toggle="tab"><?php echo app_lang('laudos_tab_files'); ?></a></li>
                <li class="nav-item"><a class="nav-link disabled" href="#view-checklists" data-toggle="tab"><?php echo app_lang('laudos_tab_checklists'); ?></a></li>
                <li class="nav-item"><a class="nav-link disabled" href="#view-photos" data-toggle="tab"><?php echo app_lang('laudos_tab_photos'); ?></a></li>
            </ul>

            <div class="tab-content mt-3">
                <!-- Resumo -->
                <div class="tab-pane fade show active" id="view-summary">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><?php echo app_lang('laudos_identification'); ?></h5>
                            <table class="table table-sm">
                                <tr><th width="40%"><?php echo app_lang('laudos_client'); ?></th><td><?php echo $laudo->company_name ?? '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('project'); ?></th><td><?php echo $laudo->project_title ?? '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('laudos_address'); ?></th><td><?php echo $laudo->address ?? '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('laudos_location'); ?></th><td><?php echo $laudo->location ?? '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('priority'); ?></th><td><span class="badge bg-<?php echo $this->_get_priority_color($laudo->priority); ?>"><?php echo ucfirst($laudo->priority ?? 'normal'); ?></span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5><?php echo app_lang('laudos_dates'); ?></h5>
                            <table class="table table-sm">
                                <tr><th width="40%"><?php echo app_lang('laudos_request_date'); ?></th><td><?php echo $laudo->request_date ? $laudo->request_date : '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('laudos_scheduled_date'); ?></th><td><?php echo $laudo->scheduled_date ? $laudo->scheduled_date : '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('laudos_inspection_date'); ?></th><td><?php echo $laudo->inspection_date ? $laudo->inspection_date : '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('laudos_issue_date'); ?></th><td><?php echo $laudo->issue_date ? $laudo->issue_date : '-'; ?></td></tr>
                                <tr><th><?php echo app_lang('laudos_valid_until'); ?></th><td><?php echo $laudo->valid_until ? $laudo->valid_until : '-'; ?></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5><?php echo app_lang('laudos_team'); ?></h5>
                            <table class="table table-sm">
                                <tr>
                                    <th><?php echo app_lang('laudos_commercial_responsible'); ?></th>
                                    <th><?php echo app_lang('laudos_technician'); ?></th>
                                    <th><?php echo app_lang('laudos_reviewer'); ?></th>
                                    <th><?php echo app_lang('laudos_approver'); ?></th>
                                </tr>
                                <tr>
                                    <td><?php echo $laudo->commercial_name ?? '-'; ?></td>
                                    <td><?php echo $laudo->technician_name ?? '-'; ?></td>
                                    <td><?php echo $laudo->reviewer_name ?? '-'; ?></td>
                                    <td><?php echo $laudo->approver_name ?? '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($laudo->description): ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5><?php echo app_lang('description'); ?></h5>
                            <p><?php echo nl2br($laudo->description); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Conteúdo Técnico -->
                <div class="tab-pane fade" id="view-technical">
                    <?php if ($laudo->objective): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_objective'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->objective); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->scope): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_scope'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->scope); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->methodology): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_methodology'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->methodology); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->installation_description): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_installation_description'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->installation_description); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->results): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_results'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->results); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->diagnosis): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_diagnosis'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->diagnosis); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->conclusion): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_conclusion'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->conclusion); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laudo->recommendations): ?>
                    <div class="mb-3">
                        <h6><strong><?php echo app_lang('laudos_recommendations'); ?></strong></h6>
                        <p><?php echo nl2br($laudo->recommendations); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($laudo->objective) && empty($laudo->scope) && empty($laudo->methodology)): ?>
                    <div class="alert alert-info"><?php echo app_lang('laudos_no_technical_content'); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Histórico -->
                <div class="tab-pane fade" id="view-history">
                    <div class="timeline">
                        <?php if (!empty($status_history)): ?>
                            <?php foreach ($status_history as $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon bg-<?php echo $this->_get_status_color($history->to_status_code); ?>">
                                    <i data-feather="activity" class="icon-16"></i>
                                </div>
                                <div class="timeline-content">
                                    <span class="badge bg-<?php echo $this->_get_status_color($history->to_status_code); ?>">
                                        <?php echo $history->to_status_name; ?>
                                    </span>
                                    <small class="text-muted"><?php echo $history->created_at; ?></small>
                                    <?php if ($history->user_name): ?>
                                    <small class="d-block">Por: <?php echo $history->user_name; ?></small>
                                    <?php endif; ?>
                                    <?php if ($history->comment): ?>
                                    <p class="mb-0 mt-1"><?php echo nl2br($history->comment); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="alert alert-info"><?php echo app_lang('laudos_no_history'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Arquivos -->
                <div class="tab-pane fade" id="view-files">
                    <div class="alert alert-info">
                        <i data-feather="info" class="icon-16"></i>
                        <?php echo app_lang('laudos_files_coming_soon'); ?>
                    </div>
                </div>
                
                <!-- Checklists (futuro) -->
                <div class="tab-pane fade" id="view-checklists">
                    <div class="alert alert-secondary"><?php echo app_lang('laudos_coming_soon'); ?></div>
                </div>
                
                <!-- Fotos (futuro) -->
                <div class="tab-pane fade" id="view-photos">
                    <div class="alert alert-secondary"><?php echo app_lang('laudos_coming_soon'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de alteração de status -->
<div class="modal fade" id="status-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo app_lang('laudos_change_status'); ?></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="status-form">
                    <input type="hidden" name="status" id="new-status" />
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_comment'); ?></label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo app_lang('cancel'); ?></button>
                <button type="button" class="btn btn-primary" onclick="submitStatusChange()"><?php echo app_lang('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
function changeStatus(status) {
    $('#new-status').val(status);
    $('#status-modal').modal('show');
}

function submitStatusChange() {
    var status = $('#new-status').val();
    var comment = $('textarea[name="comment"]').val();
    
    $.ajax({
        url: '<?php echo get_uri("laudos_tecnicos/change_status/" . $laudo->id); ?>',
        type: 'POST',
        data: { status: status, comment: comment },
        success: function(response) {
            if (response.success) {
                $('#status-modal').modal('hide');
                window.location.reload();
            } else {
                appAlert.error(response.message);
            }
        }
    });
}
</script>

<style>
.timeline { padding-left: 20px; }
.timeline-item { 
    position: relative; 
    padding-left: 30px; 
    padding-bottom: 20px; 
    border-left: 2px solid #dee2e6;
}
.timeline-item:last-child { border-left: none; }
.timeline-icon {
    position: absolute;
    left: -12px;
    top: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>