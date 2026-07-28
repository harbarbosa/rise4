<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>Automações</h1>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Schedule</th>
                                <th>Última Execução</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($automations as $a): ?>
                            <tr>
                                <td><?php echo $a->name; ?></td>
                                <td><?php echo $a->description; ?></td>
                                <td><code><?php echo $a->schedule; ?></code></td>
                                <td><?php echo $a->last_run_at ?? 'Nunca'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $a->is_active ? 'success' : 'secondary'; ?>">
                                        <?php echo $a->is_active ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-primary" onclick="runAutomation('<?php echo $a->code; ?>')">
                                        <i data-feather="play" class="icon-14"></i>
                                    </button>
                                    <button class="btn btn-xs btn-<?php echo $a->is_active ? 'warning' : 'success'; ?>" onclick="toggleAutomation(<?php echo $a->id; ?>)">
                                        <i data-feather="<?php echo $a->is_active ? 'pause' : 'play'; ?>" class="icon-14"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runAutomation(code) {
    appAlert.confirm('Executar esta automação agora?', function() {
        $.ajax({
            url: '<?php echo get_uri("laudo_automations/run/"); ?>' + code,
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    appAlert.success('Automação executada');
                    console.log(response.results);
                } else {
                    appAlert.error(response.message);
                }
            }
        });
    });
}

function toggleAutomation(id) {
    $.ajax({
        url: '<?php echo get_uri("laudo_automations/toggle/"); ?>' + id,
        type: 'POST',
        success: function() {
            location.reload();
        }
    });
}
</script>