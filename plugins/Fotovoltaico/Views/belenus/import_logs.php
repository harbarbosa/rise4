<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_belenus_logs'); ?></h4>
        <div class="title-button-group float-end">
            <button type="button" class="btn btn-default" id="reload-belenus-logs-btn"><?php echo app_lang('refresh'); ?></button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="belenus-import-logs-table">
                    <thead>
                        <tr>
                            <th><?php echo app_lang('date'); ?></th>
                            <th>Tipo</th>
                            <th>Ação</th>
                            <th>Externo</th>
                            <th>Local</th>
                            <th>Status</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function () {
    function loadLogs() {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/logs/list_data'); ?>",
            type: "POST",
            dataType: "json",
            success: function (response) {
                var rows = [];
                if (response && response.data) {
                    $.each(response.data, function (_, row) {
                        rows.push("<tr>" +
                            "<td>" + row[0] + "</td>" +
                            "<td>" + row[1] + "</td>" +
                            "<td>" + row[2] + "</td>" +
                            "<td>" + row[3] + "</td>" +
                            "<td>" + row[4] + "</td>" +
                            "<td>" + row[5] + "</td>" +
                            "<td>" + row[6] + "</td>" +
                        "</tr>");
                    });
                }
                if (!rows.length) {
                    rows.push("<tr><td colspan='7' class='text-center text-muted'>Sem registros.</td></tr>");
                }
                $("#belenus-import-logs-table tbody").html(rows.join(""));
            }
        });
    }

    $("#reload-belenus-logs-btn").on("click", function () {
        loadLogs();
    });

    loadLogs();
});
</script>
