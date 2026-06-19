// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

/**
 * "Se falta tradução": regra de negócio do template multilíngue (8 idiomas).
 *
 * Cada string visível ao usuário sai de uma chave TPL_GENERICO_* resolvida pelo
 * Joomla. Se uma chave existe em um idioma mas não nos outros, o visitante
 * naquele idioma vê a CHAVE crua (ex.: "TPL_GENERICO_BANNER_LABEL") em vez do
 * texto — um defeito de tradução. Este teste garante PARIDADE TOTAL de chaves
 * entre os 8 locais, lendo os arquivos .ini REAIS do pacote (não uma fixture).
 *
 * Assim, esquecer de traduzir uma chave nova (como as de SEO/landmarks da Fase
 * 3) quebra o build — exatamente o "falta tradução" que se quer barrar.
 */

const LANG_DIR = path.join(__dirname, '..', '..', 'tpl_generico', 'language');
const LOCALES = ['en-GB', 'pt-BR', 'es-ES', 'de-DE', 'fr-FR', 'it-IT', 'ja-JP', 'zh-CN'];
const FILES = ['tpl_generico.ini', 'tpl_generico.sys.ini'];

/** Extrai o conjunto de chaves de um arquivo .ini de idioma do Joomla. */
function parseKeys(absPath) {
  const keys = new Set();
  const raw = fs.readFileSync(absPath, 'utf8');
  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (trimmed === '' || trimmed.startsWith(';')) {
      continue; // linha vazia ou comentário
    }
    const m = trimmed.match(/^([A-Z0-9_]+)\s*=/);
    if (m) {
      keys.add(m[1]);
    }
  }
  return keys;
}

/** Lê as chaves de um mesmo arquivo (ini ou sys.ini) em todos os locais. */
function keysByLocale(fileName) {
  const out = {};
  for (const loc of LOCALES) {
    out[loc] = parseKeys(path.join(LANG_DIR, loc, fileName));
  }
  return out;
}

for (const fileName of FILES) {
  test.describe(`i18n — paridade de chaves (${fileName})`, () => {
    const perLocale = keysByLocale(fileName);

    // Universo de chaves: a união de todas as chaves vistas em qualquer idioma.
    const universe = new Set();
    for (const loc of LOCALES) {
      perLocale[loc].forEach((k) => universe.add(k));
    }

    test('todos os idiomas têm exatamente o mesmo conjunto de chaves', () => {
      /** @type {Record<string, string[]>} */
      const missingByLocale = {};
      for (const loc of LOCALES) {
        const missing = [...universe].filter((k) => !perLocale[loc].has(k)).sort();
        if (missing.length) {
          missingByLocale[loc] = missing;
        }
      }
      expect(
        missingByLocale,
        'Chaves presentes em algum idioma mas faltando em outro(s):\n' +
          JSON.stringify(missingByLocale, null, 2)
      ).toEqual({});
    });

    test('nenhuma chave tem valor vazio (string não traduzida)', () => {
      /** @type {Record<string, string[]>} */
      const emptyByLocale = {};
      for (const loc of LOCALES) {
        const raw = fs.readFileSync(path.join(LANG_DIR, loc, fileName), 'utf8');
        const empties = [];
        for (const line of raw.split(/\r?\n/)) {
          const t = line.trim();
          if (t === '' || t.startsWith(';')) continue;
          const m = t.match(/^([A-Z0-9_]+)\s*=\s*"(.*)"\s*$/);
          if (m && m[2].trim() === '') {
            empties.push(m[1]);
          }
        }
        if (empties.length) emptyByLocale[loc] = empties;
      }
      expect(
        emptyByLocale,
        'Chaves com valor vazio:\n' + JSON.stringify(emptyByLocale, null, 2)
      ).toEqual({});
    });
  });
}

test.describe('i18n — chaves novas da Fase 3 (SEO/landmarks)', () => {
  const novas = ['TPL_GENERICO_BREADCRUMB_LABEL', 'TPL_GENERICO_BANNER_LABEL'];

  for (const loc of LOCALES) {
    test(`${loc} traduz as chaves de landmark de SEO`, () => {
      const keys = parseKeys(path.join(LANG_DIR, loc, 'tpl_generico.ini'));
      for (const k of novas) {
        expect(keys.has(k), `${loc} não traduz ${k}`).toBe(true);
      }
    });
  }
});
