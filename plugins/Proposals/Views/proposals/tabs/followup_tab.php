<div class="tab-content mt-3">
    <!-- Aba: Detalhes -->
    <div role="tabpanel" class="tab-pane active" id="details">
        <?php echo $proposal_details_html ?? ''; ?>
    </div>
    
    <!-- Aba: Follow-up (Eventos) -->
    <div role="tabpanel" class="tab-pane" id="followup">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo app_lang('proposals_followup'); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <?php echo modal_anchor(get_uri("events/modal_form"), "<i class='fa fa-plus'></i> " . app_lang('proposals_add_followup'), array("class" => "btn btn-default", "data-post-context" => "proposal", "data-post-proposal_id" => $proposal_info->id)); ?>
                        </div>
                        <div id="followup-events-list">
                            <?php echo $followup_events_html ?? '<p class="text-muted">' . app_lang('proposals_no_followup') . '</p>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba: Anotações -->
    <div role="tabpanel" class="tab-pane" id="notes">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo app_lang('proposals_notes'); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <textarea id="proposal-notes" class="form-control" rows="8" placeholder="<?php echo app_lang('proposals_notes_placeholder'); ?>"><?php echo isset($proposal_info->notes) ? esc($proposal_info->notes) : ''; ?></textarea>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="save-notes-btn"><?php echo app_lang('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba: Arquivos -->
    <div role="tabpanel" class="tab-pane" id="files">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo app_lang('proposals_files'); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php echo view("includes\file_uploader", array(
                            "upload_url" => get_uri("propostas/upload_file/" . $proposal_info->id),
                            "validation" => array("allowed_extensions" => array("pdf", "doc", "docx", "xls", "xlsx", "jpg", "jpeg", "png", "zip")),
                            "extras" => array("id" => "proposal_files_uploader")
                        )); ?>
                        
                        <div id="proposal-files-list" class="mt-3">
                            <?php echo $proposal_files_html ?? '<p class="text-muted">' . app_lang('proposals_no_files') . '</p>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Salvar anotações
    $('#save-notes-btn').click(function() {
        var notes = $('#proposal-notes').val();
        $.ajax({
            url: '<?php echo_uri("propostas/save_notes"); ?>',
            type: 'POST',
            data: {
                id: <?php echo $proposal_info->id ?? 0; ?>,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    appAlert.success('<?php echo app_lang("record_saved"); ?>');
                }
            }
        });
    });
    
    // Carregar eventos de follow-up
    loadFollowupEvents();
});

function loadFollowupEvents() {
    $.ajax({
        url: '<?php echo_uri("events/proposal_events/" . ($proposal_info->id ?? 0)); ?>',
        type: 'GET',
        success: function(html) {
            $('#followup-events-list').html(html);
        }
    });
}
</script>