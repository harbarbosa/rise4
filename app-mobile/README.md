# AlfaHP Mobile

Objetivo:

Aplicativo para equipes de campo da AlfaHP Tecnologia realizarem acompanhamento de projetos, lançamentos de atividades, apontamento de horas e despesas de viagem.

Tecnologia:

- React

- Typescript

- Tailwind

- Mobile First

- PWA

- Estrutura preparada para Capacitor

Tema visual:

- Azul escuro #003B8E

- Branco

- Cinza claro

- Visual corporativo moderno

- Interface semelhante a Monday, ClickUp e Asana

Criar navegação inferior com:

1. Início

2. Agenda

3. Projetos

4. Despesas

5. Perfil

Utilizar cards modernos, sombras suaves e ícones Lucide.

O aplicativo é executado localmente e se integra à API REST do RiseCRM.

## Development

## Desenvolvimento local

Requisitos: Node.js 20+ ou Bun 1.3+.

```sh
bun install
bun run dev
```

Abra http://127.0.0.1:5173.

O proxy local encaminha as chamadas `/api/*` para `https://intranet.alfahp.com.br/index.php`. Para gerar o build web/Capacitor:

```sh
bun run build
npx cap sync
```

As variáveis locais podem ser colocadas em `.env.local`, que não deve ser versionado.
