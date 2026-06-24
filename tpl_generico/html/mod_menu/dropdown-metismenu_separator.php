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
use Joomla\Utilities\ArrayHelper;

$attributes = [];

// Escapa os campos editaveis do item de menu antes de injeta-los em atributos
// HTML (ver dropdown-metismenu_url.php) — evita XSS armazenado via anchor_*/title.
$titleEsc = htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8');

if ($item->anchor_title) {
    $attributes['title'] = htmlspecialchars((string) $item->anchor_title, ENT_QUOTES, 'UTF-8');
}

$attributes['class'] = 'mod-menu__separator separator';
$attributes['class'] .= $item->anchor_css ? ' ' . htmlspecialchars((string) $item->anchor_css, ENT_QUOTES, 'UTF-8') : '';

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

    if ($itemParams->get('menu_text', 1)) {
        $linktype .= '<span class="image-title">' . $titleEsc . '</span>';
    }
}

if ($showAll && $item->deeper) {
    $attributes['class'] .= ' mm-collapsed mm-toggler mm-toggler-nolink';
    $attributes['aria-haspopup'] = 'true';
    $attributes['aria-expanded'] = 'false';
    echo '<button ' . ArrayHelper::toString($attributes) . '>' . $linktype . '</button>';
} else {
    echo '<span ' . ArrayHelper::toString($attributes) . '>' . $linktype . '</span>';
}
