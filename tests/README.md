# Testes de UI (Playwright)

Testes de interface do template **tpl_generico** (Joomla 5). Eles validam o
comportamento de UI que não dá para garantir só com `php -l`/SonarQube — hoje, o
**destaque do item de menu ativo**.

## Como funcionam (sem precisar de Joomla rodando)

Os testes **não** sobem uma instalação do Joomla. Em vez disso:

1. As fixtures em [`fixtures/`](fixtures) são HTML estáticas que **espelham
   exatamente a marcação** produzida pelo template (overrides de menu em
   `tpl_generico/html/mod_menu/*` e o modal de newsletter do `index.php`).
2. Cada fixture referencia o **CSS e o JS reais** do template
   (`tpl_generico/media/css/template.css`, `tpl_generico/media/js/template.js`)
   e injeta as variáveis de tema que o `index.php` normalmente gera.
3. Um servidor estático ([`server.js`](server.js), iniciado automaticamente pelo
   `webServer` do Playwright) serve a **raiz do repositório** via HTTP. Isso faz
   os caminhos relativos das fixtures resolverem para os assets reais e permite
   o uso de `localStorage` (necessário ao modal de newsletter).
4. As specs em [`specs/`](specs) abrem a fixture e checam o **contrato de
   marcação** (`.active`, `aria-current`, etc.) e o **comportamento real**
   (estilo computado, visibilidade, validação de e-mail, redirect).

Assim, um teste vermelho aponta uma regressão no markup **ou** no `template.css`
/ `template.js`. Se você mudar a marcação produzida pelo template, atualize a
fixture correspondente — ela é o contrato.

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
├── server.js                  # servidor estático (raiz do repo) usado nos testes
├── fixtures/                  # HTML que espelha a saída do template
│   ├── navbar.html            # menu principal (html/mod_menu/default.php)
│   ├── metismenu.html         # menu offcanvas/sidebar (dropdown-metismenu)
│   ├── newsletter.html        # modal de newsletter (index.php + template.js)
│   └── registration-stub.html # destino do redirect (valida o e-mail na URL)
└── specs/
    ├── menu-active.spec.js
    └── newsletter-modal.spec.js
```

## Testes E2E reais (contra um Joomla de verdade)

Além das fixtures, há uma suíte **E2E** em [`specs/e2e/`](specs/e2e) que roda
contra uma instalação Joomla real com o template montado por
[`../setDevEnv.sh`](../setDevEnv.sh) (symlinks → `localhost:8081/automovel`).
Ela valida o template renderizando o site real (smoke de várias telas: sem erro
JS, sem erro PHP, sem chave de tradução crua, com CSS/JS do template aplicados) e
os **fluxos funcionais** ponta a ponta — tema (persiste entre páginas), aviso de
cookies, back-to-top e newsletter modal (este liga/desliga o recurso pelo admin).

Config própria: [`playwright.e2e.config.js`](playwright.e2e.config.js). A config
padrão **ignora** `specs/e2e` (via `testIgnore`), então o CI não tenta rodá-los.

```bash
cd tests
# fluxos que usam o admin (newsletter) exigem credenciais — passe por ambiente,
# nunca commite. Base configurável por E2E_BASE_URL (default localhost:8081/automovel).
ADMIN_USER='...' ADMIN_PASS='...' npx playwright test --config=playwright.e2e.config.js
```

Helpers (não são specs): `specs/e2e/_helpers.js` (coletores de console/erro PHP/
chave crua, com allowlist de terceiros) e `specs/e2e/_admin.js` (login no admin e
configuração do estilo do template).

## CI

O workflow [`.github/workflows/playwright.yml`](../.github/workflows/playwright.yml)
roda estas specs em pushes/PRs que tocam `index.php`, `media/`, `html/` ou a
pasta `tests/`. Como as fixtures são estáticas, o CI não precisa de Joomla nem
de FTP. (A suíte `specs/e2e` fica fora do CI: depende de um Joomla rodando.)

> Esta pasta fica **fora** de `tpl_generico/` de propósito: o pacote instalável
> é só `tpl_generico/`, então os testes nunca vão para o ZIP de produção.
