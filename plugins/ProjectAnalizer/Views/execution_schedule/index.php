<?php
load_css(array(
    "assets/js/fullcalendar/fullcalendar.min.css"
));

load_js(array(
    "assets/js/fullcalendar/fullcalendar.min.js",
    "assets/js/fullcalendar/locales-all.min.js"
));

$page_title = isset($project_info) && $project_info ? $project_info->title : app_lang("execution_schedule");
?>
<div class="card full-width-button">
    <div class="page-title clearfix">
        <h1><?php echo $page_title; ?></h1>
        <div class="title-button-group">
            <?php echo form_input(array(
                "id" => "execution-schedule-project-filter",
                "class" => "select2 w250 mr10" . ($project_id ? " hide" : "")
            )); ?>
            <?php echo form_input(array(
                "id" => "execution-schedule-member-filter",
                "class" => "select2 w250 mr10"
            )); ?>
            <?php echo modal_anchor(get_uri("projectanalizer/execution_schedule_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add"), array(
                "class" => "btn btn-default",
                "title" => app_lang("execution_schedule"),
                "data-post-project_id" => $project_id
            )); ?>
        </div>
    </div>

    <?php echo modal_anchor(get_uri("projectanalizer/execution_schedule_modal_form"), "", array(
        "class" => "hide",
        "id" => "show-execution-schedule-modal",
        "title" => app_lang("execution_schedule")
    )); ?>

    <div class="card-body">
        <div class="mb15 text-off">
            <?php echo app_lang("execution_schedule_helper_text"); ?>
        </div>
        <div id="execution-schedule-calendar"></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var selectedProjectId = "<?php echo (int) $project_id; ?>";
        var selectedMemberId = "";
        var $calendarEl = document.getElementById("execution-schedule-calendar");

        var getEventsUrl = function (fetchInfo) {
            return "<?php echo get_uri("projectanalizer/execution_schedule_events"); ?>?" + $.param({
                project_id: selectedProjectId || "",
                user_id: selectedMemberId || "",
                start: moment(fetchInfo.start).format("YYYY-MM-DD"),
                end: moment(fetchInfo.end).subtract(1, "day").format("YYYY-MM-DD")
            });
        };

        window.executionScheduleCalendar = new FullCalendar.Calendar($calendarEl, {
            locale: AppLanugage.locale,
            height: isMobile() ? "auto" : $(window).height() - 210,
            initialView: "dayGridMonth",
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,listMonth"
            },
            firstDay: AppHelper.settings.firstDayOfWeek,
            events: function (fetchInfo, successCallback, failureCallback) {
                appAjaxRequest({
                    url: getEventsUrl(fetchInfo),
                    type: "GET",
                    dataType: "json",
                    success: successCallback,
                    error: failureCallback
                });
            },
            dateClick: function (info) {
                $("#show-execution-schedule-modal")
                    .attr("data-post-id", 0)
                    .attr("data-post-project_id", selectedProjectId || "")
                    .attr("data-post-start_date", moment(info.date).format("YYYY-MM-DD"))
                    .attr("data-post-end_date", moment(info.date).format("YYYY-MM-DD"))
                    .trigger("click");
            },
            eventClick: function (info) {
                $("#show-execution-schedule-modal")
                    .attr("data-post-id", info.event.id)
                    .attr("data-post-project_id", info.event.extendedProps.project_id || selectedProjectId || "")
                    .trigger("click");
            },
            eventContent: function (arg) {
                return {
                    html: "<div class='fc-event-title-container'><div class='fc-event-title fc-sticky'>" + $("<div>").text(arg.event.title).html() + "</div></div>"
                };
            },
            eventDidMount: function (info) {
                var tooltipLines = [];

                if (info.event.extendedProps.project_title) {
                    tooltipLines.push(info.event.extendedProps.project_title);
                }

                if (info.event.extendedProps.member_names && info.event.extendedProps.member_names.length) {
                    tooltipLines.push("Equipe: " + info.event.extendedProps.member_names.join(", "));
                }

                if (info.event.extendedProps.notes) {
                    tooltipLines.push("Obs: " + info.event.extendedProps.notes);
                }

                info.el.setAttribute("title", tooltipLines.join("\n"));
            },
            loading: function (isLoading) {
                if (!isLoading) {
                    $(".fc-prev-button").html("<i data-feather='chevron-left' class='icon-16'></i>");
                    $(".fc-next-button").html("<i data-feather='chevron-right' class='icon-16'></i>");
                    feather.replace();
                }
            }
        });

        window.executionScheduleCalendar.render();

        $("#execution-schedule-project-filter").select2({
            data: <?php echo json_encode($projects_dropdown); ?>
        }).on("change", function () {
            selectedProjectId = $(this).val() || "";
            window.executionScheduleCalendar.refetchEvents();
        });

        $("#execution-schedule-member-filter").select2({
            data: <?php echo json_encode($members_dropdown); ?>
        }).on("change", function () {
            selectedMemberId = $(this).val() || "";
            window.executionScheduleCalendar.refetchEvents();
        });

        if (selectedProjectId) {
            $("#execution-schedule-project-filter").val(selectedProjectId).trigger("change");
        }
    });
</script>
