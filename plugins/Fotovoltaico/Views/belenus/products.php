<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_belenus_products'); ?></h4>
        <div class="title-button-group float-end">
            <?php if ($can_manage_products && $can_manage_belenus) { ?>
                <button type="button" class="btn btn-primary" id="import-belenus-products-btn"><?php echo app_lang('fotovoltaico_belenus_sync_products'); ?></button>
            <?php } ?>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Busca</label>
                    <input type="text" class="form-control" id="belenus-products-q" placeholder="kit solar, inversor, painel">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" id="belenus-products-nome">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" id="belenus-products-codigo">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Página</label>
                    <input type="number" class="form-control" id="belenus-products-page" value="1" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Page size</label>
                    <input type="number" class="form-control" id="belenus-products-page-size" value="20" min="1" max="200">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-default w100p" id="search-belenus-products-btn">Buscar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="belenus-products-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>ID</th>
                            <th>SKU</th>
                            <th>Nome</th>
                            <th>Marca</th>
                            <th>Categoria</th>
                            <th class="text-end">Preço</th>
                            <th class="text-end">Promo</th>
                            <th class="text-end">Estoque</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="10" class="text-center text-muted">Use a busca para carregar produtos da API.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function () {
    function rowTemplate(item) {
        return "<tr>" +
            "<td><input type='checkbox' class='belenus-product-select' value='" + item.id + "'></td>" +
            "<td>" + (item.id || '') + "</td>" +
            "<td>" + (item.codigo || '') + "</td>" +
            "<td>" + (item.nome || '') + "</td>" +
            "<td>" + (item.marca || '') + "</td>" +
            "<td>" + (item.categoria || '') + "</td>" +
            "<td class='text-end'>" + (item.precoVenda || '') + "</td>" +
            "<td class='text-end'>" + (item.precoPromocional || '') + "</td>" +
            "<td class='text-end'>" + (item.estoque || '') + "</td>" +
            "<td>" + (item.ativo ? "Ativo" : "Inativo") + "</td>" +
        "</tr>";
    }

    $("#search-belenus-products-btn").on("click", function () {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/products/search'); ?>",
            type: "POST",
            dataType: "json",
            data: {
                q: $("#belenus-products-q").val(),
                nome: $("#belenus-products-nome").val(),
                codigo: $("#belenus-products-codigo").val(),
                page: $("#belenus-products-page").val(),
                pageSize: $("#belenus-products-page-size").val()
            },
            success: function (response) {
                var items = (response && response.data && response.data.items) ? response.data.items : [];
                if (!items.length && response && response.data && Array.isArray(response.data)) {
                    items = response.data;
                }
                var html = [];
                $.each(items, function (_, item) {
                    html.push(rowTemplate(item));
                });
                if (!html.length) {
                    html.push("<tr><td colspan='10' class='text-center text-muted'>Nenhum produto encontrado.</td></tr>");
                }
                $("#belenus-products-table tbody").html(html.join(""));
            }
        });
    });

    $("#import-belenus-products-btn").on("click", function () {
        var ids = [];
        $(".belenus-product-select:checked").each(function () {
            ids.push($(this).val());
        });
        if (!ids.length) {
            appAlert.error("Selecione ao menos um produto.");
            return;
        }

        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/products/import'); ?>",
            type: "POST",
            dataType: "json",
            data: { ids: ids.join(",") },
            success: function (response) {
                if (response && response.success) {
                    appAlert.success(response.message || "OK");
                } else {
                    appAlert.error((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }
        });
    });
});
</script>
