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

defined('_JEXEC') or die;

if (!class_exists('TplGenericoHelper', false)) {
    /**
     * Helper methods for tpl_generico template.
     */
    class TplGenericoHelper
    {
        /**
         * Build CSS variables string from template parameters.
         *
         * @param  Registry|null  $params  Template parameters
         * @return string                  CSS variables ready for inline use
         */
        public static function buildCssVars($params): string
        {
            $get = static function ($key, $default) use ($params) {
                if ($params === null) {
                    return $default;
                }
                $value = $params->get($key, $default);
                return ($value === null || $value === '') ? $default : $value;
            };

            $cssVars  = "--cor-primaria: {$get('primaryColor', '#1F4E79')};";
            $cssVars .= "--cor-secundaria: {$get('secondaryColor', '#2E7D32')};";
            $cssVars .= "--cor-cta: {$get('ctaColor', '#2F80ED')};";
            // Cor do destaque do item de menu ativo. Configuravel no admin; se vazio,
            // usa o padrao (mesmo azul do CTA). Tripla RGB para o leve fundo em rgba().
            $cssVars .= "--cor-menu-ativo: {$get('menuActiveColor', '#2F80ED')};";
            $cssVars .= '--cor-menu-ativo-rgb: ' . self::hexToRgb($get('menuActiveColor', '#2F80ED')) . ';';
            // Triplas RGB das cores de marca, usadas em rgba() (focus rings, overlays).
            $cssVars .= '--cor-primaria-rgb: ' . self::hexToRgb($get('primaryColor', '#1F4E79')) . ';';
            $cssVars .= '--cor-secundaria-rgb: ' . self::hexToRgb($get('secondaryColor', '#2E7D32')) . ';';
            $cssVars .= '--cor-cta-rgb: ' . self::hexToRgb($get('ctaColor', '#2F80ED')) . ';';
            $cssVars .= "--cor-texto: {$get('textColor', '#222222')};";
            $cssVars .= "--cor-texto-secundario: {$get('textSecondaryColor', '#6B7280')};";
            $cssVars .= "--cor-superficie-clara: {$get('surfaceLightColor', '#FFFFFF')};";
            $cssVars .= "--cor-superficie-clara-topo: {$get('surfaceLightColorTopo', '#FFFFFF')};";
            $cssVars .= "--cor-superficie-alt: {$get('surfaceAltColor', '#F5F7FA')};";
            $cssVars .= "--cor-borda: {$get('borderColor', '#E5E7EB')};";
            $cssVars .= "--espaco-interno-card: {$get('espacoInternoCard', '1.5rem')};";
            $cssVars .= "--margin-topo-card: {$get('margemTopoCard', '10px')};";
            $cssVars .= "--espaco-interno-titulo-card: {$get('espacoInternoTituloCard', '1.5rem')};";
            $cssVars .= "--margin-topo-titulo-card: {$get('margemTopoTituloCard', '10px')};";
            $cssVars .= "--cor-footer: {$get('footerColor', '#0F172A')};";
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
            $primaryRgb   = self::hexToRgb($get('primaryColor', '#1F4E79'));
            $secondaryRgb = self::hexToRgb($get('secondaryColor', '#2E7D32'));
            $ctaRgb       = self::hexToRgb($get('ctaColor', '#2F80ED'));

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
            $themeColor = (string) ($params ? $params->get('primaryColor', '#1F4E79') : '#1F4E79');
            if ($themeColor !== '') {
                $doc->setMetaData('theme-color', $themeColor);
            }

            $ogImage = self::resolveLogoUrl($params, $base);
            $option  = (string) $input->getCmd('option', '');
            $view    = (string) $input->getCmd('view', '');

            // B1 — Open Graph (compartilhamento com card; o FB Pixel ja esta ativo).
            $doc->setMetaData('og:site_name', $sitename, 'property');
            $doc->setMetaData('og:title', $title, 'property');
            $doc->setMetaData('og:type', ($option === 'com_content' && $view === 'article') ? 'article' : 'website', 'property');
            $doc->setMetaData('og:url', $canonical, 'property');
            if ($description !== '') {
                $doc->setMetaData('og:description', $description, 'property');
            }
            $locale = self::currentLocale($app);
            if ($locale !== '') {
                $doc->setMetaData('og:locale', $locale, 'property');
            }
            if ($ogImage !== '') {
                $doc->setMetaData('og:image', $ogImage, 'property');
            }

            // B2 — Twitter Cards (fallback do X quando ha/nao ha imagem).
            $doc->setMetaData('twitter:card', $ogImage !== '' ? 'summary_large_image' : 'summary');
            $doc->setMetaData('twitter:title', $title);
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
            if (!method_exists($doc, 'getHeadData')) {
                return false;
            }
            try {
                $head  = $doc->getHeadData();
                $links = $head['links'] ?? [];
                foreach ($links as $data) {
                    if (is_array($data) && ($data['relation'] ?? '') === 'canonical') {
                        return true;
                    }
                }
            } catch (\Throwable $e) {
                return false;
            }
            return false;
        }

        /**
         * Resolve a URL absoluta do logo do template (para og:image/JSON-LD).
         * Aceita URL absoluta ou caminho relativo; descarta o sufixo "#joomlaImage://"
         * que o campo media pode anexar. Retorna '' quando nao ha logo.
         */
        private static function resolveLogoUrl($params, string $base): string
        {
            $logo = $params ? (string) $params->get('logoFile', '') : '';
            if ($logo === '') {
                return '';
            }
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
        private static function hexToRgb($hex): string
        {
            $hex = is_string($hex) ? trim($hex) : '';
            $hex = ltrim($hex, '#');

            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
                return '0, 0, 0';
            }

            return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
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
