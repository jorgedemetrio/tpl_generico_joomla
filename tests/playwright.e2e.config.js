// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Testes E2E REAIS contra uma instalação Joomla rodando o template `generico`.
 *
 * Diferente da config padrão (playwright.config.js, que serve fixtures estáticas
 * via server.js), esta config aponta para um Joomla de verdade — montado com
 * `./setDevEnv.sh` (symlinks do repo) em `localhost:8081/automovel`. Não sobe
 * webServer: a instalação já está no ar. Valida o template renderizando o site
 * real (sem erro/warning de console, sem erro PHP, sem chave de tradução crua) e
 * os fluxos funcionais do template (tema, newsletter, cookie, back-to-top) ponta
 * a ponta.
 *
 * Rodar:  npx playwright test --config=playwright.e2e.config.js
 * Base configurável por env:  E2E_BASE_URL=http://host/path npx playwright test --config=playwright.e2e.config.js
 *
 * Não roda no CI (não há Joomla lá): a config padrão ignora ./specs/e2e.
 */
// A barra final é obrigatória: sem ela, URLs relativas (index.php?...) e './'
// resolveriam FORA de /automovel/ (viram raiz do host) e dão 404.
const RAW = process.env.E2E_BASE_URL || 'http://localhost:8081/automovel/';
const BASE = RAW.endsWith('/') ? RAW : RAW + '/';

module.exports = defineConfig({
  testDir: './specs/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 2,
  reporter: 'list',
  timeout: 45000,
  expect: { timeout: 10000 },
  use: {
    baseURL: BASE,
    trace: 'on-first-retry',
    ignoreHTTPSErrors: true,
  },
  projects: [{ name: 'e2e-chromium', use: { ...devices['Desktop Chrome'] } }],
});
