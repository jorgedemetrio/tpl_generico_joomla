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
            $cssVars .= "--familia-fonte-primaria: {$get('fontFamilyPrimary', 'system-ui, sans-serif')};";
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

            return $cssVars;
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
