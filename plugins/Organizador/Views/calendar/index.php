<?php
load_css(array(
    "assets/js/fullcalendar/fullcalendar.min.css"
));
load_js(array(
    "assets/js/fullcalendar/fullcalendar.min.js",
    "assets/js/fullcalendar/locales-all.min.js"
));
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('organizador_calendar'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('organizador_new_task'), array('class' => 'btn btn-primary', 'title' => app_lang('organizador_new_task'))); ?>
            </div>
        </div>
        <div class="card-body">
            <div id="organizador-calendar"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        window.organizadorRefreshAfterSave = function () {
            location.reload();
        };

        var calendarEl = document.getElementById('organizador-calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: AppLanugage.locale,
            height: isMobile() ? "auto" : $(window).height() - 210,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: "<?php echo_uri('organizador/tasks/calendar_data'); ?>",
            dayMaxEvents: false,
            loading: function (state) {
                if (!state) {
                    feather.replace();
                }
            },
        });
        calendar.render();
    });
</script>
