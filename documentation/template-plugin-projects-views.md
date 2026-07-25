# Template de views para novos plugins

Baseado na pasta `app/Views/projects`, este documento consolida os padroes de interface e comportamento que podem ser reaproveitados como modelo para novos plugins ou modulos internos.

## Objetivo

Usar a estrutura de `projects` como referencia para criar telas novas com a mesma experiencia visual e funcional do core do sistema.

O conjunto analisado mostra 5 blocos principais:

1. Lista principal com tabela server-side
2. Modal de criacao/edicao
3. View de detalhes com abas dinamicas
4. Subviews por funcionalidade
5. Widgets/cards reutilizaveis

## Estrutura da pasta

Organizacao observada em `app/Views/projects`:

- `index.php` para listagem principal
- `modal_form.php` para cadastro/edicao
- `details_view.php` para tela detalhada com tabs
- `overview.php` e `overview_for_client.php` para resumo
- `project_title_buttons.php` para acoes do cabecalho
- `custom_fields_list.php` para campos extras
- Subpastas por dominio:
  - `comments/`
  - `files/`
  - `tasks/`
  - `timesheets/`
  - `milestones/`
  - `payments/`
  - `invoices/`
  - `contracts/`
  - `tickets/`
  - `reports/`
  - `widgets/`
  - `star/`

## Padrao de tela principal

### Lista

`index.php` segue este formato:

- wrapper com `#page-content` e `page-wrapper clearfix`
- um `card` principal
- `page-title` no topo
- bloco `title-button-group` para acoes
- `table-responsive` com uma tabela vazia que sera preenchida por `appTable`

Exemplo de estrutura:

```php
<div id="page-content" class="page-wrapper clearfix">
    <div class="card grid-button">
        <div class="page-title clearfix projects-page">
            <h1><?php echo app_lang('projects'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(...); ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="project-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>
```

### Regras visuais observadas

- `card` e `table-responsive` sao a base visual
- botoes de acao ficam no topo, nunca espalhados pela pagina
- icones usam `data-feather`
- classes de largura sao padronizadas: `w50`, `w100`, `w150`, `w200`
- colunas de opcao usam `option w100` ou similar

## Padrao de tabela

As telas usam `appTable` quase sempre com:

- `source` apontando para uma rota AJAX
- `serverSide: true`
- filtros em `filterDropdown`, `multiSelect`, `singleDatepicker` e `rangeDatepicker`
- `columns` com ordem e `order_by`
- colunas ocultas para ordenacao
- colunas de exportacao com `printColumns` e `xlsColumns`
- `custom_fields_headers` e `custom_fields_filters` quando houver campos personalizados

### Regras para `appTable`

1. Use `source` com rota clara e previsivel.
2. Mantenha a primeira coluna oculta quando ela for usada apenas para cor/status.
3. Use colunas ocultas para ordenar campos exibidos em formato amigavel.
4. Separe filtros de dominio da entidade e filtros de usuario.
5. Inclua coluna final de opcoes quando houver editar/excluir.

### Exemplo de filtros

Nos arquivos analisados, os filtros aparecem assim:

- filtros por status
- filtros por label/categoria
- filtros por data
- filtros por responsavel ou membro
- filtros dinamicos baseados em settings

## Padrao de modal

`modal_form.php` mostra o layout ideal para formularios em modal.

### Estrutura obrigatoria

- `form_open(...)` com:
  - `id`
  - `class => general-form`
  - `role => form`
- `modal-body clearfix`
- `container-fluid`
- `modal-footer`
- botao `Close`
- botao `Save`

### Regras de formulario

- use `form-group` + `row`
- label em coluna fixa, normalmente `col-md-3`
- campo em `col-md-9`
- `form-control` para inputs e textarea
- `select2` para selects com busca ou relacao dinamica
- `data-rule-required` e `data-msg-required` quando o campo for obrigatorio
- `autocomplete="off"` em campos de data ou busca, quando fizer sentido

### Padroes especificos observados

- campos ocultos para `id`, `context`, `context_id`
- comportamento diferente conforme tipo de usuario
- alguns campos sao escondidos com `hide` ou preenchidos com `hidden input`
- o submit pode disparar fluxo encadeado, como abrir outro modal apos salvar

## Padrao da tela de detalhes

`details_view.php` e a referencia mais importante para modulos complexos.

### Estrutura

- `page-content project-details-view clearfix`
- `container-fluid`
- bloco de titulo com:
  - status icon
  - nome do registro
  - estrela de favorito
  - botoes de acao
- `nav nav-tabs` com `data-bs-toggle="ajax-tab"`
- `tab-content` com paineis vazios que sao carregados sob demanda

### Abas dinamicas

As abas sao montadas em PHP com base em:

- tipo de usuario: `staff` ou `client`
- permissao do usuario
- settings do sistema
- relacionamento do projeto com outros modulos

### Regra importante

O `details_view.php` nao carrega tudo de uma vez. Ele:

- monta a lista de tabs em PHP
- define a rota de cada tab
- deixa cada `<div class="tab-pane">` vazio
- carrega o conteudo via AJAX quando a aba e ativada

### Exemplo de logica reutilizavel

Para novos plugins, use o mesmo principio:

- uma view principal
- tabs condicionais
- rotas separadas por funcionalidade
- hooks para extensao futura

## Subviews por dominio

Os subdiretorios seguem o padrao de "uma funcionalidade = um conjunto de views".

### Exemplos observados

- `comments/`
  - formulario de comentario
  - lista de comentarios
  - respostas
  - like/unlike
- `files/`
  - lista
  - visualizacao
  - modal de upload
  - categorias
- `tasks/`
  - listagem
  - kanban
  - scripts comuns para tabela e leitura de comentarios
- `timesheets/`
  - lista
  - modal
  - resumo
  - grafico
  - seletor de parada de timer
- `milestones/`
  - listagem e modal
- `reports/`
  - resumos e visoes agregadas
- `widgets/`
  - cards pequenos para dashboard e overview

### Regra de organizacao

Se uma feature crescer, nao acumule tudo em um unico arquivo. Quebre em:

- `index.php` para a tela principal da feature
- `modal_form.php` para formulacao
- `*_list.php` para listas parciais
- `*_script.php` para JS comum

## Widgets e cards

Os widgets da pasta `widgets/` seguem um padrao simples:

- `card` como container
- header com icone e titulo
- conteudo compacto
- metricas resumidas e links para telas relacionadas

Use esse modelo para:

- dashboards
- resumos de status
- contadores
- graficos pequenos

## Custom fields

O arquivo `custom_fields_list.php` mostra como exibir campos extras:

- percorre `custom_fields_list`
- ignora itens sem valor
- renderiza o valor pelo `view("custom_fields/output_" . $data->field_type, ...)`
- mostra em bloco visual simples

### Regra para novos plugins

Se o modulo suportar campos extras:

- prepare uma view parcial especifica
- reutilize os renderers de `custom_fields/output_*`
- mantenha o mesmo padrao visual do core

## JS e comportamento dinamico

O JavaScript nas views de `projects` segue alguns principios:

### Inicializacao

- `$(document).ready(...)`
- `setTimeout(...)` apenas para acionar tab inicial ou foco
- `feather.replace()` quando o conteudo muda dinamicamente

### Comportamento apos salvar

- atualizar tabela com `appTable({newData: ..., dataId: ...})`
- recarregar a pagina quando necessario
- abrir o proximo modal em fluxos encadeados

### Tabs AJAX

- use `data-bs-target` em cada aba
- nao renderize tudo no carregamento inicial
- deixe o conteudo ser buscado quando a tab for aberta

### Filtros dinamicos

- use variaveis PHP para decidir se um filtro aparece
- monte arrays em JS com dados vindos do servidor
- respeite estado inicial salvo pelo usuario quando houver `smartFilterIdentity`

## Dependencias de contexto

Os templates de `projects` mostram forte dependencia de:

- `login_user`
- permissoes por perfil
- settings do sistema
- custom fields
- hooks de extensao
- rotas AJAX do controller

Em um novo plugin, isso significa:

1. Definir bem os dados enviados da controller para a view
2. Separar o que e publico, staff e client
3. Evitar colocar logica de negocio pesada dentro da view
4. Manter as regras de exibicao previsiveis

## Convencoes recomendadas para novos plugins

### Nomenclatura

- use nomes de view coerentes com a rota e o modulo
- prefira `index.php`, `modal_form.php`, `details_view.php`, `overview.php`
- para subviews, use o nome da feature: `comments`, `files`, `tasks`, `reports`

### Layout

- sempre comece com `page-wrapper clearfix` ou `page-content`
- use `card` como unidade visual principal
- mantenha botoes em `title-button-group`
- use `table-responsive` para tabelas

### Interacao

- para abrir modal, use `modal_anchor`
- para acao em lote, use `ajax_anchor` ou handlers JS explicitos
- para listagens, use `appTable`
- para carregamento por aba, use `ajax-tab`

### Exportacao

- se a tabela precisar exportar, defina `printColumns` e `xlsColumns`
- se houver custom fields, combine as colunas com helper dedicado

## Checklist rapido para criar um novo plugin

Antes de criar a view:

- definir rota principal
- definir tela de lista
- definir modal de cadastro
- definir tela de detalhes, se existir
- separar subviews por funcionalidade
- definir permissao por acao
- prever custom fields, se necessario
- prever exportacao, se necessario

Antes de finalizar a view:

- conferir classes visuais padrao
- validar ids de tabela e modal
- verificar se o `appTable` aponta para a rota correta
- confirmar se as tabs carregam via AJAX
- confirmar se os botoes aparecem apenas para quem pode usar

## Resumo pratico

Se o novo plugin quiser seguir o padrao de `projects`, ele deve ter:

- uma listagem principal com `appTable`
- um modal de edicao em `general-form`
- uma view de detalhes com tabs AJAX
- subpastas por dominio
- widgets pequenos para resumo
- custom fields quando aplicavel
- logica de permissao e visibilidade no PHP

