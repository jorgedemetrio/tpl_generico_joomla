// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

/**
 * Consistência do joomla.asset.json com o que é realmente empacotado (#37).
 *
 * Regra de resolução do Web Asset Manager (Joomla 5) — VALIDADA em instalação
 * real (template inheritable). Para um asset `style`/`script` com `uri` relativa,
 * o core resolve o caminho como:
 *
 *     media/templates/site/<template>/<folder>/<uri>
 *
 * onde `<folder>` é FIXO por tipo: `css` para `style`, `js` para `script`
 * (HTMLHelper::includeRelativeFiles concatena `$folder` ANTES da `uri`). Logo a
 * `uri` NÃO pode repetir o prefixo `css/`/`js/` — se repetir, o caminho vira
 * `.../generico/css/css/template.css`, o arquivo não existe e o `<link>`/`<script>`
 * é silenciosamente omitido (CSS/JS do template somem). O arquivo físico, no
 * pacote, vive em `media/<folder>/<uri>`.
 *
 * (Correção do erro registrado antes: o manifesto chegou a usar `css/template.css`,
 * o que parecia certo olhando só a árvore do pacote (`media/css/template.css`),
 * mas quebra a resolução do core. O Cassiopeia confirma: usa `template.min.css`,
 * `offline.css`, `template.js` — sem prefixo de pasta.)
 *
 * URIs com prefixo `system/` (ex.: fontawesome) são providas pelo CORE do
 * Joomla, não vivem no pacote, e ficam fora da verificação de arquivo.
 *
 * URIs EXTERNAS (`http://`, `https://` ou `//`, ex.: bootstrap-icons via CDN)
 * também ficam fora: o core (HTMLHelper::includeRelativeFiles) as detecta e
 * emite o <link>/<script> com a URL como está, sem resolver no filesystem.
 */

const PKG = path.join(__dirname, '..', '..', 'tpl_generico');
const MEDIA = path.join(PKG, 'media');
const manifest = JSON.parse(fs.readFileSync(path.join(PKG, 'joomla.asset.json'), 'utf8'));

/** Pasta que o core concatena por tipo de asset. */
const FOLDER_BY_TYPE = { style: 'css', script: 'js' };

/** URI externa (CDN): o core emite como está, sem resolver no filesystem. */
const isExternal = (uri) => /^(https?:)?\/\//.test(uri);

/** Assets de arquivo (style/script) cujo binário é shippado no pacote. */
const owned = manifest.assets.filter(
  (a) =>
    a.uri &&
    !a.uri.startsWith('system/') &&
    !isExternal(a.uri) &&
    (a.type === 'style' || a.type === 'script')
);

test.describe('joomla.asset.json — consistência das URIs (#37)', () => {
  test('há pelo menos os assets próprios css + js', () => {
    // Guarda contra um manifesto vazio mascarar os testes abaixo.
    expect(owned.length).toBeGreaterThanOrEqual(3);
  });

  test('toda URI própria resolve como o core resolve: media/<folder>/<uri>', () => {
    const missing = [];
    for (const a of owned) {
      const folder = FOLDER_BY_TYPE[a.type];
      const abs = path.join(MEDIA, folder, a.uri);
      if (!fs.existsSync(abs)) missing.push(`${a.name} (${a.type}) -> media/${folder}/${a.uri}`);
    }
    expect(
      missing,
      'URIs que não resolvem para um arquivo real (CSS/JS não carregaria no Joomla):\n' +
        missing.join('\n')
    ).toEqual([]);
  });

  test('URIs próprias NÃO repetem o prefixo css/ ou js/ (o core já concatena o folder)', () => {
    const wrong = [];
    for (const a of owned) {
      if (a.uri.startsWith('css/') || a.uri.startsWith('js/')) {
        wrong.push(`${a.name} (${a.type}) -> ${a.uri}`);
      }
    }
    expect(
      wrong,
      'Assets com prefixo de pasta duplicado (vira .../css/css/arquivo e some):\n' + wrong.join('\n')
    ).toEqual([]);
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

/**
 * Bootstrap 5 / Bootstrap Icons / jQuery por CDN (jsDelivr).
 *
 * `bootstrap.css`, `bootstrap.css.grid` e `jquery` sobrescrevem, PELO MESMO NOME, os
 * assets do core: tudo que depende deles (inclusive extensoes de terceiros) passa a
 * usar a copia do CDN. O JS do Bootstrap NAO e trocado de proposito: o core o serve
 * como modulos ES por componente (bootstrap.collapse, .dropdown, .offcanvas...) e
 * carregar tambem o bundle do CDN registraria os handlers data-bs-* duas vezes.
 */
const CDN = /^https:\/\/cdn\.jsdelivr\.net\/npm\/[a-z-]+@\d+\.\d+\.\d+\//;
const byName = (n) => manifest.assets.find((a) => a.name === n);

test.describe('joomla.asset.json — Bootstrap, Icons e jQuery por CDN', () => {
  for (const name of ['bootstrap.css', 'bootstrap.css.grid', 'jquery', 'bootstrap-icons']) {
    test(`${name}: CDN com versão fixa, SRI sha384 e crossorigin`, () => {
      const a = byName(name);
      expect(a, `asset ${name} ausente`).toBeTruthy();
      expect(a.uri).toMatch(CDN); // versao exata (sem @latest/@5)
      expect(a.attributes && a.attributes.integrity).toMatch(/^sha384-[A-Za-z0-9+/]{64}$/);
      expect(a.attributes.crossorigin).toBe('anonymous');
    });
  }

  test('o Bootstrap CSS do CDN é o mesmo 5.3.x servido pelo core (CSS e JS consistentes)', () => {
    expect(byName('bootstrap.css').uri).toMatch(/bootstrap@5\.3\.\d+\//);
  });

  test('o preset carrega jQuery junto com o CSS e o JS do template', () => {
    const preset = byName('tpl_generico.preset');
    expect(preset.dependencies).toEqual(
      expect.arrayContaining(['tpl_generico.css#style', 'tpl_generico.js#script', 'jquery#script'])
    );
  });

  test('o manifesto não declara "version" por asset (o deploy.sh sobrescreve toda chave "version")', () => {
    const withVersion = manifest.assets.filter((a) => 'version' in a).map((a) => a.name);
    expect(withVersion).toEqual([]);
  });
});
