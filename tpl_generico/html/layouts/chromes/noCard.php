<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.generico
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

// Garante o helper carregado mesmo se este chrome for usado fora do fluxo do
// index.php (todos os entrypoints do template ja o requerem; isto e seguranca).
if (!class_exists('TplGenericoHelper', false)) {
    require_once dirname(__DIR__, 3) . '/helper.php';
}

$module  = $displayData['module'];
$params  = $displayData['params'];
$attribs = $displayData['attribs'];

if ($module->content === null || $module->content === '') {
    return;
}

$moduleTag              = $params->get('module_tag', 'div');
$moduleAttribs          = [];
$moduleAttribs['class'] = $module->position . ' no-card ' . htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$headerTag              = htmlspecialchars($params->get('header_tag', 'h3'), ENT_QUOTES, 'UTF-8');
$headerClass            = htmlspecialchars($params->get('header_class', ''), ENT_QUOTES, 'UTF-8');
$headerAttribs          = [];

// Only output a header class if one is set
if ($headerClass !== '') {
    $headerAttribs['class'] = $headerClass;
}

// Aria compartilhada (so quando a tag nao e div) e montagem do cabecalho:
// blocos identicos ao chrome card, centralizados no helper (dedup E2/E3/#48).
TplGenericoHelper::applyChromeAria($moduleAttribs, $headerAttribs, $module, $moduleTag);

$header = TplGenericoHelper::buildChromeHeader($headerTag, $headerAttribs, $module->title);
?>
<<?php echo $moduleTag; ?> <?php echo ArrayHelper::toString($moduleAttribs); ?>>
    <?php if ($module->showtitle) : ?>
        <?php echo $header; ?>
    <?php endif; ?>
    <?php echo $module->content; ?>
</<?php echo $moduleTag; ?>>
