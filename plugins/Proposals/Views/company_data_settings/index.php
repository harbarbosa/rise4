<?php
$company_data = $company_data ?? array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "company_data";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h1><?php echo app_lang("company_data"); ?></h1>
                </div>
                <div class="card-body">
                    <?php echo form_open(get_uri("company_data_settings/save"), array("id" => "company-data-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_name" class="col-md-3"><?php echo app_lang("company_data_name"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_name" name="company_data_name" class="form-control" value="<?php echo esc($company_data["company_data_name"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_cnpj" class="col-md-3"><?php echo app_lang("company_data_cnpj"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_cnpj" name="company_data_cnpj" class="form-control" value="<?php echo esc($company_data["company_data_cnpj"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_email" class="col-md-3"><?php echo app_lang("company_data_email"); ?></label>
                            <div class="col-md-9">
                                <input type="email" id="company_data_email" name="company_data_email" class="form-control" value="<?php echo esc($company_data["company_data_email"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_phone" class="col-md-3"><?php echo app_lang("company_data_phone"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_phone" name="company_data_phone" class="form-control" value="<?php echo esc($company_data["company_data_phone"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_address" class="col-md-3"><?php echo app_lang("company_data_address"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_address" name="company_data_address" class="form-control" value="<?php echo esc($company_data["company_data_address"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_city" class="col-md-3"><?php echo app_lang("company_data_city"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_city" name="company_data_city" class="form-control" value="<?php echo esc($company_data["company_data_city"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_state" class="col-md-3"><?php echo app_lang("company_data_state"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_state" name="company_data_state" class="form-control" value="<?php echo esc($company_data["company_data_state"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_zip" class="col-md-3"><?php echo app_lang("company_data_zip"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_zip" name="company_data_zip" class="form-control" value="<?php echo esc($company_data["company_data_zip"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="company_data_website" class="col-md-3"><?php echo app_lang("company_data_website"); ?></label>
                            <div class="col-md-9">
                                <input type="text" id="company_data_website" name="company_data_website" class="form-control" value="<?php echo esc($company_data["company_data_website"] ?? ""); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo app_lang("save"); ?></button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#company-data-settings-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
            }
        });
    });
</script>
