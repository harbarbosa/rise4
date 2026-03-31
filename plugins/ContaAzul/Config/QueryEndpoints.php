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
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-inventory/v1/retornarprodutoporid',
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
            'label' => 'Serviços por filtro',
            'path' => '/v1/servicos',
            'fallback_paths' => ['/v1/servico'],
            'docs_url' => 'https://developers.contaazul.com/',
        ],
        'services_show' => [
            'label' => 'Serviço por ID',
            'path' => '/v1/servicos/{id}',
            'fallback_paths' => ['/v1/servico/{id}'],
            'path_params' => ['id'],
            'docs_url' => 'https://developers.contaazul.com/',
        ],
        'cost_centers_list' => [
            'label' => 'Centros de custo',
            'path' => '/v1/centro-de-custo',
            'fallback_paths' => ['/v1/centros-de-custo'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi',
        ],
        'categories_list' => [
            'label' => 'Categorias financeiras',
            'path' => '/v1/categorias',
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi',
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
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi',
        ],
        'payables_list' => [
            'label' => 'Despesas por filtro',
            'path' => '/v1/financeiro/eventos-financeiros/contas-a-pagar/buscar',
            'fallback_paths' => ['/v1/financeiro/contas-a-pagar', '/v1/contas-a-pagar'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi',
        ],
        'receivables_list' => [
            'label' => 'Receitas por filtro',
            'path' => '/v1/financeiro/eventos-financeiros/contas-a-receber/buscar',
            'fallback_paths' => ['/v1/financeiro/contas-a-receber', '/v1/contas-a-receber'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi',
        ],
        'event_installments_list' => [
            'label' => 'Parcelas por evento financeiro',
            'path' => '/v1/financeiro/eventos-financeiros/{id_evento}/parcelas',
            'path_params' => ['id_evento'],
            'docs_url' => 'https://developers.contaazul.com/docs/financial-apis-openapi',
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
            'label' => 'Próximo número de venda',
            'path' => '/v1/venda/proximo-numero',
            'docs_url' => 'https://developers.contaazul.com/docs/sales-apis-openapi/v1/searchvendas',
        ],
        'contracts_list' => [
            'label' => 'Contratos por filtro',
            'path' => '/v1/contratos',
            'docs_url' => 'https://developers.contaazul.com/docs/contracts-apis-openapi',
        ],
        'contracts_next_number' => [
            'label' => 'Próximo número de contrato',
            'path' => '/v1/contratos/proximo-numero',
            'docs_url' => 'https://developers.contaazul.com/docs/contracts-apis-openapi',
        ],
        'invoices_list' => [
            'label' => 'Notas fiscais de produto',
            'path' => '/v1/notas-fiscais',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-invoice',
        ],
        'service_invoices_list' => [
            'label' => 'Notas fiscais de serviço',
            'path' => '/v1/notas-fiscais-servico',
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-invoice',
        ],
        'invoice_show' => [
            'label' => 'Nota fiscal por chave',
            'path' => '/v1/notas-fiscais/{chave}',
            'path_params' => ['chave'],
            'docs_url' => 'https://developers.contaazul.com/open-api-docs/open-api-invoice/v1/obternotafiscalporchave',
        ],
        'transfers_list' => [
            'label' => 'Transferências por período',
            'path' => '/v1/financeiro/transferencias',
            'docs_url' => 'https://developers.contaazul.com/changelog',
        ],
    ];
}
