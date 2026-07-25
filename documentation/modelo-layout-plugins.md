# Modelo de layout para plugins RISECRM

Este documento define o padrao visual e estrutural que os plugins devem seguir para manter a mesma experiencia de uso do RISECRM.

A referencia principal e a tela de clientes:

- `http://rise4.test/index.php/clients`
- `app/Views/clients/index.php`
- `app/Views/clients/clients_list.php`
- `app/Views/clients/modal_form.php`
- `app/Views/clients/client_form_fields.php`

Tambem foram usados como exemplo telas reais de plugins:

- `plugins/Fotovoltaico/Views/products/index.php`
- `plugins/Organizador/Views/tasks/index.php`
- `plugins/Organizador/Views/settings/index.php`
- `plugins/WhatsAppNotifications/Views/settings.php`

## Objetivo

O objetivo e evitar que cada plugin crie sua propria linguagem visual. A UI deve parecer parte nativa do RISECRM, com:

- estrutura de pagina consistente
- tabelas com o mesmo comportamento
- botoes no mesmo nivel de prioridade
- filtros e selects com largura e comportamento previsiveis
- modais e formularios com a mesma hierarquia visual

## Regras gerais

- Use `card` como container principal da maioria das telas.
- Use `page-wrapper clearfix` no nivel mais alto da pagina.
- Para paginas de listagem, use `page-title` no topo e `table-responsive` logo abaixo.
- Use `modal_anchor` para acoes que abrem formulario em modal.
- Use `appTable` para tabelas com listagem dinamica, filtros e ordenacao.
- Reaproveite classes e padroes ja usados pelo core do sistema.
- Evite inventar novos espacamentos, cores e estados visuais sem necessidade.

## Estrutura padrao de pagina

### Lista principal

Padrao recomendado:

```php
<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('titulo_da_pagina'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('plugin/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('add_item'))); ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="plugin-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>
```

### Quando a pagina tem abas

Use a mesma logica de `clients/index.php`:

- abas no topo com `nav nav-tabs`
- conteudo separado em `tab-content`
- botoes de acao no bloco `title-button-group`
- carregamento parcial por aba quando fizer sentido

### Quando ha dashboard e lista no mesmo contexto

Mantenha a pagina principal com:

- uma aba ou bloco de overview
- uma aba para lista
- uma aba para relacoes secundarias, como contatos ou documentos

## Tabelas

### Padrao visual

Tabelas do RISECRM devem seguir este desenho:

- container com `card`
- area rolavel com `table-responsive`
- tabela com classe `display`
- colunas definidas no `appTable`
- ultima coluna para opcoes com icone de menu

### `appTable`

Use `appTable` quando a tabela precisar de:

- busca
- ordenacao
- paginação
- filtros dinâmicos
- acao em lote
- exportacao
- reload apos salvar formulario

Exemplo:

```php
$("#plugin-table").appTable({
    source: '<?php echo_uri("plugin/list_data") ?>',
    order: [[0, 'asc']],
    filterDropdown: [
        {name: "status", class: "w150", options: <?php echo $status_dropdown; ?>},
        {name: "category_id", class: "w200", options: <?php echo $categories_dropdown; ?>}
    ],
    columns: [
        {title: "<?php echo app_lang('name') ?>", "class": "all"},
        {title: "<?php echo app_lang('status') ?>", "class": "text-center w100"},
        {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
    ]
});
```

### Largura e classes

Use classes conhecidas do sistema para manter consistencia:

- `w50`, `w100`, `w120`, `w150`, `w200`
- `text-center`
- `text-right`
- `option`
- `desktop`
- `all`

### Filtros

Filtros devem ficar no mesmo nivel da tabela e ser passados via `filterDropdown`.

Padrao recomendado:

- um placeholder de `quick_filters`
- filtros de dominio do plugin
- filtros de usuario ou categoria quando existirem
- estado inicial via `filterParams` quando a tela precisar abrir ja filtrada

## Botoes

### Prioridade visual

Ordem recomendada:

1. Acao primaria: `btn btn-primary`
2. Acao secundaria: `btn btn-default`
3. Acoes auxiliares em paginas de configuracao: `btn btn-outline-secondary`

### Regras

- Use um unico botao principal por fluxo.
- Evite varios botoes com o mesmo nivel de destaque.
- Prefira icone `feather` ao lado do texto quando o botao representar uma acao clara.
- Mantenha acoes de criacao, importacao e gerenciamento no bloco `title-button-group`.

### Exemplo de botoes

```php
<?php echo modal_anchor(get_uri('plugin/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-default', 'title' => app_lang('add_item'))); ?>
<?php echo modal_anchor(get_uri('plugin/import_modal_form'), "<i data-feather='upload' class='icon-16'></i> " . app_lang('import_items'), array('class' => 'btn btn-default', 'title' => app_lang('import_items'))); ?>
<?php echo anchor(get_uri('plugin/settings'), "<i data-feather='settings' class='icon-16'></i> " . app_lang('settings'), array('class' => 'btn btn-outline-secondary')); ?>
```

## Selects e filtros

### Lista

Filtros de tabela devem usar arrays no formato do `appTable`:

```php
array(
    array("id" => "", "text" => "- " . app_lang("status") . " -"),
    array("id" => "open", "text" => app_lang("open")),
    array("id" => "closed", "text" => app_lang("closed"))
)
```

### Formularios

Campos de select em formulario devem seguir o estilo do sistema:

- `form-control` como base
- `select2` quando houver busca, multiplos valores ou dados dinamicos
- label claro logo acima ou ao lado do campo, conforme o layout do formulario

Exemplo real no cliente:

- `#currency`, `#group_ids`, `#owner_id`, `#managers`, `#client_labels` usam `select2`

### Largura dos filtros

Para filtros de tabela, preserve larguras previsiveis:

- `w150` para status simples
- `w200` para categorias, grupos ou listas maiores
- `w100` apenas para seletores curtos

## Formularios e modais

### Estrutura padrao

Use a mesma estrutura do modal de clientes:

```php
<?php echo form_open(get_uri("plugin/save"), array("id" => "plugin-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <!-- campos -->
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
        <span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?>
    </button>
    <button type="submit" class="btn btn-primary">
        <span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?>
    </button>
</div>
<?php echo form_close(); ?>
```

### Regras de formulario

- Use `general-form` no `form_open`.
- Mantenha `modal-body clearfix` e `modal-footer`.
- Prefira `form-group` e `row` para formularios mais densos.
- Use `form-control` em inputs e textareas.
- Use `form-check` para toggles, checkboxes e switches.
- Use `input-group` quando houver campo com acao associada, como regenerar token.

### Comportamento JS

Depois de salvar um modal:

- recarregue a tabela relacionada com `appTable({reload: true})`, ou
- atualize a linha criada, ou
- abra o proximo modal do fluxo, se o processo for encadeado

Nao misture varios padroes de retorno na mesma tela sem necessidade.

## Estados e mensagens

### Loading e feedback

Use os mecanismos nativos do sistema:

- `appAlert.success(...)` para sucesso
- `appAlert.error(...)` para erro
- `$.get` e `$.post` com tratamento de erro quando houver integracao externa

### Vazio

Quando nao houver dados:

- preserve a estrutura da pagina
- nao remova o container principal
- deixe a area da tabela ou card vazia, mas visivelmente organizada

## Checklist de implementacao

Antes de considerar uma tela de plugin pronta, valide:

- o topo usa `page-wrapper` e `card`
- a pagina tem um titulo claro
- as acoes principais estao no `title-button-group`
- a listagem usa `appTable`
- os filtros usam larguras padrao
- modais usam `general-form`
- botoes seguem a prioridade visual do RISECRM
- selects dinamicos usam `select2` quando necessario
- o layout nao introduz componentes com estilo fora do padrao do sistema

## Regra final

Se houver duvida entre criar um visual novo ou repetir o padrao do sistema, repita o padrao do sistema.
