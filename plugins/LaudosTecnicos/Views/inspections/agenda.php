<?php
load_css(array("assets/js/fullcalendar/fullcalendar.min.css"));
load_js(array("assets/js/fullcalendar/fullcalendar.min.js", "assets/js/fullcalendar/locales-all.min.js"));
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_inspections_agenda'); ?></h1>
        </div>
        <div class="card-body">
            <div id="inspection-calendar"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        window.inspectionCalendar = new FullCalendar.Calendar(document.getElementById('inspection-calendar'), {
            locale: AppLanugage.locale,
            height: isMobile() ? "auto" : $(window).height() - 210,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: "<?php echo_uri('laudostecnicos/inspecoes/agenda_events'); ?>",
            firstDay: AppHelper.settings.firstDayOfWeek,
            eventClick: function (info) {
                if (info.event && info.event.extendedProps && info.event.extendedProps.inspection_id) {
                    window.location = "<?php echo get_uri('laudostecnicos/inspecoes/view/'); ?>" + info.event.extendedProps.inspection_id;
                }
            }
        });
        window.inspectionCalendar.render();
    });
</script>
