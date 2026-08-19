<?php
$model_info = $model_info ?? (object) array();
$clients_dropdown = is_array($clients_dropdown ?? null) ? $clients_dropdown : array();
$projects_dropdown = is_array($projects_dropdown ?? null) ? $projects_dropdown : array();
$contacts_dropdown = is_array($contacts_dropdown ?? null) ? $contacts_dropdown : array();
$team_members_dropdown = is_array($team_members_dropdown ?? null) ? $team_members_dropdown : array();
$types_dropdown = is_array($types_dropdown ?? null) ? $types_dropdown : array();
$categories_dropdown = is_array($categories_dropdown ?? null) ? $categories_dropdown : array();
$templates_dropdown = is_array($templates_dropdown ?? null) ? $templates_dropdown : array();
$statuses_dropdown = is_array($statuses_dropdown ?? null) ? $statuses_dropdown : array();
$priority_dropdown = is_array($priority_dropdown ?? null) ? $priority_dropdown : array();
$units_dropdown = is_array($units_dropdown ?? null) ? $units_dropdown : array();
$team_values = array_filter(array_map('trim', explode(',', (string) ($model_info->inspection_team ?? ''))));
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('laudostecnicos/laudos/save'), array('id' => 'laudostecnicos-laudo-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
        <input type="hidden" name="is_template_based" id="is_template_based" value="<?php echo esc($model_info->is_template_based ?? 0); ?>">
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-tabs mb20" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#laudos-identificacao">Identificação</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudos-datas">Datas</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudos-responsaveis">Responsáveis</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudos-conteudo">Conteúdo técnico</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudos-complementar">Complementares</a></li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade show active" id="laudos-identificacao">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Número sequencial</label>
                                <input type="text" name="number" value="<?php echo esc($model_info->number ?? ''); ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Código personalizado</label>
                                <input type="text" name="custom_code" value="<?php echo esc($model_info->custom_code ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Revisão</label>
                                <input type="text" name="revision" value="<?php echo esc($model_info->revision ?? '00'); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Título</label>
                                <input type="text" name="title" value="<?php echo esc($model_info->title ?? ''); ?>" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de laudo</label>
                                <?php echo form_dropdown('type_id', $types_dropdown, $model_info->type_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoria</label>
                                <?php echo form_dropdown('category_id', $categories_dropdown, $model_info->category_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Template aplicado</label>
                                <?php echo form_dropdown('template_id', $templates_dropdown, $model_info->template_id ?? '', "class='form-select' id='template_id'"); ?>
                                <small class="text-muted d-block mt-1">Se vazio, o template padrÃ£o do tipo serÃ¡ aplicado no salvamento.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <?php echo form_dropdown('status', $statuses_dropdown, $model_info->status ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cliente</label>
                                <?php echo form_dropdown('client_id', $clients_dropdown, $model_info->client_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contato</label>
                                <?php echo form_dropdown('contact_id', $contacts_dropdown, $model_info->contact_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Projeto</label>
                                <?php echo form_dropdown('project_id', $projects_dropdown, $model_info->project_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contrato</label>
                                <input type="number" name="contract_id" value="<?php echo esc($model_info->contract_id ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ordem de serviço</label>
                                <input type="number" name="service_order_id" value="<?php echo esc($model_info->service_order_id ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unidade</label>
                                <?php echo form_dropdown('unit_name', $units_dropdown, $model_info->unit_name ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Endereço</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo esc($model_info->address ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Local da inspeção</label>
                                <textarea name="inspection_location" class="form-control" rows="2"><?php echo esc($model_info->inspection_location ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prioridade</label>
                                <?php echo form_dropdown('priority', $priority_dropdown, $model_info->priority ?? 'normal', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Referência externa</label>
                                <input type="text" name="external_reference" value="<?php echo esc($model_info->external_reference ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Confidencialidade</label>
                                <input type="text" name="confidentiality" value="<?php echo esc($model_info->confidentiality ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Centro de custo</label>
                                <input type="text" name="cost_center" value="<?php echo esc($model_info->cost_center ?? ''); ?>" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade" id="laudos-datas">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Solicitação</label>
                                <input type="date" name="request_date" value="<?php echo esc($model_info->request_date ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Agendamento</label>
                                <input type="date" name="scheduled_date" value="<?php echo esc($model_info->scheduled_date ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Visita</label>
                                <input type="date" name="visit_date" value="<?php echo esc($model_info->visit_date ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inspeção</label>
                                <input type="date" name="inspection_date" value="<?php echo esc($model_info->inspection_date ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Emissão</label>
                                <input type="date" name="issue_date" value="<?php echo esc($model_info->issue_date ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Validade</label>
                                <input type="date" name="validity_date" value="<?php echo esc($model_info->validity_date ?? ''); ?>" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade" id="laudos-responsaveis">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Responsável comercial</label>
                                <?php echo form_dropdown('commercial_responsible_id', $team_members_dropdown, $model_info->commercial_responsible_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inspetor</label>
                                <?php echo form_dropdown('technical_responsible_id', $team_members_dropdown, $model_info->technical_responsible_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Revisor</label>
                                <?php echo form_dropdown('reviewer_id', $team_members_dropdown, $model_info->reviewer_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Aprovador</label>
                                <?php echo form_dropdown('approver_id', $team_members_dropdown, $model_info->approver_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Equipe de inspeção</label>
                                <select name="inspection_team_users[]" id="inspection_team_users" class="form-select" multiple>
                                    <?php foreach ($team_members_dropdown as $value => $text) { ?>
                                        <?php $team_member_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text))); ?>
                                        <option value="<?php echo esc($value); ?>" <?php echo in_array((string) $value, $team_values, true) ? 'selected' : ''; ?>>
                                            <?php echo esc($team_member_label); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <input type="hidden" name="inspection_team" id="inspection_team" value="<?php echo esc($model_info->inspection_team ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade" id="laudos-conteudo">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Objetivo</label>
                                <textarea name="objective" class="form-control" rows="4"><?php echo esc($model_info->objective ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Escopo</label>
                                <textarea name="scope" class="form-control" rows="4"><?php echo esc($model_info->scope ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metodologia</label>
                                <textarea name="methodology" class="form-control" rows="4"><?php echo esc($model_info->methodology ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Premissas</label>
                                <textarea name="premises" class="form-control" rows="4"><?php echo esc($model_info->premises ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Limitações</label>
                                <textarea name="limitations" class="form-control" rows="4"><?php echo esc($model_info->limitations ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descrição da instalação</label>
                                <textarea name="installation_description" class="form-control" rows="4"><?php echo esc($model_info->installation_description ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Resultados</label>
                                <textarea name="results" class="form-control" rows="4"><?php echo esc($model_info->results ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Diagnóstico</label>
                                <textarea name="diagnosis" class="form-control" rows="4"><?php echo esc($model_info->diagnosis ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Conclusão</label>
                                <textarea name="conclusion" class="form-control" rows="4"><?php echo esc($model_info->conclusion ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Recomendações</label>
                                <textarea name="recommendations" class="form-control" rows="4"><?php echo esc($model_info->recommendations ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Observações internas</label>
                                <textarea name="internal_notes" class="form-control" rows="4"><?php echo esc($model_info->internal_notes ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade" id="laudos-complementar">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" value="<?php echo esc($model_info->tags ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número de proposta</label>
                                <input type="text" name="proposal_number" value="<?php echo esc($model_info->proposal_number ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número de contrato</label>
                                <input type="text" name="contract_number" value="<?php echo esc($model_info->contract_number ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Observações para o cliente</label>
                                <textarea name="client_observations" class="form-control" rows="4"><?php echo esc($model_info->client_observations ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" id="save-laudo-btn" class="btn btn-primary">Salvar</button>
</div>

<script type="text/javascript">
    $(function () {
        $("#save-laudo-btn").on("click", function () {
            $("#inspection_team").val($("#inspection_team_users").val() ? $("#inspection_team_users").val().join(",") : "");
            $("#is_template_based").val($("#template_id").val() ? 1 : 0);
            $("#laudostecnicos-laudo-form").trigger("submit");
        });

        $("#template_id").on("change", function () {
            $("#is_template_based").val($(this).val() ? 1 : 0);
        });

        $("#laudostecnicos-laudo-form").appForm({
            onSuccess: function (result) {
                if (result && result.redirect_to) {
                    window.location = result.redirect_to;
                    return;
                }

                if (result && result.success) {
                    if ($(".dataTable:visible").length) {
                        $(".dataTable:visible").appTable({newData: result.data, dataId: result.id});
                    }
                }
            }
        });
    });
</script>
