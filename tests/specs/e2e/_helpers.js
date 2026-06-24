// @ts-check
const { expect } = require('@playwright/test');

/**
 * Utilitários compartilhados pelos testes E2E reais (Joomla em localhost:8081).
 * Não é um arquivo de teste (sem `.spec`), então o runner não o coleta.
 */

/** Mensagens de console de TERCEIROS (não do template) que toleramos. */
const CONSOLE_ALLOW = [
  /facebook|fbevents|fbq|pixel|connect\.facebook/i,
  /googletagmanager|gtag|google-analytics|googleads|doubleclick/i,
  /favicon/i,
  /web-vitals|hotjar|clarity|tiktok/i,
  /Failed to load resource: the server responded with a status of 404/i,
];

/** Erro PHP que nunca deve vazar no HTML renderizado. */
const PHP_ERROR = /Fatal error|Parse error|\bWarning\b:|\bNotice\b:|\bDeprecated\b:|Call to undefined|Uncaught/;

/** Padrão de chave de tradução crua (template OU core) visível na página. */
const RAW_KEY = /\b(TPL_GENERICO|JGLOBAL|COM_[A-Z]+|MOD_[A-Z]+)_[A-Z0-9_]+\b/g;

/**
 * Liga coletores de problemas do template (erros/avisos de console próprios e
 * exceções JS), filtrando ruído de terceiros. Chame ANTES do primeiro goto.
 * @param {import('@playwright/test').Page} page
 */
function attachConsole(page) {
  const errors = [];
  const warnings = [];
  const pageErrors = [];
  page.on('console', (msg) => {
    const text = msg.text();
    if (CONSOLE_ALLOW.some((re) => re.test(text))) return;
    if (msg.type() === 'error') errors.push(text);
    else if (msg.type() === 'warning') warnings.push(text);
  });
  page.on('pageerror', (err) => pageErrors.push(err.message));
  return { errors, warnings, pageErrors };
}

/** Falha se houver exceção JS ou erro de console próprio (warnings só reportados). */
function expectNoConsoleProblems(c, where) {
  expect(c.pageErrors, `exceções JS em ${where}`).toEqual([]);
  expect(c.errors, `erros de console (não-terceiros) em ${where}`).toEqual([]);
}

/** Falha se o HTML tiver erro PHP. */
function expectNoPhpError(html, where) {
  const hit = html.match(PHP_ERROR);
  expect(hit, `erro PHP em ${where}: ${hit && hit[0]}`).toBeNull();
}

/** Falha se houver chave de tradução crua no texto visível. */
async function expectNoRawKeys(page, where) {
  const bodyText = await page.locator('body').innerText();
  const raw = [...new Set(bodyText.match(RAW_KEY) || [])];
  expect(raw, `chaves de tradução cruas em ${where}`).toEqual([]);
}

/** Falha se o CSS/JS próprios do template não estiverem na página. */
async function expectTemplateAssets(page) {
  await expect(
    page.locator('link[href*="templates/site/generico/css/template.css"]'),
    'CSS do template ausente'
  ).toHaveCount(1);
  await expect(
    page.locator('script[src*="templates/site/generico/js/template.js"]'),
    'JS do template ausente'
  ).toHaveCount(1);
}

module.exports = {
  CONSOLE_ALLOW,
  attachConsole,
  expectNoConsoleProblems,
  expectNoPhpError,
  expectNoRawKeys,
  expectTemplateAssets,
};
