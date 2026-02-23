# Fotovoltaico

Plugin do Rise CRM para geração de propostas de sistemas fotovoltaicos.

## Instalação
1. Copie a pasta `Fotovoltaico` para `rise4/plugins/`.
2. No Rise CRM, vá em **Configurações > Plugins** e instale o plugin.
3. O instalador executa as migrations automaticamente.

## Migrations
As tabelas iniciais e ajustes são criados via migrations no namespace `Fotovoltaico`.

## Rotas principais
- `fotovoltaico/projects`
- `fotovoltaico/products`
- `fotovoltaico/kits`
- `fotovoltaico/utilities`
- `fotovoltaico/tariffs/{utility_id}`
- `fotovoltaico/settings`

## Wizard
O wizard inicial é acessado por:
`fotovoltaico/wizard/{project_id}/{step}`

## Integração CEC (catálogo externo)
### Configuração
1. Acesse **Fotovoltaico > Configurações > Catálogo Externo (CEC)**.
2. Ative a integração e ajuste as URLs se necessário:
   - Módulos: `https://solarequipment.energy.ca.gov/Download/ModuleList.csv`
   - Inversores: `https://solarequipment.energy.ca.gov/Download/InverterList.csv`
3. Escolha o modo de importação:
   - Somente inserir novos
   - Inserir e atualizar existentes
4. Salve.

### Execução manual
- Clique em **Testar download** para validar o acesso.
- Clique em **Executar sincronização agora** para importar.

### Logs
Na tela de integração, clique em **Ver logs** para visualizar execuções e resumo.

### Cron
Use o endpoint:
`fotovoltaico/cron/cec_sync?token=SEU_TOKEN`

O token fica disponível na tela de integração CEC. O cron não roda se:
- a integração estiver desativada
- existir execução em andamento nos últimos 30 minutos

### Observações
- Preços locais não são sobrescritos por padrão.
- Produtos importados recebem `source='cec'`.
- A integração pode desativar itens removidos se essa opção estiver marcada.
