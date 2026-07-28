<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Agenda de Inspeções</h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudo_inspections/form"), "<i data-feather='plus' class='icon-16'></i> Nova Inspeção", array("class" => "btn btn-primary", "title" => "Nova Inspeção")); ?>
            </div>
        </div>

        <div class="card-header">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Técnico</label>
                        <?php echo form_dropdown('responsible_id', $team_dropdown, '', "class='form-control' id='filter_responsible'"); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>

<script>
var calendar;

$(document).ready(function() {
    var calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '<?php echo get_uri("laudo_inspections/calendar_data"); ?>',
        editable: true,
        selectable: true,
        eventClick: function(info) {
            window.location.href = info.event.url;
        },
        eventDrop: function(info) {
            // Atualizar data/hora via AJAX
            alert('Arraste não implementado nesta versão');
            info.revert();
        },
        height: 'auto'
    });
    
    calendar.render();
    
    $('#filter_responsible').change(function() {
        var refetch = function() {
            calendar.removeAllEvents();
            calendar.addEventSource('<?php echo get_uri("laudo_inspections/calendar_data"); ?>?responsible_id=' + $('#filter_responsible').val());
        };
        
        if (calendar) {
            refetch();
        } else {
            setTimeout(refetch, 100);
        }
    });
});
</script>