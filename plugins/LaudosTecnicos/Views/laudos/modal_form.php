<?php
$id = get_array_value($model_info, 'id');
$laudo_number = get_array_value($model_info, 'laudo_number');
$custom_code = get_array_value($model_info, 'custom_code');
$title = get_array_value($model_info, 'title');
$laudo_type_id = get_array_value($model_info, 'laudo_type_id');
$category_id = get_array_value($model_info, 'category_id');
$client_id = get_array_value($model_info, 'client_id');
$project_id = get_array_value($model_info, 'project_id');
$contact_id = get_array_value($model_info, 'contact_id');
$contract_id = get_array_value($model_info, 'contract_id');
$address = get_array_value($model_info, 'address');
$city = get_array_value($model_info, 'city');
$state = get_array_value($model_info, 'state');
$location = get_array_value($model_info, 'location');
$priority = get_array_value($model_info, 'priority') ?: 'normal';
$description = get_array_value($model_info, 'description');

$request_date = get_array_value($model_info, 'request_date');
$scheduled_date = get_array_value($model_info, 'scheduled_date');
$inspection_date = get_array_value($model_info, 'inspection_date');

$commercial_responsible_id = get_array_value($model_info, 'commercial_responsible_id');
$technician_id = get_array_value($model_info, 'technician_id');
$reviewer_id = get_array_value($model_info, 'reviewer_id');
$approver_id = get_array_value($model_info, 'approver_id');

$objective = get_array_value($model_info, 'objective');
$scope = get_array_value($model_info, 'scope');
$methodology = get_array_value($model_info, 'methodology');
$assumptions = get_array_value($model_info, 'assumptions');
$limitations = get_array_value($model_info, 'limitations');
$installation_description = get_array_value($model_info, 'installation_description');
$results = get_array_value($model_info, 'results');
$diagnosis = get_array_value($model_info, 'diagnosis');
$conclusion = get_array_value($model_info, 'conclusion');
$recommendations = get_array_value($model_info, 'recommendations');

$observations = get_array_value($model_info, 'observations');
$internal_notes = get_array_value($model_info, 'internal_notes');
$client_observations = get_array_value($model_info, 'client_observations');

$tags = get_array_value($model_info, 'tags');
$cost_center = get_array_value($model_info, 'cost_center');
$proposal_number = get_array_value($model_info, 'proposal_number');
$contract_number = get_array_value($model_info, 'contract_number');
$external_reference = get_array_value($model_info, 'external_reference');
$confidentiality = get_array_value($model_info, 'confidentiality');
?>

<form id="laudo-form" method="POST" class="dialog-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    
    <ul class="nav nav-tabs" id="laudo-tabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" href="#tab-identification" data-toggle="tab"><?php echo app_lang('laudos_tab_identification'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#tab-dates" data-toggle="tab"><?php echo app_lang('laudos_tab_dates'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#tab-team" data-toggle="tab"><?php echo app_lang('laudos_tab_team'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#tab-technical" data-toggle="tab"><?php echo app_lang('laudos_tab_technical'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#tab-observations" data-toggle="tab"><?php echo app_lang('laudos_tab_observations'); ?></a></li>
    </ul>

    <div class="tab-content p-3">
        <!-- ABA 1: IDENTIFICAÇÃO -->
        <div class="tab-pane fade show active" id="tab-identification">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_number'); ?></label>
                        <input type="text" class="form-control" value="<?php echo $laudo_number ?: app_lang('laudos_auto_generate'); ?>" disabled />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_custom_code'); ?></label>
                        <input type="text" name="custom_code" class="form-control" value="<?php echo $custom_code; ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_title'); ?> *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo $title; ?>" required />
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_type'); ?></label>
                        <?php echo form_dropdown('laudo_type_id', $types_dropdown, $laudo_type_id, "class='form-control' id='laudo_type_id'"); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_category'); ?></label>
                        <?php echo form_dropdown('category_id', $categories_dropdown, $category_id, "class='form-control'"); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('priority'); ?></label>
                        <select name="priority" class="form-control">
                            <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>><?php echo app_lang('laudos_priority_low'); ?></option>
                            <option value="normal" <?php echo $priority === 'normal' ? 'selected' : ''; ?>><?php echo app_lang('laudos_priority_normal'); ?></option>
                            <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>><?php echo app_lang('laudos_priority_high'); ?></option>
                            <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>><?php echo app_lang('laudos_priority_urgent'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_client'); ?></label>
                        <?php echo form_dropdown('client_id', $clients_dropdown, $client_id, "class='form-control'"); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('project'); ?></label>
                        <?php echo form_dropdown('project_id', $projects_dropdown, $project_id, "class='form-control'"); ?>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_address'); ?></label>
                        <input type="text" name="address" class="form-control" value="<?php echo $address; ?>" />
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_city'); ?></label>
                        <input type="text" name="city" class="form-control" value="<?php echo $city; ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_state'); ?></label>
                        <input type="text" name="state" class="form-control" value="<?php echo $state; ?>" />
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_location'); ?></label>
                        <input type="text" name="location" class="form-control" value="<?php echo $location; ?>" placeholder="Ex: Andar 2, Sala 201" />
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ABA 2: DATAS -->
        <div class="tab-pane fade" id="tab-dates">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_request_date'); ?></label>
                        <input type="date" name="request_date" class="form-control" value="<?php echo $request_date ?: date('Y-m-d'); ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_scheduled_date'); ?></label>
                        <input type="datetime-local" name="scheduled_date" class="form-control" value="<?php echo $scheduled_date; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_inspection_date'); ?></label>
                        <input type="date" name="inspection_date" class="form-control" value="<?php echo $inspection_date; ?>" />
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ABA 3: EQUIPE -->
        <div class="tab-pane fade" id="tab-team">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_commercial_responsible'); ?></label>
                        <?php echo form_dropdown('commercial_responsible_id', $team_dropdown, $commercial_responsible_id, "class='form-control'"); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_technician'); ?></label>
                        <?php echo form_dropdown('technician_id', $team_dropdown, $technician_id, "class='form-control'"); ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_reviewer'); ?></label>
                        <?php echo form_dropdown('reviewer_id', $team_dropdown, $reviewer_id, "class='form-control'"); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_approver'); ?></label>
                        <?php echo form_dropdown('approver_id', $team_dropdown, $approver_id, "class='form-control'"); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ABA 4: CONTEÚDO TÉCNICO -->
        <div class="tab-pane fade" id="tab-technical">
            <div class="form-group">
                <label><?php echo app_lang('laudos_objective'); ?></label>
                <textarea name="objective" class="form-control" rows="2"><?php echo $objective; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_scope'); ?></label>
                <textarea name="scope" class="form-control" rows="2"><?php echo $scope; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_methodology'); ?></label>
                <textarea name="methodology" class="form-control" rows="2"><?php echo $methodology; ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_assumptions'); ?></label>
                        <textarea name="assumptions" class="form-control" rows="2"><?php echo $assumptions; ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_limitations'); ?></label>
                        <textarea name="limitations" class="form-control" rows="2"><?php echo $limitations; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_installation_description'); ?></label>
                <textarea name="installation_description" class="form-control" rows="3"><?php echo $installation_description; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_results'); ?></label>
                <textarea name="results" class="form-control" rows="3"><?php echo $results; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_diagnosis'); ?></label>
                <textarea name="diagnosis" class="form-control" rows="3"><?php echo $diagnosis; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_conclusion'); ?></label>
                <textarea name="conclusion" class="form-control" rows="3"><?php echo $conclusion; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_recommendations'); ?></label>
                <textarea name="recommendations" class="form-control" rows="3"><?php echo $recommendations; ?></textarea>
            </div>
        </div>
        
        <!-- ABA 5: OBSERVAÇÕES -->
        <div class="tab-pane fade" id="tab-observations">
            <div class="form-group">
                <label><?php echo app_lang('description'); ?></label>
                <textarea name="description" class="form-control" rows="3"><?php echo $description; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_observations'); ?></label>
                <textarea name="observations" class="form-control" rows="3"><?php echo $observations; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_internal_notes'); ?></label>
                <textarea name="internal_notes" class="form-control" rows="3"><?php echo $internal_notes; ?></textarea>
            </div>
            <div class="form-group">
                <label><?php echo app_lang('laudos_client_observations'); ?></label>
                <textarea name="client_observations" class="form-control" rows="2"><?php echo $client_observations; ?></textarea>
            </div>
            
            <hr />
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_tags'); ?></label>
                        <input type="text" name="tags" class="form-control" value="<?php echo $tags; ?>" placeholder="tag1, tag2, tag3" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_cost_center'); ?></label>
                        <input type="text" name="cost_center" class="form-control" value="<?php echo $cost_center; ?>" />
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_proposal_number'); ?></label>
                        <input type="text" name="proposal_number" class="form-control" value="<?php echo $proposal_number; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_contract_number'); ?></label>
                        <input type="text" name="contract_number" class="form-control" value="<?php echo $contract_number; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_external_reference'); ?></label>
                        <input type="text" name="external_reference" class="form-control" value="<?php echo $external_reference; ?>" />
                    </div>
                </div>
            </div>
            
            <div class="form-check mt-3">
                <?php echo form_checkbox('confidentiality', '1', $confidentiality ? true : false, "id='confidentiality' class='form-check-input'"); ?>
                <label for="confidentiality"><?php echo app_lang('laudos_confidential'); ?></label>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#laudo-form').appForm({
        onSuccess: function(response) {
            if (typeof updateLaudosTable === 'function') {
                updateLaudosTable();
            }
            $('#laudos-table').appTable({reload: true});
        }
    });
    
    // Atualizar categorias quando mudar o tipo
    $('#laudo_type_id').change(function() {
        var type_id = $(this).val();
        // Aqui você pode adicionar lógica para filtrar categorias por tipo
    });
});
</script>