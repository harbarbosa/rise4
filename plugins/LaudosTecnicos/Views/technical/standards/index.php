<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Normas Técnicas</h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudo_technical/standard_form"), "<i data-feather='plus' class='icon-16'></i> Nova Norma", array("class" => "btn btn-primary", "title" => "Nova Norma")); ?>
            </div>
        </div>

        <div class="card-header">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Instituição</label>
                        <select id="filter_institution" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($institutions as $inst): ?>
                            <option value="<?php echo $inst->institution; ?>"><?php echo $inst->institution; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select id="filter_category" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->category; ?>"><?php echo $cat->category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="standards-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#standards-table").appTable({
        source: '<?php echo_uri("laudo_technical/standards_list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: '<?php echo app_lang("laudos_code"); ?>' },
            { title: '<?php echo app_lang("laudos_title"); ?>' },
            { title: '<?php echo app_lang("laudos_institution"); ?>' },
            { title: '<?php echo app_lang("laudos_category"); ?>' },
            { title: '<?php echo app_lang("laudos_year"); ?>' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w80" }
        ],
        order: [[1, 'asc']],
        filterParams: {
            institution: function() { return $('#filter_institution').val(); },
            category: function() { return $('#filter_category').val(); }
        }
    });
    
    $('#filter_institution, #filter_category').change(function() {
        table.reload({
            institution: $('#filter_institution').val(),
            category: $('#filter_category').val()
        });
    });
});
</script>