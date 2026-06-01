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
        if (empty($items)) {
            return;
        }
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }
            $title      = isset($item->title) ? (string) $item->title : '';
            $flink      = isset($item->flink) ? (string) $item->flink : '#';
            $active     = !empty($item->active);
            $children   = $item->children ?? [];
            $browserNav = (int) ($item->browserNav ?? 0);

            $hasChildren    = !empty($children);
            $isDropdown     = $hasChildren && !$isSubmenu;
            $isDropdownItem = $isSubmenu;

            $menuItemClass = 'nav-item';
            if ($active) {
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
                'title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"',
            ];
            if ($isDropdown) {
                $linkAttrs[] = 'href="#"';
                $linkAttrs[] = 'role="button"';
                $linkAttrs[] = 'data-bs-toggle="dropdown"';
                $linkAttrs[] = 'aria-expanded="false"';
            } else {
                $linkAttrs[] = 'href="' . htmlspecialchars($flink, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($browserNav === 1) {
                $linkAttrs[] = 'target="_blank" rel="noopener"';
            }

            echo '<a ' . implode(' ', $linkAttrs) . '>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a>';

            if ($hasChildren) {
                echo '<ul class="dropdown-menu">';
                renderMenuItems($children, true);
                echo '</ul>';
            }

            echo '</li>';
        }
    }
}

$id = $params->get('tag_id', 'main-menu-' . $module->id);
?>
<ul class="navbar-nav me-auto" id="<?php echo $id; ?>">
    <?php renderMenuItems($list); ?>
</ul>
