# Testes de UI (Playwright)

Testes de interface do template **tpl_generico** (Joomla 5). Eles validam o
comportamento de UI que não dá para garantir só com `php -l`/SonarQube — hoje, o
**destaque do item de menu ativo**.

## Como funcionam (sem precisar de Joomla rodando)

Os testes **não** sobem uma instalação do Joomla. Em vez disso:

1. As fixtures em [`fixtures/`](fixtures) são HTML estáticas que **espelham
   exatamente a marcação** produzida pelos overrides de menu
   (`tpl_generico/html/mod_menu/*`).
2. Cada fixture referencia o **CSS real** do template
   (`tpl_generico/media/css/template.css`) e injeta as variáveis de tema que o
   `index.php` normalmente gera.
3. As specs em [`specs/`](specs) abrem a fixture via `file://` e checam o
   **contrato de marcação** (`.active`, `aria-current="page"`) e o **estilo
   computado** (cor, peso da fonte, indicadores).

Assim, um teste vermelho aponta uma regressão no override **ou** no
`template.css`. Se você mudar a marcação de um override, atualize a fixture
correspondente — ela é o contrato.

## Rodando localmente

```bash
cd tests
npm install
npx playwright install chromium   # baixa o navegador na primeira vez
npm test                          # roda todas as specs
```

Outros comandos úteis:

```bash
npm run test:headed   # com navegador visível
npm run report        # abre o último relatório HTML
```

## Estrutura

```
tests/
├── package.json
├── playwright.config.js
├── fixtures/            # HTML que espelha a saída dos overrides
│   ├── navbar.html      # menu principal (html/mod_menu/default.php)
│   └── metismenu.html   # menu offcanvas/sidebar (dropdown-metismenu)
└── specs/
    └── menu-active.spec.js
```

## CI

O workflow [`.github/workflows/playwright.yml`](../.github/workflows/playwright.yml)
roda estas specs em pushes/PRs que tocam o CSS, os overrides de menu ou a pasta
`tests/`. Como as fixtures são estáticas, o CI não precisa de Joomla nem de FTP.

> Esta pasta fica **fora** de `tpl_generico/` de propósito: o pacote instalável
> é só `tpl_generico/`, então os testes nunca vão para o ZIP de produção.
