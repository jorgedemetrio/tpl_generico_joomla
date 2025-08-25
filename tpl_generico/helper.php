<?php
use Joomla\CMS\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Helper methods for tpl_generico template.
 */
class TplGenericoHelper
{
    /**
     * Build CSS variables string from template parameters.
     *
     * @param  Registry  $params  Template parameters
     * @return string             CSS variables ready for inline use
     */
    public static function buildCssVars(Registry $params): string
    {
        $cssVars  = "--cor-primaria: {$params->get('primaryColor', '#1F4E79')};";
        $cssVars .= "--cor-secundaria: {$params->get('secondaryColor', '#2E7D32')};";
        $cssVars .= "--cor-cta: {$params->get('ctaColor', '#2F80ED')};";
        $cssVars .= "--cor-texto: {$params->get('textColor', '#222222')};";
        $cssVars .= "--cor-texto-secundario: {$params->get('textSecondaryColor', '#6B7280')};";
        $cssVars .= "--cor-superficie-clara: {$params->get('surfaceLightColor', '#FFFFFF')};";
        $cssVars .= "--cor-superficie-clara-topo: {$params->get('surfaceLightColorTopo', '#FFFFFF')};";
        $cssVars .= "--cor-superficie-alt: {$params->get('surfaceAltColor', '#F5F7FA')};";
        $cssVars .= "--cor-borda: {$params->get('borderColor', '#E5E7EB')};";
        $cssVars .= "--espaco-interno-card: {$params->get('espacoInternoCard', '1.5rem')};";
        $cssVars .= "--margin-topo-card: {$params->get('margemTopoCard', '10px')};";
        $cssVars .= "--espaco-interno-titulo-card: {$params->get('espacoInternoTituloCard', '1.5rem')};";
        $cssVars .= "--margin-topo-titulo-card: {$params->get('margemTopoTituloCard', '10px')};";
        $cssVars .= "--cor-footer: {$params->get('footerColor', '#0F172A')};";
        $cssVars .= "--familia-fonte-primaria: {$params->get('fontFamilyPrimary', 'system-ui, sans-serif')};";
        $cssVars .= "--tamanho-base-fonte: {$params->get('fontSizeBase', '1rem')};";
        $cssVars .= "--peso-fonte-normal: {$params->get('fontWeightNormal', '400')};";
        $cssVars .= "--peso-fonte-titulos: {$params->get('fontWeightHeadings', '700')};";
        $cssVars .= "--raio-borda-global: {$params->get('borderRadius', '4')}px;";

        $spacing = $params->get('verticalSpacing', 'M');
        $spacingValue = '2rem';
        if ($spacing === 'S') {
            $spacingValue = '1rem';
        } elseif ($spacing === 'L') {
            $spacingValue = '3rem';
        }
        $cssVars .= "--espacamento-vertical-global: {$spacingValue};";

        return $cssVars;
    }
}
