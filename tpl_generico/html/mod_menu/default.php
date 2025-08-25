<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// This is a custom layout override for mod_menu to render a Bootstrap 5 compatible navbar.
// It supports multilevel dropdowns and accessibility attributes.

if (!function_exists('renderMenuItems')) {
    /**
     * Recursive function to render menu items.
     *
     * @param array $items     The menu items to render.
     * @param bool  $isSubmenu Whether the items are in a submenu.
     */
    function renderMenuItems($items, $isSubmenu = false)
    {
        foreach ($items as $i => $item) {
            $menuItemClass = 'nav-item';
            $hasChildren   = !empty($item->children);
            $isDropdown    = $hasChildren && !$isSubmenu;
            $isDropdownItem = $isSubmenu;

            if ($item->active) {
                $menuItemClass .= ' active';
            }
            if ($isDropdown) {
                $menuItemClass .= ' dropdown';
            }

            echo '<li class="' . $menuItemClass . '">';

            // Link attributes
            $linkClass = $isDropdownItem ? 'dropdown-item' : 'nav-link';
            $linkAttrs = [
                'class="' . $linkClass . ($isDropdown ? ' dropdown-toggle' : '') . '"',
                'title="' . htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8') . '"'
            ];
            if ($isDropdown) {
                $linkAttrs[] = 'href="#"';
                $linkAttrs[] = 'role="button"';
                $linkAttrs[] = 'data-bs-toggle="dropdown"';
                $linkAttrs[] = 'aria-expanded="false"';
            } else {
                $linkAttrs[] = 'href="' . $item->flink . '"';
            }
            if ($item->browserNav == 1) {
                $linkAttrs[] = 'target="_blank"';
            }

            echo '<a ' . implode(' ', $linkAttrs) . '>' . $item->title . '</a>';

            if ($hasChildren) {
                echo '<ul class="dropdown-menu">';
                renderMenuItems($item->children, true);
                echo '</ul>';
            }

            echo '</li>';
        }
    }
}

$id = $params->get('tag_id', 'main-menu-' . $module->id);
?>
<ul class="navbar-nav ms-auto" id="<?php echo $id; ?>">
    <?php renderMenuItems($list); ?>
</ul>
