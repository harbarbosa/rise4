# AssistenteIA para RISE CRM

Primeiro esqueleto do plugin. Ele implementa:

- histórico de conversas separado por `user_id`;
- validação de acesso ao plugin;
- configuração administrativa do token e do modelo OpenRouter;
- catálogo inicial de ferramentas condicionado às permissões do RISE;
- endpoint de conversa e interface inicial.

## Próxima integração necessária

As ferramentas reais devem ser implementadas em adaptadores do RISE, por exemplo `listar_projetos`, `listar_orcamentos` e `listar_ordens_servico`. Cada adaptador deve validar a permissão no servidor novamente antes de consultar o endpoint ou model.

O plugin não altera o núcleo. A migração preserva dados na desinstalação.
