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
 */

const PKG = path.join(__dirname, '..', '..', 'tpl_generico');
const MEDIA = path.join(PKG, 'media');
const manifest = JSON.parse(fs.readFileSync(path.join(PKG, 'joomla.asset.json'), 'utf8'));

/** Pasta que o core concatena por tipo de asset. */
const FOLDER_BY_TYPE = { style: 'css', script: 'js' };

/** Assets de arquivo (style/script) cujo binário é shippado no pacote. */
const owned = manifest.assets.filter(
  (a) => a.uri && !a.uri.startsWith('system/') && (a.type === 'style' || a.type === 'script')
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
