<?php
$statuses = is_array($statuses ?? null) ? $statuses : array();
$responsibles_dropdown = is_array($responsibles_dropdown ?? null) ? $responsibles_dropdown : array();
$clients_dropdown = is_array($clients_dropdown ?? null) ? $clients_dropdown : array();

$responsible_filter = array(array('id' => '', 'text' => 'Todos'));
foreach ($responsibles_dropdown as $id => $text) {
    $responsible_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text)));
    $responsible_filter[] = array('id' => (string) $id, 'text' => $responsible_label);
}

$client_filter = array(array('id' => '', 'text' => 'Todos'));
foreach ($clients_dropdown as $id => $text) {
    $client_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text)));
    $client_filter[] = array('id' => (string) $id, 'text' => $client_label);
}

$status_filter = array(array('id' => '', 'text' => 'Todos'));
foreach ($statuses as $id => $text) {
    $status_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text)));
    $status_filter[] = array('id' => (string) $id, 'text' => $status_label);
}

load_css(array("assets/js/fullcalendar/fullcalendar.min.css"));
load_js(array("assets/js/fullcalendar/fullcalendar.min.js", "assets/js/fullcalendar/locales-all.min.js"));
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h1><?php echo app_lang('laudostecnicos_inspections_title'); ?></h1>
        <div class="title-button-group">
            <?php if (!empty($can_manage_inspections)) { ?>
                <?php echo modal_anchor(get_uri('laudostecnicos/inspecoes/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_inspections_title'))); ?>
            <?php } ?>
        </div>
    </div>

    <ul class="nav nav-tabs scrollable-tabs rounded mb20" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#inspection-list-tab">Lista</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inspection-agenda-tab">Agenda</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="inspection-list-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="laudostecnicos-inspections-table" class="display" cellspacing="0" width="100%"></table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="inspection-agenda-tab">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><?php echo form_dropdown('agenda_status', $status_filter, '', "class='form-select' id='agenda-status'"); ?></div>
                        <div class="col-md-3"><?php echo form_dropdown('agenda_responsible', $responsible_filter, '', "class='form-select' id='agenda-responsible'"); ?></div>
                        <div class="col-md-3"><?php echo form_dropdown('agenda_client', $client_filter, '', "class='form-select' id='agenda-client'"); ?></div>
                        <div class="col-md-3">
                            <?php if (!empty($can_manage_inspections)) { ?>
                                <?php echo modal_anchor(get_uri('laudostecnicos/inspecoes/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Nova inspeção", array('class' => 'btn btn-outline-primary w-100', 'title' => app_lang('laudostecnicos_inspections_title'))); ?>
                            <?php } ?>
                        </div>
                    </div>
                    <div id="inspection-calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo modal_anchor(get_uri('laudostecnicos/inspecoes/modal_form'), '', array('class' => 'hide', 'id' => 'add-inspection-hidden', 'title' => app_lang('laudostecnicos_inspections_title'))); ?>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-inspections-table").appTable({
            source: '<?php echo_uri("laudostecnicos/inspecoes/list_data") ?>',
            columns: [
                {title: "Codigo", "class": "all"},
                {title: "Laudo", "class": "desktop"},
                {title: "Cliente", "class": "desktop"},
                {title: "Unidade", "class": "desktop"},
                {title: "Local", "class": "desktop"},
                {title: "Tipo", "class": "desktop"},
                {title: "Data", "class": "desktop"},
                {title: "Hora", "class": "desktop"},
                {title: "Duracao", "class": "desktop"},
                {title: "Responsavel", "class": "desktop"},
                {title: "Status", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w180"}
            ],
            order: [[6, 'desc'], [7, 'asc']]
        });

        function loadCalendar() {
            if (window.inspectionCalendar) {
                window.inspectionCalendar.destroy();
            }

            window.inspectionCalendar = new FullCalendar.Calendar(document.getElementById('inspection-calendar'), {
                locale: AppLanugage.locale,
                height: isMobile() ? "auto" : $(window).height() - 220,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                events: {
                    url: "<?php echo_uri('laudostecnicos/inspecoes/agenda_events'); ?>",
                    extraParams: function () {
                        return {
                            status: $("#agenda-status").val(),
                            responsible_id: $("#agenda-responsible").val(),
                            client_id: $("#agenda-client").val()
                        };
                    }
                },
                eventClick: function (info) {
                    if (info.event && info.event.extendedProps && info.event.extendedProps.inspection_id) {
                        window.location = "<?php echo get_uri('laudostecnicos/inspecoes/view/'); ?>" + info.event.extendedProps.inspection_id;
                    }
                },
                dateClick: function (info) {
                    if (!<?php echo !empty($can_manage_inspections) ? 'true' : 'false'; ?>) {
                        return;
                    }
                    $("#add-inspection-hidden").attr("data-post-inspection_date", moment(info.date).format("YYYY-MM-DD"));
                    $("#add-inspection-hidden").attr("data-post-start_time", moment(info.date).format("HH:mm"));
                    $("#add-inspection-hidden").attr("data-post-source", "agenda");
                    $("#add-inspection-hidden").trigger("click");
                },
                firstDay: AppHelper.settings.firstDayOfWeek
            });

            window.inspectionCalendar.render();
        }

        $("#agenda-status, #agenda-responsible, #agenda-client").on("change", function () {
            loadCalendar();
        });

        loadCalendar();
    });
</script>
