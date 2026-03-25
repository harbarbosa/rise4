<div class="clearfix">
    <div class="page-title clearfix">
        <h1><?php echo app_lang("revenues_expenses_section"); ?></h1>
    </div>
</div>

<div class="card mb15">
    <div class="card-header clearfix">
        <h4 class="mb0 pull-left">
            <?php echo app_lang("expenses_section"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Custos planejados por tarefa e tipo.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
        <div class="pull-right">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/cost_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_cost"), array("class" => "btn btn-default", "title" => app_lang("add_cost")));
            ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("date"); ?></th>
                    <th><?php echo app_lang("cost_type"); ?></th>
                    <th><?php echo app_lang("planned_value"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($task_costs)) { ?>
                    <?php foreach ($task_costs as $cost) { ?>
                        <tr>
                            <td><?php echo $cost->planned_date ? format_to_date($cost->planned_date, false) : "-"; ?></td>
                            <td><?php echo app_lang("cost_" . $cost->cost_type); ?></td>
                            <td><?php echo to_currency($cost->planned_value); ?></td>
                            <td class="text-center">
                                <?php
                                echo modal_anchor(get_uri("projectanalizer/evolucao/cost_modal_form/" . $project_id), "<i data-feather='edit' class='icon-16'></i>", array(
                                    "title" => app_lang("edit"),
                                    "data-post-id" => $cost->id
                                ));
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $cost->id,
                                    "data-action-url" => get_uri("projectanalizer/evolucao/delete_task_cost/" . $project_id),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("expenses_monthly_schedule"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Cronograma mensal de desembolso (planejado x realizado).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("month"); ?></th>
                    <th><?php echo app_lang("planned"); ?></th>
                    <th><?php echo app_lang("realized"); ?></th>
                    <th><?php echo app_lang("delta"); ?></th>
                    <th><?php echo app_lang("cumulative"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($cashflow_summary["labels"])) { ?>
                    <?php foreach ($cashflow_summary["labels"] as $index => $label) { ?>
                        <?php
                        $planned_month = get_array_value($cashflow_summary["expenses"]["planned_by_month"], $index, 0);
                        $realized_month = get_array_value($cashflow_summary["expenses"]["realized_by_month"], $index, 0);
                        $delta = $planned_month - $realized_month;
                        $cum = get_array_value($cashflow_summary["expenses"]["realized_cum"], $index, 0);
                        ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td><?php echo to_currency($planned_month); ?></td>
                            <td><?php echo to_currency($realized_month); ?></td>
                            <td><?php echo to_currency($delta); ?></td>
                            <td><?php echo to_currency($cum); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb15">
    <div class="card-header clearfix">
        <h4 class="mb0 pull-left">
            <?php echo app_lang("realized_costs"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Lancamentos de custos realizados no projeto.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
        <div class="pull-right">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/realized_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_realized"), array("class" => "btn btn-default", "title" => app_lang("add_realized")));
            ?>
        </div>
    </div>
    <div class="card-body">
        <?php
        $filter_cost_type = isset($filters["cost_type"]) ? $filters["cost_type"] : "";
        $filter_date_from = isset($filters["date_from"]) ? $filters["date_from"] : "";
        $filter_date_to = isset($filters["date_to"]) ? $filters["date_to"] : "";
        ?>
        <form id="realized-filter-form" class="mb15" method="GET" action="<?php echo_uri("projectanalizer/revenues_expenses/" . $project_id); ?>">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="date_from" class="form-control" placeholder="<?php echo app_lang("date_from"); ?>" value="<?php echo $filter_date_from; ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="date_to" class="form-control" placeholder="<?php echo app_lang("date_to"); ?>" value="<?php echo $filter_date_to; ?>">
                </div>
                <div class="col-md-3">
                    <?php
                    echo form_dropdown("cost_type", array(
                        "" => "-",
                        "material" => app_lang("cost_material"),
                        "mao_obra" => app_lang("cost_labor"),
                        "servico" => app_lang("cost_service"),
                        "terceiros" => app_lang("cost_third_party"),
                        "outros" => app_lang("cost_other")
                    ), $filter_cost_type, "class='form-control'");
                    ?>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-default" type="submit"><?php echo app_lang("filter"); ?></button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("date"); ?></th>
                    <th><?php echo app_lang("cost_type"); ?></th>
                    <th><?php echo app_lang("value"); ?></th>
                    <th><?php echo app_lang("description"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($realized_items)) { ?>
                    <?php foreach ($realized_items as $item) { ?>
                        <tr>
                            <td><?php echo format_to_date($item->date, false); ?></td>
                            <td><?php echo app_lang("cost_" . $item->cost_type); ?></td>
                            <td><?php echo to_currency($item->value); ?></td>
                            <td>
                                <?php
                                $is_ca_desc = isset($item->reference) && strpos((string)$item->reference, "ca:") === 0;
                                if ($is_ca_desc) {
                                    echo "<span class='badge bg-primary me-1'>CA</span>";
                                }
                                echo $item->description;
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                echo modal_anchor(get_uri("projectanalizer/evolucao/realized_modal_form/" . $project_id), "<i data-feather='edit' class='icon-16'></i>", array(
                                    "title" => app_lang("edit"),
                                    "data-post-id" => $item->id
                                ));
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $item->id,
                                    "data-action-url" => get_uri("projectanalizer/evolucao/delete_realized/" . $project_id),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb15">
    <div class="card-header clearfix">
        <h4 class="mb0 pull-left">
            <?php echo app_lang("revenues_section"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Receitas planejadas e realizadas do projeto.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
        <div class="pull-right">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_planned_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_planned_revenue"), array("class" => "btn btn-default", "title" => app_lang("add_planned_revenue")));
            echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_realized_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_realized_revenue"), array("class" => "btn btn-default", "title" => app_lang("add_realized_revenue")));
            ?>
        </div>
    </div>
    <div class="card-body">
        <h5 class="mt0"><?php echo app_lang("planned_revenues"); ?></h5>
        <div class="table-responsive mb20">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("revenue_title"); ?></th>
                    <th><?php echo app_lang("planned_date"); ?></th>
                    <th><?php echo app_lang("planned_value"); ?></th>
                    <th><?php echo app_lang("percent_of_contract"); ?></th>
                    <th><?php echo app_lang("notes"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($revenue_planned_items)) { ?>
                    <?php foreach ($revenue_planned_items as $item) { ?>
                        <tr>
                            <td><?php echo $item->title; ?></td>
                            <td><?php echo format_to_date($item->planned_date, false); ?></td>
                            <td><?php echo to_currency($item->planned_value); ?></td>
                            <td><?php echo $item->percent_of_contract ? number_format($item->percent_of_contract, 2) . "%" : "-"; ?></td>
                            <td><?php echo $item->notes; ?></td>
                            <td class="text-center">
                                <?php
                                echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_planned_modal_form/" . $project_id), "<i data-feather='edit' class='icon-16'></i>", array(
                                    "title" => app_lang("edit"),
                                    "data-post-id" => $item->id
                                ));
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $item->id,
                                    "data-action-url" => get_uri("projectanalizer/revenue/delete_planned"),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <h5 class="mt0"><?php echo app_lang("realized_revenues"); ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("realized_date"); ?></th>
                    <th><?php echo app_lang("realized_value"); ?></th>
                    <th><?php echo app_lang("planned_revenue"); ?></th>
                    <th><?php echo app_lang("document_ref"); ?></th>
                    <th><?php echo app_lang("notes"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($revenue_realized_items)) { ?>
                    <?php foreach ($revenue_realized_items as $item) { ?>
                        <tr>
                            <td><?php echo format_to_date($item->realized_date, false); ?></td>
                            <td><?php echo to_currency($item->realized_value); ?></td>
                            <td>
                                <?php
                                $planned_title = "-";
                                if (!empty($revenue_planned_items)) {
                                    foreach ($revenue_planned_items as $planned_item) {
                                        if ((int)$planned_item->id === (int)$item->planned_id) {
                                            $planned_title = $planned_item->title;
                                            break;
                                        }
                                    }
                                }
                                echo $planned_title;
                                ?>
                            </td>
                            <td><?php echo $item->document_ref; ?></td>
                            <td>
                                <?php
                                $is_ca_notes = isset($item->document_ref) && strpos((string)$item->document_ref, "ca:") === 0;
                                if ($is_ca_notes) {
                                    echo "<span class='badge bg-primary me-1'>CA</span>";
                                }
                                echo $item->notes;
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_realized_modal_form/" . $project_id), "<i data-feather='edit' class='icon-16'></i>", array(
                                    "title" => app_lang("edit"),
                                    "data-post-id" => $item->id
                                ));
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $item->id,
                                    "data-action-url" => get_uri("projectanalizer/revenue/delete_realized"),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("revenue_monthly_schedule"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Cronograma mensal de faturamento (planejado x realizado).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("month"); ?></th>
                    <th><?php echo app_lang("planned"); ?></th>
                    <th><?php echo app_lang("realized"); ?></th>
                    <th><?php echo app_lang("delta"); ?></th>
                    <th><?php echo app_lang("cumulative"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($cashflow_summary["labels"])) { ?>
                    <?php foreach ($cashflow_summary["labels"] as $index => $label) { ?>
                        <?php
                        $planned_month = get_array_value($cashflow_summary["revenue"]["planned_by_month"], $index, 0);
                        $realized_month = get_array_value($cashflow_summary["revenue"]["realized_by_month"], $index, 0);
                        $delta = $planned_month - $realized_month;
                        $cum = get_array_value($cashflow_summary["revenue"]["realized_cum"], $index, 0);
                        ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td><?php echo to_currency($planned_month); ?></td>
                            <td><?php echo to_currency($realized_month); ?></td>
                            <td><?php echo to_currency($delta); ?></td>
                            <td><?php echo to_currency($cum); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        window.refreshProjectRevenuesExpensesSection = function () {
            var target = $("#project-revenues_expenses_section-section");
            var url = "<?php echo get_uri('projectanalizer/revenues_expenses/' . $project_id); ?>";
            var query = $("#realized-filter-form").length ? $("#realized-filter-form").serialize() : "";

            if (target.length) {
                appLoader.show();
                $.ajax({
                    url: url + (query ? ("?" + query) : ""),
                    type: "GET",
                    success: function (result) {
                        target.html(result);
                        if (window.feather) {
                            feather.replace();
                        }
                    },
                    complete: function () {
                        appLoader.hide();
                    }
                });
                return;
            }

            window.location = url + (query ? ("?" + query) : "");
        };

        $('[data-bs-toggle="tooltip"]').tooltip();
        setDatePicker("input[name='date_from']");
        setDatePicker("input[name='date_to']");

        $(document).on("click", "a.delete[data-action='delete-confirmation']", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.attr("data-action-url");
            var id = $btn.attr("data-id");
            if (!url) {
                return;
            }
            if (!confirm("<?php echo app_lang('delete_confirmation_message'); ?>")) {
                return;
            }
            $.ajax({
                url: url,
                type: "POST",
                dataType: "json",
                data: {id: id},
                success: function (result) {
                    if (result && result.success) {
                        window.refreshProjectRevenuesExpensesSection();
                    } else {
                        appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });

        $("#realized-filter-form").on("submit", function (e) {
            e.preventDefault();
            var url = $(this).attr("action");
            var query = $(this).serialize();
            var target = $("#project-revenues_expenses_section-section");
            if (target.length) {
                appLoader.show();
                $.ajax({
                    url: url + (query ? ("?" + query) : ""),
                    type: "GET",
                    success: function (result) {
                        target.html(result);
                        if (window.feather) {
                            feather.replace();
                        }
                    },
                    complete: function () {
                        appLoader.hide();
                    }
                });
            } else {
                window.location = url + (query ? ("?" + query) : "");
            }
        });
    });
</script>
