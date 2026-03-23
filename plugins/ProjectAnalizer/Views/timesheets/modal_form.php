<?php echo form_open_multipart(get_uri("projectanalizer/save_timelog"), [
    "id" => "timelog-form",
    "class" => "general-form",
    "role" => "form"
]); ?>


<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />

        <?php if (!$project_id) { ?>
            <div class="form-group">
                <div class="row">
                    <label for="project_id" class=" col-md-3"><?php echo app_lang('project'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_dropdown("project_id", $projects_dropdown, array(), "class='select2 validate-hidden' id='project_id' data-rule-required='true', data-msg-required='" . app_lang('field_required') . "'");
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($team_members_info)) { ?>
            
            <div class="form-group">
                <div class="row">
                    <label for="applicant_id" class=" col-md-3"><?php echo app_lang('team_member'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        $image_url = get_avatar($team_members_info->image);
                        echo "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span>" . $team_members_info->first_name . " " . $team_members_info->last_name;
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>

       
        <?php if (!empty($model_info->id)) { ?>
            
    <div class="form-group">
        <div class="row">
            <label for="collaborators" class="col-md-3">
                <?php echo app_lang('collaborators'); ?>
            </label>
            <div class="col-md-9" id="dropdown-apploader-section">
                <?php
                echo form_input(array(
                    "id" => "collaborators",
                    "name" => "user_id",
                    "value" => $model_info->user_id, // ✅ adiciona value só quando há dado
                    "class" => "form-control",
                    "placeholder" => app_lang('collaborator')
                ));
                ?>
            </div>
        </div>
    </div>
<?php } else { ?>
    
    <div class="form-group">
        <div class="row">
            <label for="collaborators" class="col-md-3">
                <?php echo app_lang('collaborators'); ?>
            </label>
            <div class="col-md-9" id="dropdown-apploader-section">
                <?php
                echo form_input(array(
                    "id" => "collaborators",
                    "name" => "user_id",
                    "class" => "form-control",
                    "placeholder" => app_lang('collaborator')
                ));
                ?>
            </div>
        </div>
    </div>
<?php } ?>


            
            
        <?php if ((get_setting("users_can_input_only_total_hours_instead_of_period") && (!$model_info->id || $model_info->hours)) || (!get_setting("users_can_input_only_total_hours_instead_of_period") && $model_info->hours)) { ?>
            <div class="row">
                <label for="date" class=" col-md-3 col-sm-3"><?php echo app_lang('date'); ?></label>
                <div class="col-md-4 col-sm-4 form-group">
                    <?php
                    $in_time = is_date_exists($model_info->start_time) ? convert_date_utc_to_local($model_info->start_time) : "";

                    echo form_input(array(
                        "id" => "date",
                        "name" => "date",
                        "value" => $in_time ? date("Y-m-d", strtotime($in_time)) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('date'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
                <label for="hours" class=" col-md-2 col-sm-2"><?php echo app_lang('hours'); ?></label>
                <div class=" col-md-3 col-sm-3 form-group">
                    <?php
                    echo form_input(array(
                        "id" => "hours",
                        "name" => "hours",
                        "value" => $model_info->hours ? convert_hours_to_humanize_data($model_info->hours) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('timesheet_hour_input_help_message'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>

        <?php } else { ?>

            <div class="row">
                <label for="start_date" class=" col-md-3 col-sm-3"><?php echo app_lang('start_date'); ?></label>
                <div class="col-md-4 col-sm-4 form-group">
                    <?php
                    $in_time = is_date_exists($model_info->start_time) ? convert_date_utc_to_local($model_info->start_time) : "";

                    if ($time_format_24_hours) {
                        $in_time_value = $in_time ? date("H:i", strtotime($in_time)) : "";
                    } else {
                        $in_time_value = $in_time ? convert_time_to_12hours_format(date("H:i:s", strtotime($in_time))) : "";
                    }

                    echo form_input(array(
                        "id" => "start_date",
                        "name" => "start_date",
                        "value" => $in_time ? date("Y-m-d", strtotime($in_time)) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('start_date'),
                        "autocomplete" => "off",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
                <label for="start_time" class=" col-md-2 col-sm-2"><?php echo app_lang('start_time'); ?></label>
                <div class=" col-md-3 col-sm-3  form-group">
                    <?php
                    echo form_input(array(
                        "id" => "start_time",
                        "name" => "start_time",
                        "value" => $in_time_value,
                        "class" => "form-control",
                        "placeholder" => app_lang('start_time'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>

            <div class="row">
                <label for="end_date" class=" col-md-3 col-sm-3"><?php echo app_lang('end_date'); ?></label>
                <div class=" col-md-4 col-sm-4 form-group">
                    <?php
                    $out_time = is_date_exists($model_info->end_time) ? convert_date_utc_to_local($model_info->end_time) : "";

                    if ($time_format_24_hours) {
                        $out_time_value = $in_time ? date("H:i", strtotime($out_time)) : "";
                    } else {
                        $out_time_value = $in_time ? convert_time_to_12hours_format(date("H:i:s", strtotime($out_time))) : "";
                    }
                    echo form_input(array(
                        "id" => "end_date",
                        "name" => "end_date",
                        "value" => $out_time ? date("Y-m-d", strtotime($out_time)) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('end_date'),
                        "autocomplete" => "off",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                        "data-rule-greaterThanOrEqual" => "#start_date",
                        "data-msg-greaterThanOrEqual" => app_lang("end_date_must_be_equal_or_greater_than_start_date")
                    ));
                    ?>
                </div>
                <label for="end_time" class=" col-md-2 col-sm-2"><?php echo app_lang('end_time'); ?></label>
                <div class=" col-md-3 col-sm-3 form-group">
                    <?php
                    echo form_input(array(
                        "id" => "end_time",
                        "name" => "end_time",
                        "value" => $out_time_value,
                        "class" => "form-control",
                        "placeholder" => app_lang('end_time'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="note" class=" col-md-3"><?php echo app_lang('note'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "note",
                        "name" => "note",
                        "class" => "form-control",
                        "placeholder" => app_lang('note'),
                        "value" => process_images_from_content($model_info->note, false),
                        "data-rich-text-editor" => true
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="task_id" class=" col-md-3"><?php echo app_lang('task'); ?></label>
                <div class="col-md-9" id="dropdown-apploader-section">
                    <?php
                    echo form_input(array(
                        "id" => "task_id",
                        "name" => "task_id",
                        "value" => $model_info->task_id,
                        "class" => "form-control",
                        "placeholder" => app_lang('task')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group" id="percentage-executed-wrapper" style="display:none;">
            <div class="row">
                <label for="percentage_executed" class=" col-md-3">Percentual Executado</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "percentage_executed",
                        "name" => "percentage_executed",
                        "value" => isset($model_info->percentage_executed) ? $model_info->percentage_executed : "",
                        "class" => "form-control",
                        "type" => "number",
                        "min" => 0,
                        "max" => 100,
                        "step" => "0.01",
                        "placeholder" => "0.00"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
                <div class="row">
                    <label for="atividade_realizada" class=" col-md-3">Atividades Realizadas</label>
                    <div class=" col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "atividade_realizada",
                            "name" => "atividade_realizada",
                            "value" => $add_type == "multiple" ? "" : process_images_from_content($model_info->atividade_realizada, false),
                            "class" => "form-control",
                            "placeholder" => "Atividades Realizadas",
                            "data-rich-text-editor" => true
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="observacoes" class=" col-md-3">Observações </label>
                    <div class=" col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "observacoes",
                            "name" => "observacoes",
                            "value" => $add_type == "multiple" ? "" : process_images_from_content($model_info->observacoes, false),
                            "class" => "form-control",
                            "placeholder" => "Observações",
                            "data-rich-text-editor" => true
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                   <h5> Condições Climaticas </h5>
                    <div class="row">
            
                    <div class="col-md-10">
                    <label class="col-md-2" for="tempo_manha" >Manhã</label>
                    
                        <?php
                
                    
                    
                    
                        echo form_radio(array(
                            "id" => "option_2",
                            "name" => "tempo_manha",
                            "value" => "claro",
                            "checked" => (empty($model_info->project_id) || ($model_info->tempo_manha === "claro")),
                            
                            "class" => "form-check-input",
                        ));
                        
                        echo form_label("Claro", "option_2", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_manha",
                            "value" => "nublado",
                            "checked" => ($model_info->tempo_manha === "nublado"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("Nublado", "option_1", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_manha",
                            "value" => "chuvoso",
                            "checked" => ($model_info->tempo_manha == "chuvoso"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("Cluvoso", "option_1", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_manha",
                            "value" => "n/a",
                            "checked" => ($model_info->tempo_manha == "n/a"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("N/A", "option_1", array("class" => "form-check-label col-md-2"));
                        ?>

                        
                    </div>

                    
                
                

                </div>
           

                <div class="row">
                    
                    <div class="col-md-10">
                    <label class="col-md-2" for="impressao_total" >Tarde</label>
                        <?php
                        $option1 = false; // Define como false por padrão
                        $option2 = false; // Define como false por padrão
                        
                    
                    
                        echo form_radio(array(
                            "id" => "option_2",
                            "name" => "tempo_tarde",
                            "value" => "claro",
                            "checked" => (empty($model_info->project_id) || ($model_info->tempo_tarde === "claro")),
                            
                            "class" => "form-check-input",
                        ));
                        
                        echo form_label("Claro", "option_2", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_tarde",
                            "value" => "nublado",
                            "checked" => ($model_info->tempo_tarde == "nublado"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("Nublado", "option_1", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_tarde",
                            "value" => "chuvoso",
                            "checked" => ($model_info->tempo_tarde == "chuvoso"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("Cluvoso", "option_1", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_tarde",
                            "value" => "n/a",
                            "checked" => ($model_info->tempo_tarde == "n/a"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("N/A", "option_1", array("class" => "form-check-label col-md-2"));
                        ?>

                        
                    </div>

                    
                
                

                </div>

                <div class="row">
                    
                    <div class="col-md-10">
                    <label class="col-md-2" for="impressao_total" >Noite</label>
                        <?php
                        $option1 = false; // Define como false por padrão
                        $option2 = false; // Define como false por padrão
                        
                    
                    
                        echo form_radio(array(
                            "id" => "option_2",
                            "name" => "tempo_noite",
                            "value" => "claro",
                            "checked" => ($model_info->tempo_noite == "claro"),
                            
                            "class" => "form-check-input",
                        ));
                        
                        echo form_label("Claro", "option_2", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_noite",
                            "value" => "nublado",
                            "checked" => ($model_info->tempo_noite == "nublado"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("Nublado", "option_1", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_noite",
                            "value" => "chuvoso",
                            "checked" => ($model_info->tempo_noite == "chuvoso"),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("Cluvoso", "option_1", array("class" => "form-check-label col-md-2"));

                        echo form_radio(array(
                            "id" => "option_1",
                            "name" => "tempo_noite",
                            "value" => "n/a",
                            "checked" => (empty($model_info->project_id) || ($model_info->tempo_noite === "n/a")),
                            "class" => "form-check-input ",
                        ));
                        
                        echo form_label("N/A", "option_1", array("class" => "form-check-label col-md-2"));
                        ?>

                        
                    </div>

                    
                
                

                </div>

           

                <hr>
            </div>

                    <!-- Upload de fotos -->
            <div class="form-group">
                <div class="row">
                    <label class="col-md-3"><?php echo app_lang('photos'); ?></label>
                    <div class="col-md-9">
                        <input type="file"
                            name="photos[]"
                            id="photos"
                            class="form-control"
                            multiple
                            accept="image/*" />
                        <small class="text-muted"><?php echo app_lang("you_can_select_multiple_photos"); ?></small>

                        <!-- Preview -->
                        <div id="photo-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                    </div>
                </div>
            </div>

            <?php
                $Photos_model = new \ProjectAnalizer\Models\Photos_model();
                $photos = $Photos_model->get_by_timelog($model_info->id);
                ?>

                <?php if (!empty($photos)): ?>
                <hr>
                <div class="mt-3">
                    <h5>📸 Fotos Anexadas</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($photos as $photo): ?>
                            <div class="photo-item text-center" style="position: relative;">
                                <img src="<?= base_url($photo['file_path']); ?>"
                                    style="width:100px;height:100px;border-radius:6px;object-fit:cover;border:1px solid #ccc;">
                                <button type="button"
                                        class="btn btn-danger btn-sm delete-photo"
                                        data-id="<?= $photo['id']; ?>"
                                        style="position:absolute;top:-5px;right:-5px;padding:0 5px;">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
  
            

        <?php echo view("custom_fields/form/prepare_context_fields", array("custom_fields" => $custom_fields, "label_column" => "col-md-3", "field_column" => " col-md-9")); ?>

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#timelog-form").appForm({
            onSuccess: function (result) {
                var table = $(".dataTable:visible").attr("id");
                if (table === "project-timesheet-table" || table === "all-project-timesheet-table") {
                    $("#" + table).appTable({newData: result.data, dataId: result.id});
                }
            }
        });

        $("#timelog-form .select2").select2();

        //load all related data of the selected project
        $("#project_id").select2().on("change", function () {
            var projectId = $(this).val();
            if (projectId) {
                $('#user_id').select2("destroy");
                $("#user_id").hide();
                $('#task_id').select2("destroy");
                $("#task_id").hide();
                appLoader.show({container: "#dropdown-apploader-section"});
                appAjaxRequest({
                    url: "<?php echo get_uri('projects/get_all_related_data_of_selected_project_for_timelog') ?>" + "/" + projectId,
                    dataType: "json",
                    success: function (result) {
                        $("#user_id").show().val("");
                        $('#user_id').select2({data: result.project_members_dropdown});
                        $("#task_id").show().val("");
                        $('#task_id').select2({data: result.tasks_dropdown});
                        appLoader.hide();
                    }
                });
            }
        });

        //intialized select2 dropdown for first time
        $("#user_id").select2({data: <?php echo json_encode($project_members_dropdown); ?>});
        $("#task_id").select2({data: <?php echo $tasks_dropdown; ?>});

        function togglePercentageExecuted() {
            var taskValue = $("#task_id").val();
            var hasTask = taskValue && taskValue !== "";
            if (hasTask) {
                $("#percentage-executed-wrapper").show();
                $("#percentage_executed").prop("required", true);
            } else {
                $("#percentage-executed-wrapper").hide();
                $("#percentage_executed").prop("required", false).val("");
            }
        }

        $("#task_id").on("change select2:select", function () {
            togglePercentageExecuted();
        });

        togglePercentageExecuted();

        setDatePicker("#start_date, #end_date, #date");
        setTimePicker("#start_time, #end_time");

        $('[data-bs-toggle="tooltip"]').tooltip();

        const collaboratorsData = <?php echo json_encode($project_members_dropdown); ?>;

        const $collabSelect = $("#collaborators").select2({
        data: collaboratorsData,
        placeholder: "<?php echo app_lang('select_collaborators'); ?>",
        multiple: true,
        allowClear: true,
        width: "100%"
    });

    <?php if (!empty($model_info->collaborators)) : ?>
        let selectedValues = "<?php echo $model_info->collaborators; ?>".split(",");
        $collabSelect.val(selectedValues).trigger("change");
    <?php endif; ?>

    // 🔹 Sempre que mudar, atualiza o valor real do input para envio no form
    $collabSelect.on("change", function () {
        let selected = $(this).val() || [];
        $(this).val(selected.join(",")); // envia como "1,2,5"
    });


    });

    $("#photos").on("change", function (e) {
    const preview = $("#photo-preview");
    preview.empty();
    const files = e.target.files;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.append(
                `<div style="width:80px;height:80px;border:1px solid #ccc;border-radius:6px;overflow:hidden">
                    <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
                </div>`
            );
        };
        reader.readAsDataURL(file);
    }
});

// Excluir foto via AJAX
$(document).on("click", ".delete-photo", function () {
    const id = $(this).data("id");
    const el = $(this);

    if (confirm("Deseja realmente excluir esta foto?")) {
        appAjaxRequest({
            url: "<?php echo get_uri('projectanalizer/delete_photo'); ?>",
            type: "POST",
            data: {id: id},
            success: function (result) {
                if (result.success) {
                    el.closest(".photo-item").fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            }
        });
    }
});



</script>
