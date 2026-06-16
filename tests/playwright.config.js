// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Testes de UI do template tpl_generico.
 *
 * Os testes carregam fixtures HTML estaticas (tests/fixtures/*.html) que
 * espelham EXATAMENTE a saida dos overrides de menu (html/mod_menu/*) e
 * referenciam o CSS real do template (tpl_generico/media/css/template.css).
 * Assim validamos o contrato de marcacao (.active, aria-current) e o destaque
 * visual (estilo computado) sem precisar de uma instalacao Joomla rodando.
 */
module.exports = defineConfig({
  testDir: './specs',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 0,
  reporter: process.env.CI ? [['github'], ['list']] : 'list',
  use: {
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
