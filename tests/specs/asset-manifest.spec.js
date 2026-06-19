// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

/**
 * Consistência do joomla.asset.json com o que é realmente empacotado (#37).
 *
 * Regra de negócio do empacotamento Joomla 5: os assets do template vivem em
 * `media/{css,js}/` no pacote e são instalados em
 * `media/templates/site/generico/`. O Web Asset Manager resolve a `uri` de cada
 * asset RELATIVA a essa pasta — então a `uri` precisa incluir o prefixo
 * `css/`/`js/`. Quando faltava (era `template.css` em vez de `css/template.css`),
 * numa instalação limpa o CSS/JS não carregava. Este teste lê os arquivos REAIS
 * do pacote e falha se uma `uri` própria não resolver — barrando a regressão.
 *
 * URIs com prefixo `system/` (ex.: fontawesome) são providas pelo CORE do
 * Joomla, não vivem no pacote, e ficam fora da verificação de arquivo.
 */

const PKG = path.join(__dirname, '..', '..', 'tpl_generico');
const MEDIA = path.join(PKG, 'media');
const manifest = JSON.parse(fs.readFileSync(path.join(PKG, 'joomla.asset.json'), 'utf8'));

/** Assets de arquivo (style/script) cujo binário é shippado no pacote. */
const owned = manifest.assets.filter(
  (a) => a.uri && !a.uri.startsWith('system/') && (a.type === 'style' || a.type === 'script')
);

test.describe('joomla.asset.json — consistência das URIs (#37)', () => {
  test('há pelo menos os assets próprios css + js', () => {
    // Guarda contra um manifesto vazio mascarar os testes abaixo.
    expect(owned.length).toBeGreaterThanOrEqual(3);
  });

  test('toda URI própria resolve para um arquivo existente em media/', () => {
    const missing = [];
    for (const a of owned) {
      const abs = path.join(MEDIA, a.uri);
      if (!fs.existsSync(abs)) missing.push(`${a.name} -> ${a.uri}`);
    }
    expect(
      missing,
      'URIs sem arquivo correspondente em media/ (CSS/JS não carregaria):\n' + missing.join('\n')
    ).toEqual([]);
  });

  test('assets próprios apontam para css/ (style) e js/ (script), não para a raiz', () => {
    const wrong = [];
    for (const a of owned) {
      const ok =
        (a.type === 'style' && a.uri.startsWith('css/')) ||
        (a.type === 'script' && a.uri.startsWith('js/'));
      if (!ok) wrong.push(`${a.name} (${a.type}) -> ${a.uri}`);
    }
    expect(wrong, 'Assets fora de css/ ou js/:\n' + wrong.join('\n')).toEqual([]);
  });

  test('as subpastas declaradas em <media> do templateDetails.xml existem no pacote', () => {
    const xml = fs.readFileSync(path.join(PKG, 'templateDetails.xml'), 'utf8');
    const mediaBlock = (xml.match(/<media[\s\S]*?<\/media>/) || [''])[0];
    const folders = [...mediaBlock.matchAll(/<folder>([^<]+)<\/folder>/g)].map((m) => m[1].trim());
    expect(folders.length, 'Bloco <media> sem <folder> declarada').toBeGreaterThan(0);
    const missing = folders.filter((f) => !fs.existsSync(path.join(MEDIA, f)));
    expect(missing, 'Subpastas de <media> ausentes no pacote: ' + missing.join(', ')).toEqual([]);
  });
});
