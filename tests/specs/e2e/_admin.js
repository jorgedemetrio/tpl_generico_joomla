// @ts-check
const { expect } = require('@playwright/test');

/**
 * Helpers de administração (Joomla /administrator) para os fluxos E2E que
 * dependem do backend — ex.: habilitar o newsletter modal no estilo do template
 * ou liberar/ativar um usuário registrado.
 *
 * Credenciais SOMENTE via ambiente (nunca no repositório):
 *   ADMIN_USER=... ADMIN_PASS=... npx playwright test --config=playwright.e2e.config.js
 */

const ADMIN = 'administrator/index.php';
const STYLE_ID = 13; // estilo "generico - Padrão" (#__template_styles)

function creds() {
  const user = process.env.ADMIN_USER;
  const pass = process.env.ADMIN_PASS;
  if (!user || !pass) {
    throw new Error('Defina ADMIN_USER e ADMIN_PASS no ambiente para os testes que usam o admin.');
  }
  return { user, pass };
}

/** Loga no /administrator (idempotente: se já logado, apenas confirma). */
async function adminLogin(page) {
  const { user, pass } = creds();
  await page.goto(ADMIN, { waitUntil: 'load' });

  const userField = page.locator('input[name="username"]');
  if (await userField.count()) {
    await userField.first().fill(user);
    await page.locator('input[name="passwd"]').first().fill(pass);
    await page.locator('#form-login button[type="submit"], button.btn-primary[type="submit"]').first().click();
    await page.waitForLoadState('networkidle').catch(() => {});
  }
  await expect(
    page.locator('a[href*="task=logout"], a[href*="com_login&task=logout"]').first(),
    'login no admin falhou (confira ADMIN_USER/ADMIN_PASS)'
  ).toBeAttached({ timeout: 15000 });
}

/**
 * Define parâmetros do estilo do template e SALVA (libera o checkout).
 * Cobre switchers (radio com >1 opção) e campos simples. Ex.:
 *   await setTemplateStyleParams(page, { newsletterModal: 1, newsletterModalDelay: 60 });
 */
async function setTemplateStyleParams(page, params) {
  await page.goto(`${ADMIN}?option=com_templates&task=style.edit&id=${STYLE_ID}`, { waitUntil: 'load' });
  await page.waitForLoadState('networkidle').catch(() => {});

  await page.evaluate((p) => {
    for (const [key, val] of Object.entries(p)) {
      const group = document.querySelectorAll(`input[name="jform[params][${key}]"]`);
      if (group.length > 1) {
        // Radio/switcher: marca a opção cujo value bate.
        group.forEach((r) => {
          r.checked = r.value === String(val);
          r.dispatchEvent(new Event('change', { bubbles: true }));
        });
      } else {
        const el =
          document.getElementById('jform_params_' + key) ||
          document.querySelector(`[name="jform[params][${key}]"]`);
        if (el) {
          el.value = String(val);
          el.dispatchEvent(new Event('input', { bubbles: true }));
          el.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
  }, params);

  // Salva e volta para a lista (libera o checkout do item).
  await page.evaluate(() => window.Joomla.submitbutton('style.save'));
  await page.waitForLoadState('networkidle').catch(() => {});
  // Sucesso = voltou para a lista de estilos; um erro de validação manteria
  // a URL em task=style.edit.
  await expect
    .poll(() => page.url(), { message: 'salvar o estilo deveria voltar à lista', timeout: 15000 })
    .toContain('view=styles');
}

module.exports = { ADMIN, STYLE_ID, adminLogin, setTemplateStyleParams };
