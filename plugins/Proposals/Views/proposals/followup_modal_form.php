<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?php echo app_lang('proposals_add_followup'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <?php echo form_open('propostas/save_followup', array('id' => 'proposal-followup-form', 'class' => 'dialog-form')); ?>
        <div class="modal-body">
            <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">
            <div class="form-group">
                <label for="title"><?php echo app_lang('title'); ?></label>
                <input type="text" name="title" id="title" class="form-control" value="[Proposta: <?php echo $proposal_id; ?>] Follow-up" required>
            </div>
            <div class="form-group">
                <label for="description"><?php echo app_lang('description'); ?></label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="event_date"><?php echo app_lang('date'); ?></label>
                <input type="datetime-local" name="event_date" id="event_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
            <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#proposal-followup-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#app-modal').modal('hide');
                        // Recarregar lista de follow-ups
                        var proposalId = <?php echo $proposal_id; ?>;
                        $.ajax({
                            url: '<?php echo_uri("propostas/get_followup_events/"); ?>' + proposalId,
                            type: 'GET',
                            success: function(resp) {
                                if (resp.success) {
                                    $('#followup-events-list').html(resp.html);
                                }
                            }
                        });
                        appAlert.success(response.message);
                    } else {
                        appAlert.error(response.message);
                    }
                }
            });
        });
    });
</script>