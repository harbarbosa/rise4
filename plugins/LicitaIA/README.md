# LicitaIA

LicitaIA e um plugin para o Rise CRM voltado a gestao de oportunidades de licitacoes publicas.

Ele foi estruturado para seguir o padrao nativo do sistema, usando Controllers, Models, Views, Migrations, permissões, modais, DataTables e componentes visuais ja existentes no Rise CRM.

## Recursos

- Dashboard principal
- Cadastro e importacao de oportunidades
- Busca de editais no PNCP
- Analise de edital com IA
- Checklist documental
- Palavras-chave de inclusao e exclusao
- Fontes de busca
- Geracao de parecer tecnico em HTML e PDF
- Integracao com tarefas nativas
- Alertas internos e base para e-mail/WhatsApp

## Como instalar

1. Copie a pasta `LicitaIA` para `plugins/LicitaIA`.
2. Garanta que o Rise CRM tenha permissao para gravar em `writable/`.
3. Acesse o sistema com um usuario administrador.
4. Abra o plugin pelo menu do Rise CRM.
5. As migrations sao executadas automaticamente pelo plugin ao carregar o modulo.

Se estiver usando um fluxo manual de instalacao, execute tambem os scripts de instalacao do plugin quando aplicavel.

## Como configurar a API de IA

1. Acesse `LicitaIA > Configuracoes`.
2. Preencha os campos de IA:
   - Provedor
   - Modelo
   - URL base da API
   - Chave da API
3. Habilite a analise por IA.
4. Salve as configuracoes.

Observacao:
- O plugin espera uma API compativel com chat/completions.
- O retorno da IA deve ser JSON valido no formato definido pelo modulo.

## Como cadastrar palavras-chave

1. Acesse `LicitaIA > Palavras-chave`.
2. Clique em `Nova palavra-chave`.
3. Preencha:
   - Palavra-chave
   - Categoria
   - Tipo: Inclusao ou Exclusao
   - Peso
   - Ativa/Inativa
4. Salve.

Regras:
- Inclusao: ajuda a identificar editais aderentes.
- Exclusao: ajuda a ignorar editais irrelevantes.
- O peso fica reservado para rankings futuros.

## Como importar edital manual

1. Acesse `LicitaIA > Oportunidades`.
2. Clique em `Nova oportunidade` para cadastrar manualmente, ou
3. Use `LicitaIA > Buscar editais` para localizar resultados no PNCP e importar os selecionados.

## Como buscar no PNCP

1. Acesse `LicitaIA > Buscar editais`.
2. Informe filtros opcionais:
   - Palavra-chave
   - UF
   - Data inicial
   - Data final
   - Fonte
3. Clique em `Buscar`.
4. Revise os resultados e clique em `Importar selecionados`.

O processo usa as palavras-chave ativas de inclusao e exclusao para filtrar os resultados.

## Como rodar o cron

Busca PNCP:

```bash
php index.php licitaia/search/run_cron
```

Alertas:

```bash
php index.php licitaia/alerts/run_cron
```

Tambem e possivel chamar as rotas via GET:

- `licitaia/search/run_cron`
- `licitaia/alerts/run_cron`

## Como gerar parecer

1. Abra uma oportunidade em `LicitaIA > Oportunidades`.
2. Acesse a tela de detalhes.
3. Clique em `Gerar parecer`.
4. O sistema gera a versao HTML e salva o PDF vinculado a oportunidade.
5. Use o botao de download para baixar o arquivo.

## Permissoes

O plugin usa o sistema de permissões do Rise CRM para controlar:

- Visualizacao do modulo
- Gestao de oportunidades
- Gestao de palavras-chave
- Gestao de fontes
- Gestao de configuracoes
- Gestao de configuracoes de IA
- Execucao de analise de IA
- Geracao de parecer
- Gestao de checklist
- Exclusao de registros

## Observacoes tecnicas

- Compativel com CodeIgniter 4.
- Usa PHP 8+.
- Usa MySQL/MariaDB.
- Nao altera o core do Rise CRM quando nao e necessario.
- Mantem compatibilidade com componentes nativos do sistema.

