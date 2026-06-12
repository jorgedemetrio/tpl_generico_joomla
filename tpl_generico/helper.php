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
