# Plugin Engenharia — MVP

## Instalação

1. Copie o diretório `Engenharia` para `plugins/`.
2. No RISE, abra **Configurações → Plugins** e instale/ative Engenharia.
3. Configure as permissões do papel em **Configurações → Papéis e permissões**.
4. Em **Engenharia → Configurações**, informe dados do relatório e cadastre instrumentos.

As migrations usam o namespace do plugin e o prefixo configurado pelo RISE. A desativação não remove dados; a desinstalação segue o instalador do plugin.

## Uso resumido

1. Cadastre uma versão publicada de checklist com grupos e itens.
2. Crie um laudo e selecione cliente, tipo e checklist.
3. Inicie a vistoria, registre respostas, áreas, medições, fotografias e não conformidades.
4. Envie para revisão pelo fluxo de status.
5. Finalize o laudo e gere o PDF final na aba **Relatório**.

PDFs de pré-visualização são identificados como rascunho. PDFs finais são versionados e armazenados em `files/engenharia/laudos/{id}`.

## Próxima etapa

Ficam fora deste MVP: Análise de Risco PDA, plano de ação completo, assinatura digital, editor avançado de modelos, otimização server-side de imagens e permissões granulares por cliente.
