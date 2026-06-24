<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_menu
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filter\OutputFilter;

$attributes = [];

// Escapa os campos editaveis do item de menu antes de injeta-los em atributos
// HTML — ArrayHelper::toString / HTMLHelper::link NAO escapam valores, entao sem
// isto um editor de menu (privilegio menor que super admin) poderia injetar
// HTML/JS via anchor_*/menu_*/title (XSS armazenado).
$titleEsc = htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8');

if ($item->anchor_title) {
    $attributes['title'] = htmlspecialchars((string) $item->anchor_title, ENT_QUOTES, 'UTF-8');
}

if ($item->anchor_css) {
    $attributes['class'] = htmlspecialchars((string) $item->anchor_css, ENT_QUOTES, 'UTF-8');
}

if ($item->anchor_rel) {
    $attributes['rel'] = htmlspecialchars((string) $item->anchor_rel, ENT_QUOTES, 'UTF-8');
}

$linktype = $titleEsc;

if ($item->menu_icon) {
    $iconClass = htmlspecialchars((string) $item->menu_icon, ENT_QUOTES, 'UTF-8');
    // The link is an icon
    if ($itemParams->get('menu_text', 1)) {
        // If the link text is to be displayed, the icon is added with aria-hidden
        $linktype = '<span class="p-2 ' . $iconClass . '" aria-hidden="true"></span>' . $titleEsc;
    } else {
        // If the icon itself is the link, it needs a visually hidden text
        $linktype = '<span class="p-2 ' . $iconClass . '" aria-hidden="true"></span><span class="visually-hidden">' . $titleEsc . '</span>';
    }
} elseif ($item->menu_image) {
    // The link is an image, maybe with an own class
    $image_attributes = [];

    if ($item->menu_image_css) {
        $image_attributes['class'] = htmlspecialchars((string) $item->menu_image_css, ENT_QUOTES, 'UTF-8');
    }

    $linktype = HTMLHelper::_('image', $item->menu_image, '', $image_attributes);

    $linktype .= '<span class="image-title' . ($itemParams->get('menu_text', 1) ? '' : ' visually-hidden') . '">' . $titleEsc . '</span>';
}

if ((int) $item->browserNav === 1) {
    $attributes['target'] = '_blank';
    $attributes['rel']    = 'noopener noreferrer';

    if ($item->anchor_rel === 'nofollow') {
        $attributes['rel'] .= ' nofollow';
    }
} elseif ((int) $item->browserNav === 2) {
    $options = 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,' . $params->get('window_open');

    $attributes['onclick'] = "window.open(this.href, 'targetWindow', '" . $options . "'); return false;";
}

echo HTMLHelper::link(OutputFilter::ampReplace(htmlspecialchars($item->flink, ENT_COMPAT, 'UTF-8', false)), $linktype, $attributes);

if ($showAll && $item->deeper) {
    echo '<button class="mm-collapsed mm-toggler mm-toggler-link" aria-haspopup="true" aria-expanded="false" aria-label="' . $titleEsc . '"></button>';
}
