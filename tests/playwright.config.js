// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Testes de UI do template tpl_generico.
 *
 * Os testes carregam fixtures HTML estaticas (tests/fixtures/*.html) que
 * espelham EXATAMENTE a saida dos overrides/markup do template e referenciam o
 * CSS e o JS reais (tpl_generico/media/...). Um servidor estatico (server.js)
 * serve a raiz do repo via HTTP, para que os caminhos relativos resolvam e o
 * localStorage funcione (necessario ao modal de newsletter). Assim validamos o
 * contrato de marcacao e o comportamento real sem uma instalacao Joomla.
 */
const PORT = 3210;

module.exports = defineConfig({
  testDir: './specs',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['list']] : 'list',
  use: {
    baseURL: 'http://127.0.0.1:' + PORT,
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'node server.js',
    url: 'http://127.0.0.1:' + PORT + '/tests/fixtures/navbar.html',
    reuseExistingServer: !process.env.CI,
    timeout: 30000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
