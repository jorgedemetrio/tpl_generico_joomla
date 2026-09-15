// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Google Funding Choices (CMP) + Consent Mode v2 — só ativo quando o template
 * tem `adsensePubId` preenchido (ver bloco "Google Funding Choices" em
 * index.php). Contrato coberto aqui:
 *  - consent mode default ('denied') aparece ANTES do script do Funding
 *    Choices no HTML (ordem exigida pelo Google — ver docblock de index.php);
 *  - o script do Funding Choices usa o pub id configurado e carrega `async`;
 *  - o aviso de cookies customizado (#cookieNotice) some quando o Funding
 *    Choices está ativo (evita 2 avisos de consentimento conflitantes).
 * Não aguarda a rede real do domínio fundingchoicesmessages.google.com (o
 * script é async e o teste roda sem internet garantida em CI) — só valida a
 * marcação, que é o contrato do template. A requisição real a esse domínio é
 * abortada em todo teste (beforeEach) para não depender de internet em CI.
 */

const FIXTURE = '/tests/fixtures/funding-choices.html';

test.beforeEach(async ({ page }) => {
  await page.route('https://fundingchoicesmessages.google.com/**', (route) => route.abort());
});

test('consent mode default vem ANTES do script do Funding Choices, na ordem exigida pelo Google', async ({ page }) => {
  await page.goto(FIXTURE);
  const html = await page.content();
  const consentIdx = html.indexOf("gtag('consent','default'");
  const fcScriptIdx = html.indexOf('fundingchoicesmessages.google.com');
  expect(consentIdx, 'consent mode default presente').toBeGreaterThan(-1);
  expect(fcScriptIdx, 'script do Funding Choices presente').toBeGreaterThan(-1);
  expect(consentIdx, 'consent default vem antes do script do CMP').toBeLessThan(fcScriptIdx);
});

test('consent mode default nega os 4 sinais até o visitante decidir', async ({ page }) => {
  await page.goto(FIXTURE);
  const html = await page.content();
  for (const sinal of ['ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage']) {
    expect(html, `${sinal} negado por padrão`).toContain(`'${sinal}':'denied'`);
  }
});

test('script do Funding Choices usa o pub id do template e carrega async', async ({ page }) => {
  await page.goto(FIXTURE);
  const script = page.locator('script[src*="fundingchoicesmessages.google.com"]');
  await expect(script).toHaveCount(1);
  await expect(script).toHaveAttribute('src', /\/i\/pub-1234567890123456\?ers=1$/);
  await expect(script).toHaveAttribute('async', '');
});

test('aviso de cookies customizado NAO aparece quando o Funding Choices está ativo', async ({ page }) => {
  await page.goto(FIXTURE);
  await expect(page.locator('#cookieNotice')).toHaveCount(0);
});
