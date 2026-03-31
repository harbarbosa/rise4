<?php

namespace ContaAzul\Config;

use CodeIgniter\Config\BaseConfig;

class QueryEndpoints extends BaseConfig
{
    public array $endpoints = [
        'people_list' => [
            'label' => 'Pessoas por filtro',
            'path' => '/v1/pessoas',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-person/v1',
        ],
        'people_show' => [
            'label' => 'Pessoa por ID',
            'path' => '/v1/pessoas/{id}',
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-person/v1',
        ],
        'people_legacy_show' => [
            'label' => 'Pessoa por legacy ID',
            'path' => '/v1/pessoas/legado/{id}',
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-person/v1',
        ],
        'products_list' => [
            'label' => 'Produtos por filtro',
            'path' => '/v1/produtos',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1',
        ],
        'products_show' => [
            'label' => 'Produto por ID',
            'path' => '/v1/produtos/{id}',
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1/retornarprodutoporid',
        ],
        'product_categories_list' => [
            'label' => 'Categorias de produto',
            'path' => '/v1/produtos/categorias',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1',
        ],
        'product_cests_list' => [
            'label' => 'CESTs de produto',
            'path' => '/v1/produtos/cest',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1',
        ],
        'product_ncms_list' => [
            'label' => 'NCMs de produto',
            'path' => '/v1/produtos/ncm',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1/retornarncms',
        ],
        'product_units_list' => [
            'label' => 'Unidades de medida de produto',
            'path' => '/v1/produtos/unidades-medida',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1',
        ],
        'product_ecommerce_categories_list' => [
            'label' => 'Categorias de e-commerce',
            'path' => '/v1/produtos/ecommerce-categorias',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1',
        ],
        'product_ecommerce_brands_list' => [
            'label' => 'Marcas de e-commerce',
            'path' => '/v1/produtos/ecommerce-marcas',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1/retornarmarcasecommerce',
        ],
        'services_list' => [
            'label' => 'Servicos por filtro',
            'path' => '/v1/servicos',
            'docs_url' => 'https://developers.contaazul.com/aboutapis',
        ],
        'services_show' => [
            'label' => 'Servico por ID',
            'path' => '/v1/servicos/{id}',
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/aboutapis',
        ],
        'cost_centers_list' => [
            'label' => 'Centros de custo',
            'path' => '/v1/centro-de-custo',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchcostcenters',
        ],
        'categories_list' => [
            'label' => 'Categorias financeiras',
            'path' => '/v1/categorias',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchcategories',
        ],
        'dre_categories_list' => [
            'label' => 'Categorias DRE',
            'path' => '/v1/financeiro/categorias-dre',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchdrecategories',
        ],
        'financial_accounts_list' => [
            'label' => 'Contas financeiras',
            'path' => '/v1/conta-financeira',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchfinancialaccounts',
        ],
        'financial_account_balance' => [
            'label' => 'Saldo de conta financeira',
            'path' => '/v1/conta-financeira/{id_conta_financeira}/saldo-atual',
            'path_params' => ['id_conta_financeira'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchbalancebyfinancialaccountid',
        ],
        'payables_list' => [
            'label' => 'Despesas por filtro',
            'path' => '/v1/financeiro/eventos-financeiros/contas-a-pagar/buscar',
            'query_params' => [
                'pagina',
                'tamanho_pagina',
                'campo_ordenado_ascendente',
                'campo_ordenado_descendente',
                'descricao',
                'data_vencimento_de',
                'data_vencimento_ate',
                'data_competencia_de',
                'data_competencia_ate',
                'data_pagamento_de',
                'data_pagamento_ate',
                'data_alteracao_de',
                'data_alteracao_ate',
                'valor_de',
                'valor_ate',
                'status',
                'ids_contas_financeiras',
                'ids_categorias',
                'ids_centros_de_custo',
                'ids_clientes',
            ],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchinstallmentstopaybyfilter',
        ],
        'receivables_list' => [
            'label' => 'Receitas por filtro',
            'path' => '/v1/financeiro/eventos-financeiros/contas-a-receber/buscar',
            'query_params' => [
                'pagina',
                'tamanho_pagina',
                'campo_ordenado_ascendente',
                'campo_ordenado_descendente',
                'descricao',
                'data_vencimento_de',
                'data_vencimento_ate',
                'data_competencia_de',
                'data_competencia_ate',
                'data_pagamento_de',
                'data_pagamento_ate',
                'data_alteracao_de',
                'data_alteracao_ate',
                'valor_de',
                'valor_ate',
                'status',
                'ids_contas_financeiras',
                'ids_categorias',
                'ids_centros_de_custo',
                'ids_clientes',
            ],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchinstallmentstoreceivebyfilter',
        ],
        'event_installments_list' => [
            'label' => 'Parcelas por evento financeiro',
            'path' => '/v1/financeiro/eventos-financeiros/{id_evento}/parcelas',
            'path_params' => ['id_evento'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/getinstallmentsbyeventid',
        ],
        'event_changes_list' => [
            'label' => 'Alteracoes de eventos financeiros',
            'path' => '/v1/financeiro/eventos-financeiros/alteracoes',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/getalteredfinancialevents',
        ],
        'installment_show' => [
            'label' => 'Parcela por ID',
            'path' => '/v1/financeiro/eventos-financeiros/parcelas/{id}',
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/getinstallmentbyid',
        ],
        'sales_list' => [
            'label' => 'Vendas por filtro',
            'path' => '/v1/venda/busca',
            'docs_url' => 'https://developers.contaazul.com/docs/sales-apis-openapi/v1/searchvendas',
        ],
        'sales_show' => [
            'label' => 'Venda por ID',
            'path' => '/v1/venda/{id}',
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/docs/sales-apis-openapi/v1/searchvendas',
        ],
        'sales_sellers_list' => [
            'label' => 'Vendedores',
            'path' => '/v1/venda/vendedores',
            'docs_url' => 'https://developers.contaazul.com/docs/sales-apis-openapi/v1/searchvendas',
        ],
        'sales_items_list' => [
            'label' => 'Itens de venda',
            'path' => '/v1/venda/{id_venda}/itens',
            'path_params' => ['id_venda'],
            'docs_url' => 'https://developers.contaazul.com/docs/sales-apis-openapi/v1/searchvendas',
        ],
        'sales_next_number' => [
            'label' => 'Proximo numero de venda',
            'path' => '/v1/venda/proximo-numero',
            'docs_url' => 'https://developers.contaazul.com/docs/sales-apis-openapi/v1/getnextvendanumber',
        ],
        'contracts_list' => [
            'label' => 'Contratos por filtro',
            'path' => '/v1/contratos',
            'docs_url' => 'https://developers.contaazul.com/docs/contracts-apis-openapi/v1/searchcontracts',
        ],
        'contracts_next_number' => [
            'label' => 'Proximo numero de contrato',
            'path' => '/v1/contratos/proximo-numero',
            'docs_url' => 'https://developers.contaazul.com/docs/contracts-apis-openapi/v1/getnextcontractnumber',
        ],
        'invoices_list' => [
            'label' => 'Notas fiscais de produto',
            'path' => '/v1/notas-fiscais',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-invoice/v1',
        ],
        'service_invoices_list' => [
            'label' => 'Notas fiscais de servico',
            'path' => '/v1/notas-fiscais-servico',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-invoice/v1',
        ],
        'invoice_show' => [
            'label' => 'Nota fiscal por chave',
            'path' => '/v1/notas-fiscais/{chave}',
            'path_params' => ['chave'],
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-invoice/v1/obternotafiscalporchave',
        ],
        'transfers_list' => [
            'label' => 'Transferencias por periodo',
            'path' => '/v1/financeiro/transferencias',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi/v1/searchaccountingexporttransfers',
        ],
    ];
}
