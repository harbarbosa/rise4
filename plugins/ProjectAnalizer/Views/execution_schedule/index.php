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
        <div class="bg-light rounded p15 mb15">
            <div class="row">
                <div class="col-md-3 mb10">
                    <label class="text-off mb5"><?php echo app_lang("execution_schedule_date_from"); ?></label>
                    <?php echo form_input(array(
                        "id" => "execution-schedule-date-from",
                        "class" => "form-control",
                        "type" => "date",
                        "value" => $selected_date_from
                    )); ?>
                </div>
                <div class="col-md-3 mb10">
                    <label class="text-off mb5"><?php echo app_lang("execution_schedule_date_to"); ?></label>
                    <?php echo form_input(array(
                        "id" => "execution-schedule-date-to",
                        "class" => "form-control",
                        "type" => "date",
                        "value" => $selected_date_to
                    )); ?>
                </div>
                <div class="col-md-3 mb10<?php echo $project_id ? " hide" : ""; ?>">
                    <label class="text-off mb5"><?php echo app_lang("project"); ?></label>
                    <?php echo form_input(array(
                        "id" => "execution-schedule-project-filter",
                        "class" => "select2 w-100"
                    )); ?>
                </div>
                <div class="col-md-3 mb10">
                    <label class="text-off mb5"><?php echo app_lang("member"); ?></label>
                    <?php echo form_input(array(
                        "id" => "execution-schedule-member-filter",
                        "class" => "select2 w-100"
                    )); ?>
                </div>
            </div>
        </div>
        <div class="row mb15">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="text-off"><?php echo app_lang("execution_schedule_not_allocated_today"); ?></div>
                        <h3 class="mt10 mb0" id="execution-schedule-unallocated-today"><?php echo (int) get_array_value($availability_summary["totals"], "unallocated_today"); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="text-off"><?php echo app_lang("execution_schedule_not_allocated_week"); ?></div>
                        <h3 class="mt10 mb0" id="execution-schedule-unallocated-week"><?php echo (int) get_array_value($availability_summary["totals"], "unallocated_week"); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="text-off"><?php echo app_lang("execution_schedule_not_allocated_period"); ?></div>
                        <h3 class="mt10 mb0" id="execution-schedule-unallocated-period"><?php echo (int) get_array_value($availability_summary["totals"], "unallocated_period"); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb15 text-off">
            <?php echo app_lang("execution_schedule_helper_text"); ?>
        </div>
        <div class="card mb15">
            <div class="card-body">
                <div class="mb10"><strong><?php echo app_lang("execution_schedule_unallocated_list"); ?></strong></div>
                <div id="execution-schedule-unallocated-list">
                    <?php if (!empty($availability_summary["unallocated_members"])) { ?>
                        <?php foreach ($availability_summary["unallocated_members"] as $member) { ?>
                            <span class="badge bg-light text-dark mr5 mb5"><?php echo esc($member["name"]); ?></span>
                        <?php } ?>
                    <?php } else { ?>
                        <span class="text-off"><?php echo app_lang("execution_schedule_no_unallocated_members"); ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div id="execution-schedule-calendar"></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var selectedProjectId = "<?php echo (int) $project_id; ?>";
        var selectedMemberId = "";
        var selectedDateFrom = $("#execution-schedule-date-from").val();
        var selectedDateTo = $("#execution-schedule-date-to").val();
        var $calendarEl = document.getElementById("execution-schedule-calendar");

        var getEventsUrl = function (fetchInfo) {
            return "<?php echo get_uri("projectanalizer/execution_schedule_events"); ?>?" + $.param({
                project_id: selectedProjectId || "",
                user_id: selectedMemberId || "",
                start: selectedDateFrom || moment(fetchInfo.start).format("YYYY-MM-DD"),
                end: selectedDateTo || moment(fetchInfo.end).subtract(1, "day").format("YYYY-MM-DD")
            });
        };

        var updateAvailabilitySummary = function () {
            appAjaxRequest({
                url: "<?php echo get_uri("projectanalizer/execution_schedule_availability_summary"); ?>",
                type: "GET",
                dataType: "json",
                data: {
                    project_id: selectedProjectId || "",
                    date_from: selectedDateFrom || "",
                    date_to: selectedDateTo || ""
                },
                success: function (response) {
                    if (!response || !response.success) {
                        return;
                    }

                    var data = response.data || {};
                    var totals = data.totals || {};
                    var members = data.unallocated_members || [];

                    $("#execution-schedule-unallocated-today").text(totals.unallocated_today || 0);
                    $("#execution-schedule-unallocated-week").text(totals.unallocated_week || 0);
                    $("#execution-schedule-unallocated-period").text(totals.unallocated_period || 0);

                    if (!members.length) {
                        $("#execution-schedule-unallocated-list").html("<span class='text-off'><?php echo app_lang("execution_schedule_no_unallocated_members"); ?></span>");
                        return;
                    }

                    var html = "";
                    $.each(members, function (index, member) {
                        html += "<span class='badge bg-light text-dark mr5 mb5'>" + $("<div>").text(member.name || "").html() + "</span>";
                    });

                    $("#execution-schedule-unallocated-list").html(html);
                }
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
            data: <?php echo json_encode($projects_dropdown); ?>,
            width: "100%"
        }).on("change", function () {
            selectedProjectId = $(this).val() || "";
            window.executionScheduleCalendar.refetchEvents();
            updateAvailabilitySummary();
        });

        $("#execution-schedule-member-filter").select2({
            data: <?php echo json_encode($members_dropdown); ?>,
            width: "100%"
        }).on("change", function () {
            selectedMemberId = $(this).val() || "";
            window.executionScheduleCalendar.refetchEvents();
        });

        $("#execution-schedule-date-from, #execution-schedule-date-to").on("change", function () {
            selectedDateFrom = $("#execution-schedule-date-from").val();
            selectedDateTo = $("#execution-schedule-date-to").val();
            window.executionScheduleCalendar.refetchEvents();
            updateAvailabilitySummary();
        });

        if (selectedProjectId) {
            $("#execution-schedule-project-filter").val(selectedProjectId).trigger("change");
        }

        updateAvailabilitySummary();
    });
</script>
