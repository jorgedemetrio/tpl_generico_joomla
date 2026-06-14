<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.generico
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Layout de componente (popups, impressao, modais). Usa o preset do proprio
 * template — versoes anteriores herdavam assets do Cassiopeia (template.generico.*,
 * theme.colors_standard, template.user) que NAO existem aqui e geravam erro.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app = Factory::getApplication();
$wa  = $this->getWebAssetManager();

require_once __DIR__ . '/helper.php';

// Aplica as cores do admin tambem na view de componente.
try {
    $params = $app->getTemplate(true)->params;
} catch (\Throwable $e) {
    $params = null;
}

HTMLHelper::_('bootstrap.framework');
$wa->usePreset('tpl_generico.preset');
$this->addStyleDeclaration(':root { ' . TplGenericoHelper::buildCssVars($params) . ' }');

$colorScheme = $params ? $params->get('colorScheme', 'light') : 'light';
$htmlTheme   = in_array($colorScheme, ['light', 'dark'], true) ? $colorScheme : 'light';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-bs-theme="<?php echo $htmlTheme; ?>">
<head>
    <jdoc:include type="metas" />
    <title><?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <jdoc:include type="styles" />
    <jdoc:include type="scripts" />
</head>
<body class="contentpane component <?php echo $this->direction === 'rtl' ? 'rtl' : ''; ?>">
    <jdoc:include type="message" />
    <jdoc:include type="component" />
</body>
</html>
