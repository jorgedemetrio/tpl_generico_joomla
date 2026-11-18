<?php
/**
 * Template generico - helper.
 *
 * Fornece a classe `TplGenericoHelper` usada pelo `index.php` atual
 * (método `buildCssVars`). Também mantém fallback `__callStatic`/`__call`
 * por compatibilidade com versões antigas instaladas em produção que
 * podem invocar métodos diferentes — evita erros fatais durante a
 * transição entre versões publicadas.
 *
 * @package   Templates.generico
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

if (!class_exists('TplGenericoHelper', false)) {
    /**
     * Helper methods for tpl_generico template.
     */
    class TplGenericoHelper
    {
        /** Cores padrao de marca (defaults dos parametros do admin). */
        private const DEFAULT_PRIMARY   = '#1F4E79';
        private const DEFAULT_SECONDARY = '#2E7D32';
        private const DEFAULT_CTA       = '#2F80ED';
        /** Branco: fundo claro padrao das superficies (reutilizado por TplGenericoColor). */
        public const WHITE_HEX      = '#FFFFFF';
        /** Fallback da tripla RGB quando o hex e invalido. */
        private const RGB_FALLBACK      = '0, 0, 0';

        /**
         * Chave do localStorage que guarda o tema escolhido (claro/escuro).
         * Fonte unica da verdade: o index.php injeta este valor no script inline
         * do <head> e no atributo data-theme-key do <html>; o template.js le do
         * atributo. Assim a chave nunca dessincroniza entre PHP e JS (#81/A10).
         */
        public const THEME_STORAGE_KEY  = 'generico-theme';

        /**
         * Le um parametro tratando vazio/null como "use o default".
         * Promovido do closure local do buildCssVars para ser reutilizavel.
         *
         * @param  Registry|null  $params   Template parameters
         * @param  string         $key      Nome do parametro
         * @param  mixed          $default  Valor padrao
         * @return mixed
         */
        public static function getParam($params, string $key, $default)
        {
            if ($params === null) {
                return $default;
            }
            $value = $params->get($key, $default);
            return ($value === null || $value === '') ? $default : $value;
        }

        /**
         * Build CSS variables string from template parameters.
         *
         * @param  Registry|null  $params  Template parameters
         * @return string                  CSS variables ready for inline use
         */
        public static function buildCssVars($params): string
        {
            // Os valores vem de parametros do admin e sao impressos dentro de um
            // <style>: sanitiza para que nenhum valor consiga fechar a declaracao,
            // o bloco ou a propria tag (</style><script>...). Ver cssValue().
            $get = static function ($key, $default) use ($params) {
                $value = TplGenericoSanitizer::cssValue(self::getParam($params, $key, $default));
                return $value === '' ? $default : $value;
            };

            // Cada cor de marca e lida UMA vez e reutilizada para a variavel e sua
            // tripla RGB (antes o mesmo default era lido 2-4x por cor).
            $primary    = $get('primaryColor', self::DEFAULT_PRIMARY);
            $secondary  = $get('secondaryColor', self::DEFAULT_SECONDARY);
            $cta        = $get('ctaColor', self::DEFAULT_CTA);
            // Destaque do menu ativo: se vazio, usa o mesmo azul do CTA.
            $menuActive = $get('menuActiveColor', self::DEFAULT_CTA);
            $primaryRgb   = self::hexToRgb($primary);
            $secondaryRgb = self::hexToRgb($secondary);
            $ctaRgb       = self::hexToRgb($cta);

            $cssVars  = "--cor-primaria: {$primary};";
            $cssVars .= "--cor-secundaria: {$secondary};";
            $cssVars .= "--cor-cta: {$cta};";
            // Cor do destaque do item de menu ativo. Tripla RGB para o leve fundo em rgba().
            $cssVars .= "--cor-menu-ativo: {$menuActive};";
            $cssVars .= '--cor-menu-ativo-rgb: ' . self::hexToRgb($menuActive) . ';';
            // Triplas RGB das cores de marca, usadas em rgba() (focus rings, overlays).
            $cssVars .= '--cor-primaria-rgb: ' . $primaryRgb . ';';
            $cssVars .= '--cor-secundaria-rgb: ' . $secondaryRgb . ';';
            $cssVars .= '--cor-cta-rgb: ' . $ctaRgb . ';';
            $cssVars .= "--cor-texto: {$get('textColor', '#222222')};";
            $cssVars .= "--cor-texto-secundario: {$get('textSecondaryColor', '#6B7280')};";
            $cssVars .= "--cor-superficie-clara: {$get('surfaceLightColor', self::WHITE_HEX)};";
            $cssVars .= "--cor-superficie-clara-topo: {$get('surfaceLightColorTopo', self::WHITE_HEX)};";
            $cssVars .= "--cor-superficie-alt: {$get('surfaceAltColor', '#F5F7FA')};";
            $cssVars .= "--cor-borda: {$get('borderColor', '#E5E7EB')};";
            $cssVars .= "--espaco-interno-card: {$get('espacoInternoCard', '1.5rem')};";
            $cssVars .= "--margin-topo-card: {$get('margemTopoCard', '10px')};";
            $cssVars .= "--espaco-interno-titulo-card: {$get('espacoInternoTituloCard', '1.5rem')};";
            $cssVars .= "--margin-topo-titulo-card: {$get('margemTopoTituloCard', '10px')};";
            $footerColor = $get('footerColor', '#0F172A');
            $cssVars .= "--cor-footer: {$footerColor};";
            // Texto/titulo/links do rodape com contraste garantido sobre o fundo
            // escolhido (o rodape tem fundo proprio nos dois temas).
            $cssVars .= '--cor-footer-texto: ' . TplGenericoColor::readableTextOn($footerColor, false) . ';';
            $cssVars .= '--cor-footer-titulo: ' . TplGenericoColor::readableTextOn($footerColor, true) . ';';
            $defaultFontStack = "'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
            $cssVars .= "--familia-fonte-primaria: {$get('fontFamilyPrimary', $defaultFontStack)};";
            $cssVars .= "--tamanho-base-fonte: {$get('fontSizeBase', '1rem')};";
            $cssVars .= "--peso-fonte-normal: {$get('fontWeightNormal', '400')};";
            $cssVars .= "--peso-fonte-titulos: {$get('fontWeightHeadings', '700')};";
            $cssVars .= "--raio-borda-global: {$get('borderRadius', '4')}px;";

            $spacing = $get('verticalSpacing', 'M');
            $spacingValue = '2rem';
            if ($spacing === 'S') {
                $spacingValue = '1rem';
            } elseif ($spacing === 'L') {
                $spacingValue = '3rem';
            }
            $cssVars .= "--espacamento-vertical-global: {$spacingValue};";

            // Largura maxima do container "boxed". Aceita valor livre (px/rem) vindo do admin;
            // se vazio, usa o comportamento padrao do Bootstrap (sem limite extra).
            $maxWidth = $get('containerMaxWidth', '');
            if ($maxWidth !== '') {
                $cssVars .= "--generico-container-max-width: {$maxWidth};";
            }

            // Sincroniza as variaveis do Bootstrap 5 com as cores do template,
            // garantindo que componentes do core (alerts, badges, navs, links,
            // dropdowns, paginacao, etc.) respeitem as cores definidas no admin.
            // (As triplas RGB ja foram calculadas uma vez no topo do metodo.)
            $cssVars .= '--bs-primary: var(--cor-primaria);';
            $cssVars .= '--bs-secondary: var(--cor-secundaria);';
            $cssVars .= "--bs-primary-rgb: {$primaryRgb};";
            $cssVars .= "--bs-secondary-rgb: {$secondaryRgb};";
            $cssVars .= '--bs-link-color: var(--cor-cta);';
            $cssVars .= "--bs-link-color-rgb: {$ctaRgb};";
            $cssVars .= '--bs-link-hover-color: var(--cor-primaria);';
            $cssVars .= "--bs-link-hover-color-rgb: {$primaryRgb};";
            $cssVars .= '--bs-body-color: var(--cor-texto);';
            $cssVars .= '--bs-body-bg: var(--cor-superficie-clara);';
            $cssVars .= '--bs-border-color: var(--cor-borda);';
            $cssVars .= '--bs-border-radius: var(--raio-borda-global);';
            $cssVars .= '--bs-font-sans-serif: var(--familia-fonte-primaria);';
            $cssVars .= '--bs-body-font-family: var(--familia-fonte-primaria);';
            $cssVars .= '--bs-body-font-size: var(--tamanho-base-fonte);';
            $cssVars .= '--bs-body-font-weight: var(--peso-fonte-normal);';

            return $cssVars;
        }

        /**
         * Emite os metadados de SEO/redes sociais no <head>: canonical, fallback
         * de meta description, theme-color, Open Graph e Twitter Cards.
         *
         * Recebe o proprio documento ($doc) para nao depender de estado global.
         * Os valores sao passados crus — o renderizador de metas do Joomla escapa
         * na saida (passar ja-escapado causaria duplo-escape do "&").
         *
         * @param  object  $doc     Documento HTML (Joomla\CMS\Document\HtmlDocument)
         * @param  Registry|null  $params  Parametros do template
         * @param  object  $app     Aplicacao Joomla
         * @param  object  $input   Input da requisicao
         * @return void
         */
        public static function applyHeadSeo($doc, $params, $app, $input): void
        {
            if (!is_object($doc) || !method_exists($doc, 'setMetaData')) {
                return;
            }

            $uri      = \Joomla\CMS\Uri\Uri::getInstance();
            $base     = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');
            $sitename = (string) $app->get('sitename');

            // A1 — URL canonica (so caminho, sem query/fragmento). Nao sobrescreve
            // um canonical ja definido por outro componente (ex.: com_content).
            $canonical = $uri->toString(['scheme', 'host', 'port', 'path']);
            if (!self::hasCanonical($doc)) {
                $doc->addHeadLink($canonical, 'canonical');
            }

            // A2 — Fallback de meta description (evita o Google inventar o snippet).
            $description = (string) $doc->getMetaData('description');
            if ($description === '') {
                $description = (string) ($app->get('MetaDesc') ?: $sitename);
                if ($description !== '') {
                    $doc->setMetaData('description', $description);
                }
            }

            // Titulo da pagina (cru; o documento escapa na saida).
            $title = (string) $doc->getTitle();
            if ($title === '') {
                $title = $sitename;
            }

            // I2 — theme-color derivado da cor de marca.
            $themeColor = (string) self::getParam($params, 'primaryColor', self::DEFAULT_PRIMARY);
            if ($themeColor !== '') {
                $doc->setMetaData('theme-color', $themeColor);
            }

            $ogImage = self::resolveLogoUrl($params, $base);
            $option  = (string) $input->getCmd('option', '');
            $view    = (string) $input->getCmd('view', '');

            // B1 — Open Graph (compartilhamento com card; o FB Pixel ja esta ativo).
            self::applyOpenGraph($doc, [
                'site_name'   => $sitename,
                'title'       => $title,
                'type'        => ($option === 'com_content' && $view === 'article') ? 'article' : 'website',
                'url'         => $canonical,
                'description' => $description,
                'locale'      => self::currentLocale($app),
                'image'       => $ogImage,
            ]);

            // B2 — Twitter Cards (fallback do X quando ha/nao ha imagem).
            self::applyTwitterCards($doc, $title, $description, $ogImage);
        }

        /**
         * Emite as propriedades Open Graph que ainda nao existem no documento.
         * RESPEITA OG ja emitido pelo componente (ex.: com_automoveis detalhe de
         * item emite og:title/imagem especificos via AutomoveisHelperSeo) — o
         * template so preenche a propriedade quando ela ainda NAO existe.
         *
         * @param  object  $doc  Documento HTML
         * @param  array   $og   Valores: site_name, title, type, url, description, locale, image
         * @return void
         */
        private static function applyOpenGraph($doc, array $og): void
        {
            $tem = function ($k) use ($doc) {
                return (string) $doc->getMetaData($k, 'property') !== '';
            };

            if (!$tem('og:site_name')) { $doc->setMetaData('og:site_name', $og['site_name'], 'property'); }
            if (!$tem('og:title'))     { $doc->setMetaData('og:title', $og['title'], 'property'); }
            if (!$tem('og:type'))      { $doc->setMetaData('og:type', $og['type'], 'property'); }
            if (!$tem('og:url'))       { $doc->setMetaData('og:url', $og['url'], 'property'); }
            if ($og['description'] !== '' && !$tem('og:description')) {
                $doc->setMetaData('og:description', $og['description'], 'property');
            }
            if ($og['locale'] !== '' && !$tem('og:locale')) {
                $doc->setMetaData('og:locale', $og['locale'], 'property');
            }
            if ($og['image'] !== '' && !$tem('og:image')) {
                $doc->setMetaData('og:image', $og['image'], 'property');
            }
        }

        /**
         * Emite as metas de Twitter Card que ainda nao existem no documento
         * (fallback do X quando ha/nao ha imagem).
         *
         * @param  object  $doc          Documento HTML
         * @param  string  $title        Titulo da pagina
         * @param  string  $description  Meta description resolvida
         * @param  string  $ogImage      URL absoluta da imagem (ou '')
         * @return void
         */
        private static function applyTwitterCards($doc, string $title, string $description, string $ogImage): void
        {
            $tem = function ($k) use ($doc) {
                return (string) $doc->getMetaData($k) !== '';
            };

            if (!$tem('twitter:card'))  { $doc->setMetaData('twitter:card', $ogImage !== '' ? 'summary_large_image' : 'summary'); }
            if (!$tem('twitter:title')) { $doc->setMetaData('twitter:title', $title); }
            if ($description !== '') {
                $doc->setMetaData('twitter:description', $description);
            }
            if ($ogImage !== '') {
                $doc->setMetaData('twitter:image', $ogImage);
            }
        }

        /**
         * C1 — Injeta os schemas globais Organization e WebSite (com SearchAction)
         * como JSON-LD, apenas na pagina inicial, para nao repetir em cada URL.
         *
         * @param  object  $doc     Documento HTML
         * @param  Registry|null  $params  Parametros do template
         * @param  object  $app     Aplicacao Joomla
         * @return void
         */
        public static function injectGlobalJsonLd($doc, $params, $app): void
        {
            if (!is_object($doc) || !method_exists($doc, 'addCustomTag') || !self::isHome($app)) {
                return;
            }

            $base     = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');
            $sitename = (string) $app->get('sitename');

            $organization = [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => $sitename,
                'url'      => $base . '/',
            ];
            $logo = self::resolveLogoUrl($params, $base);
            if ($logo !== '') {
                $organization['logo'] = $logo;
            }
            self::addJsonLd($doc, $organization);

            $website = [
                '@context'        => 'https://schema.org',
                '@type'           => 'WebSite',
                'name'            => $sitename,
                'url'             => $base . '/',
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => $base . '/index.php?option=com_search&searchword={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];
            self::addJsonLd($doc, $website);
        }

        /**
         * Verifica se ja existe um <link rel="canonical"> no head (definido por
         * outro componente), para nao emitir um segundo.
         */
        private static function hasCanonical($doc): bool
        {
            $found = false;
            try {
                $head  = method_exists($doc, 'getHeadData') ? $doc->getHeadData() : [];
                $links = $head['links'] ?? [];
                foreach ($links as $data) {
                    if (is_array($data) && ($data['relation'] ?? '') === 'canonical') {
                        $found = true;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                $found = false;
            }
            return $found;
        }

        /**
         * Resolve a URL absoluta do logo do template (para og:image/JSON-LD).
         * Aceita URL absoluta ou caminho relativo; descarta o sufixo "#joomlaImage://"
         * que o campo media pode anexar. Retorna '' quando nao ha logo.
         */
        private static function resolveLogoUrl($params, string $base): string
        {
            $logo = $params ? (string) $params->get('logoFile', '') : '';
            $logo = explode('#', $logo)[0];
            if ($logo === '') {
                return '';
            }
            if (preg_match('#^https?://#i', $logo)) {
                return $logo;
            }
            return $base . '/' . ltrim($logo, '/');
        }

        /**
         * Locale BCP-47 com underscore para og:locale (ex.: "pt_BR").
         */
        private static function currentLocale($app): string
        {
            try {
                $tag = method_exists($app, 'getLanguage') ? (string) $app->getLanguage()->getTag() : '';
                if ($tag === '') {
                    $tag = (string) $app->get('language', '');
                }
                return $tag !== '' ? str_replace('-', '_', $tag) : '';
            } catch (\Throwable $e) {
                return '';
            }
        }

        /**
         * True quando a requisicao corresponde ao item de menu padrao (home).
         */
        private static function isHome($app): bool
        {
            try {
                $menu = (is_object($app) && method_exists($app, 'getMenu')) ? $app->getMenu() : null;
                if (!$menu) {
                    return false;
                }
                $active  = $menu->getActive();
                $default = $menu->getDefault();
                return $active && $default && (int) $active->id === (int) $default->id;
            } catch (\Throwable $e) {
                return false;
            }
        }

        /**
         * Serializa um array como bloco JSON-LD e o injeta no <head>.
         * JSON_HEX_TAG protege contra fechamento prematuro de </script>.
         */
        private static function addJsonLd($doc, array $data): void
        {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
            if ($json !== false) {
                $doc->addCustomTag('<script type="application/ld+json">' . $json . '</script>');
            }
        }

        /**
         * Converte um valor hexadecimal (#RGB ou #RRGGBB) na tripla "R, G, B"
         * usada pelas variaveis `--bs-*-rgb` do Bootstrap 5.
         */
        public static function hexToRgb($hex): string
        {
            $hex = is_string($hex) ? trim($hex) : '';
            $hex = ltrim($hex, '#');

            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
                return self::RGB_FALLBACK;
            }

            return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
        }

        /**
         * Aplica os atributos ARIA compartilhados pelos chromes de modulo
         * (card/noCard) quando a tag do modulo NAO e <div>: associa o titulo via
         * aria-labelledby/id quando ha titulo visivel, ou aria-label quando nao
         * ha. Bloco identico nos dois chromes (dedup E2/#48), por referencia para
         * preencher os arrays de atributos ja montados no chrome.
         *
         * @param  array   $moduleAttribs  Atributos do elemento do modulo (por ref)
         * @param  array   $headerAttribs  Atributos do cabecalho (por ref)
         * @param  object  $module         Objeto do modulo (showtitle/id/title)
         * @param  string  $moduleTag      Tag do modulo (div, section, aside...)
         * @return void
         */
        public static function applyChromeAria(array &$moduleAttribs, array &$headerAttribs, $module, string $moduleTag): void
        {
            if ($moduleTag === 'div') {
                return;
            }
            if ($module->showtitle) {
                $moduleAttribs['aria-labelledby'] = 'mod-' . $module->id;
                $headerAttribs['id']              = 'mod-' . $module->id;
            } else {
                $moduleAttribs['aria-label'] = htmlspecialchars((string) $module->title, ENT_QUOTES, 'UTF-8');
            }
        }

        /**
         * Monta o cabecalho `<headerTag ...>titulo</headerTag>` do modulo —
         * identico nos chromes card/noCard (dedup E3/#48). O titulo NAO e escapado
         * aqui, preservando o comportamento atual (o mod_* entrega o titulo pronto
         * para saida, como nos chromes do core do Joomla).
         *
         * @param  string  $headerTag      Tag do cabecalho (h3, h2...)
         * @param  array   $headerAttribs  Atributos do cabecalho
         * @param  string  $title          Titulo do modulo (saida pronta)
         * @return string                  HTML do cabecalho
         */
        public static function buildChromeHeader(string $headerTag, array $headerAttribs, string $title): string
        {
            return '<' . $headerTag . ' ' . ArrayHelper::toString($headerAttribs) . '>' . $title . '</' . $headerTag . '>';
        }

        /**
         * Monta o HTML do logo do cabecalho: imagem com prioridade de carga
         * (eager + fetchpriority) ou, sem logoFile, o titulo do site como texto.
         * Extraido do index.php para enxugar a montagem inline. O $sitename deve
         * chegar JA escapado (vai cru em alt/title, como no index.php original).
         *
         * Performance (F2/CLS): quando a URL do logo traz as dimensoes
         * intrinsecas (campos `width`/`height` que o Joomla embute no
         * `#joomlaImage://...?width=W&height=H`), reserva a ALTURA proporcional
         * ao `logoWidth` — assim o navegador nao "pula" o layout ao carregar a
         * imagem (Cumulative Layout Shift). Sem essas dimensoes, mantem
         * `height: auto` (comportamento anterior).
         *
         * @param  Registry|null  $params    Parametros do template
         * @param  string         $sitename  Nome do site, JA escapado
         * @param  string         $rootUrl   Uri::root(false) — base do src
         * @return string                    HTML do logo
         */
        public static function buildLogo($params, string $sitename, string $rootUrl): string
        {
            $logoWidth = (int) ($params ? $params->get('logoWidth', 150) : 150);
            $logoFile  = $params ? $params->get('logoFile') : '';

            if ($logoFile) {
                $heightTag   = '';
                $heightStyle = 'height: auto;';
                if (
                    preg_match('/[?&]width=(\d+)/', $logoFile, $mw)
                    && preg_match('/[?&]height=(\d+)/', $logoFile, $mh)
                    && (int) $mw[1] > 0 && (int) $mh[1] > 0
                ) {
                    $logoHeight  = (int) round($logoWidth * (int) $mh[1] / (int) $mw[1]);
                    $heightTag   = ' height="' . $logoHeight . '"';
                    $heightStyle = 'height: ' . $logoHeight . 'px;';
                }

                return '<img src="' . $rootUrl . htmlspecialchars($logoFile, ENT_QUOTES) . '" alt="' . $sitename . '" title="' . $sitename . '" width="' . $logoWidth . '"' . $heightTag . ' style="width: ' . $logoWidth . 'px; ' . $heightStyle . '" loading="eager" fetchpriority="high" decoding="async" />';
            }

            $siteTitle = $params ? $params->get('siteTitle', $sitename) : $sitename;
            return '<span class="site-title" title="' . $sitename . '">' . htmlspecialchars((string) $siteTitle, ENT_COMPAT, 'UTF-8') . '</span>';
        }

        /**
         * Classe Bootstrap da coluna da area principal conforme as sidebars
         * presentes (output-identico ao index.php): ambas -> col-lg-6; uma ->
         * col-lg-9; nenhuma -> col-12.
         *
         * @param  mixed  $hasLeft   Contagem/flag da sidebar esquerda
         * @param  mixed  $hasRight  Contagem/flag da sidebar direita
         * @return string
         */
        public static function mainColClass($hasLeft, $hasRight): string
        {
            if ($hasLeft && $hasRight) {
                return 'col-lg-6';
            }
            if ($hasLeft || $hasRight) {
                return 'col-lg-9';
            }
            return 'col-12';
        }

        /**
         * Classe Bootstrap de cada modulo do rodape conforme o numero de colunas
         * configurado (output-identico ao index.php). No celular cada item ocupa
         * a linha inteira (col-12); divide a partir do tablet.
         *
         * @param  int  $columns  Numero de colunas do rodape (2/3/4)
         * @return string
         */
        public static function footerColClass(int $columns): string
        {
            if ($columns === 3) {
                return 'col-12 col-md-6 col-lg-4';
            }
            if ($columns === 2) {
                return 'col-12 col-md-6';
            }
            return 'col-12 col-md-6 col-lg-3';
        }

        /**
         * Fallback estático: evita fatal "method not found" em chamadas
         * legadas que possam existir em versões antigas do template.
         */
        public static function __callStatic($name, $arguments)
        {
            return '';
        }

        /**
         * Fallback de instância: idem para chamadas não-estáticas.
         */
        public function __call($name, $arguments)
        {
            return '';
        }
    }
}

if (!class_exists('TplGenericoSanitizer', false)) {
    /**
     * Saneamento de valores vindos de parametros do admin antes de serem
     * impressos em <style>, <script> ou atributos HTML. Separada do
     * TplGenericoHelper para manter cada classe enxuta.
     */
    class TplGenericoSanitizer
    {
        /**
         * Neutraliza um valor antes de imprimi-lo numa declaracao CSS inline.
         * Remove o que permitiria escapar do valor: "<" e ">" (fechar a tag
         * <style>), "{" e "}" (abrir/fechar blocos), ";" (encadear declaracoes),
         * a barra invertida (escapes CSS), comentarios e quebras de linha.
         * Aspas, virgulas, parenteses e "#" ficam — fontes ('Segoe UI',
         * sans-serif) e cores (#FFF, rgb()) continuam funcionando.
         *
         * @param  mixed  $value  Valor cru do parametro
         * @return string         Valor seguro (pode ser '')
         */
        public static function cssValue($value): string
        {
            if (!is_scalar($value)) {
                return '';
            }

            $clean = preg_replace('~/\*|\*/|[<>{};\\\\\r\n]~', '', (string) $value);

            return trim((string) $clean);
        }

        /**
         * Valida um identificador vindo de parametro (GTM, Pixel, AdSense) contra
         * um padrao. Retorna o valor aparado quando casa, ou '' quando invalido —
         * nunca o valor cru, pois ele e impresso dentro de <script>/atributos.
         *
         * @param  mixed   $value    Valor cru do parametro
         * @param  string  $pattern  Expressao regular (PCRE) completa
         * @return string
         */
        public static function validId($value, string $pattern): string
        {
            $value = is_scalar($value) ? trim((string) $value) : '';

            return ($value !== '' && preg_match($pattern, $value) === 1) ? $value : '';
        }

        /**
         * Limpa um HTML livre do admin (texto do aviso de cookies/newsletter) com
         * o filtro do core: remove <script>, atributos on*, javascript: etc.
         * Defesa em profundidade — o campo e filter="raw" por permitir
         * links/negrito.
         *
         * @param  string  $html  HTML cru
         * @return string
         */
        public static function cleanHtml(string $html): string
        {
            if ($html === '') {
                return '';
            }

            try {
                return (string) \Joomla\CMS\Filter\InputFilter::getInstance()->clean($html, 'html');
            } catch (\Throwable $e) {
                return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
            }
        }
    }
}

if (!class_exists('TplGenericoColor', false)) {
    /**
     * Calculos de cor (contraste WCAG) usados pelo helper para gerar variaveis
     * CSS que dependem de uma cor escolhida no admin.
     */
    class TplGenericoColor
    {
        private const TEXT_LIGHT = '#CBD5E1';
        private const TEXT_DARK  = '#1F2937';
        private const HEAD_DARK  = '#111827';

        /** Acima desta luminancia o fundo e considerado claro (texto escuro). */
        private const LIGHT_BG_THRESHOLD = 0.4;

        /**
         * Cor de texto legivel sobre um fundo (contraste >= AA). Fundo escuro ->
         * texto claro; fundo claro -> texto escuro. Hex invalido e tratado como
         * fundo escuro (o padrao do rodape).
         *
         * @param  string   $background  Cor de fundo em hex (#RGB/#RRGGBB)
         * @param  boolean  $strong      true = titulo/link (maximo contraste);
         *                               false = texto corrido (tom mais suave)
         * @return string                Hex do texto
         */
        public static function readableTextOn(string $background, bool $strong): string
        {
            if (self::relativeLuminance($background) > self::LIGHT_BG_THRESHOLD) {
                return $strong ? self::HEAD_DARK : self::TEXT_DARK;
            }

            return $strong ? TplGenericoHelper::WHITE_HEX : self::TEXT_LIGHT;
        }

        /**
         * Luminancia relativa WCAG (0 = preto, 1 = branco) de um hex. Hex
         * invalido retorna 0 (escuro).
         *
         * @param  string  $hex  Cor em hex (#RGB/#RRGGBB)
         * @return float
         */
        public static function relativeLuminance(string $hex): float
        {
            $rgb = array_map('trim', explode(',', TplGenericoHelper::hexToRgb($hex)));
            if (count($rgb) !== 3) {
                return 0.0;
            }

            $lin = array_map(static function ($v): float {
                $c = ((int) $v) / 255;
                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, $rgb);

            return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
        }
    }
}
