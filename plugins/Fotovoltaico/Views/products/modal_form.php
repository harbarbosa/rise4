<?php
$item = $item ?? null;
$types = $types ?? array();
$specs = $item->specs ?? array();
?>

<?php echo form_open(get_uri('fotovoltaico/products_save'), array('id' => 'fv-product-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $item->id ?? ''; ?>" />

    <ul class="nav nav-tabs bg-white title" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#fv-basic-tab"><?php echo app_lang('fv_basic_data'); ?></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#fv-specs-tab"><?php echo app_lang('fv_specs'); ?></a></li>
    </ul>

    <div class="tab-content pt15">
        <div class="tab-pane fade active show" id="fv-basic-tab">
            <div class="form-group">
                <label for="type"><?php echo app_lang('type'); ?></label>
                <?php echo form_dropdown('type', $types, $item->type ?? 'module', "class='select2' id='type' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "'"); ?>
            </div>

            <div class="form-group">
                <label for="brand"><?php echo app_lang('brand'); ?></label>
                <?php echo form_input(array('id' => 'brand', 'name' => 'brand', 'value' => $item->brand ?? '', 'class' => 'form-control', 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required'))); ?>
            </div>

            <div class="form-group">
                <label for="model"><?php echo app_lang('model'); ?></label>
                <?php echo form_input(array('id' => 'model', 'name' => 'model', 'value' => $item->model ?? '', 'class' => 'form-control', 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required'))); ?>
            </div>

            <div class="form-group">
                <label for="sku"><?php echo app_lang('sku'); ?></label>
                <?php echo form_input(array('id' => 'sku', 'name' => 'sku', 'value' => $item->sku ?? '', 'class' => 'form-control')); ?>
            </div>

            <div class="form-group">
                <label for="power_w"><?php echo app_lang('power_w'); ?></label>
                <?php echo form_input(array('id' => 'power_w', 'name' => 'power_w', 'value' => $item->power_w ?? '', 'class' => 'form-control')); ?>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="cost"><?php echo app_lang('cost'); ?></label>
                        <?php echo form_input(array('id' => 'cost', 'name' => 'cost', 'value' => $item->cost ?? '', 'class' => 'form-control')); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="price"><?php echo app_lang('price'); ?></label>
                        <?php echo form_input(array('id' => 'price', 'name' => 'price', 'value' => $item->price ?? '', 'class' => 'form-control')); ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="warranty_years"><?php echo app_lang('warranty'); ?></label>
                <?php echo form_input(array('id' => 'warranty_years', 'name' => 'warranty_years', 'value' => $item->warranty_years ?? '', 'class' => 'form-control')); ?>
            </div>

            <div class="form-group">
                <label for="datasheet_url">Datasheet URL</label>
                <?php echo form_input(array('id' => 'datasheet_url', 'name' => 'datasheet_url', 'value' => $item->datasheet_url ?? '', 'class' => 'form-control')); ?>
            </div>

            <div class="form-group">
                <label>
                    <?php echo form_checkbox('is_active', '1', isset($item->is_active) ? (bool)$item->is_active : true); ?>
                    <?php echo app_lang('active'); ?>
                </label>
            </div>
        </div>

        <div class="tab-pane fade" id="fv-specs-tab">
            <div class="fv-specs fv-specs-module">
                <h5 class="mb10"><?php echo app_lang('fv_specs_module'); ?></h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Pmpp (W)</label>
                            <input type="text" class="form-control" name="specs[pmpp]" value="<?php echo esc($specs['pmpp'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Vmpp (V)</label>
                            <input type="text" class="form-control" name="specs[vmpp]" value="<?php echo esc($specs['vmpp'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Impp (A)</label>
                            <input type="text" class="form-control" name="specs[impp]" value="<?php echo esc($specs['impp'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Voc (V)</label>
                            <input type="text" class="form-control" name="specs[voc]" value="<?php echo esc($specs['voc'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Isc (A)</label>
                            <input type="text" class="form-control" name="specs[isc]" value="<?php echo esc($specs['isc'] ?? ''); ?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Coef temp Pmax (%/°C)</label>
                            <input type="text" class="form-control" name="specs[coef_pmax]" value="<?php echo esc($specs['coef_pmax'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Coef temp Voc (%/°C)</label>
                            <input type="text" class="form-control" name="specs[coef_voc]" value="<?php echo esc($specs['coef_voc'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Eficiência (%)</label>
                            <input type="text" class="form-control" name="specs[efficiency]" value="<?php echo esc($specs['efficiency'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>NOCT (°C)</label>
                            <input type="text" class="form-control" name="specs[noct]" value="<?php echo esc($specs['noct'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Bifacial</label>
                            <?php echo form_dropdown('specs[bifacial]', array('0' => app_lang('no'), '1' => app_lang('yes')), isset($specs['bifacial']) ? (string)$specs['bifacial'] : '0', "class='select2'"); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fv-specs fv-specs-inverter">
                <h5 class="mb10"><?php echo app_lang('fv_specs_inverter'); ?></h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Potência AC nominal (W)</label>
                            <input type="text" class="form-control" name="specs[ac_power]" value="<?php echo esc($specs['ac_power'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Potência DC máx (W)</label>
                            <input type="text" class="form-control" name="specs[dc_power_max]" value="<?php echo esc($specs['dc_power_max'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Vdc máx (V)</label>
                            <input type="text" class="form-control" name="specs[vdc_max]" value="<?php echo esc($specs['vdc_max'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Faixa MPPT min (V)</label>
                            <input type="text" class="form-control" name="specs[mppt_min]" value="<?php echo esc($specs['mppt_min'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Faixa MPPT max (V)</label>
                            <input type="text" class="form-control" name="specs[mppt_max]" value="<?php echo esc($specs['mppt_max'] ?? ''); ?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nº MPPT</label>
                            <input type="text" class="form-control" name="specs[mppt_count]" value="<?php echo esc($specs['mppt_count'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Strings por MPPT</label>
                            <input type="text" class="form-control" name="specs[strings_per_mppt]" value="<?php echo esc($specs['strings_per_mppt'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Corrente máx por MPPT (A)</label>
                            <input type="text" class="form-control" name="specs[current_max_mppt]" value="<?php echo esc($specs['current_max_mppt'] ?? ''); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Eficiência (%)</label>
                            <input type="text" class="form-control" name="specs[efficiency]" value="<?php echo esc($specs['efficiency'] ?? ''); ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="fv-specs fv-specs-service">
                <h5 class="mb10"><?php echo app_lang('fv_specs_service'); ?></h5>
                <div class="form-group">
                    <label><?php echo app_lang('fv_tech_description'); ?></label>
                    <textarea class="form-control" name="specs_description" rows="4"><?php echo esc($specs['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();

        function toggleSpecs() {
            var type = $("#type").val();
            $(".fv-specs").hide();
            if (type === "module") {
                $(".fv-specs-module").show();
            } else if (type === "inverter") {
                $(".fv-specs-inverter").show();
            } else {
                $(".fv-specs-service").show();
            }
        }

        toggleSpecs();
        $("#type").on("change", toggleSpecs);

        $("#fv-product-form").appForm({
            onSuccess: function (result) {
                $("#fv-products-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
