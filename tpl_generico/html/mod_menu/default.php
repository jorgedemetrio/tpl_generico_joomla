<?php
defined('_JEXEC') or die;

// Este override usa apenas funcoes nativas do PHP e as variaveis injetadas pelo
// mod_menu ($list, $params, $module, $active_id, $path) — nao precisa de imports.

// This is a custom layout override for mod_menu to render a Bootstrap 5 compatible navbar.
// It supports multilevel dropdowns and accessibility attributes.

if (!function_exists('genericoMenuBranchHasActive')) {
    /**
     * Indica se algum item do ramo (ou um descendente) e a pagina atual. Usado
     * para destacar o item-pai de um dropdown quando um dos filhos esta ativo,
     * para o usuario saber em que secao do menu se encontra.
     *
     * @param array $items     Itens do ramo.
     * @param int   $activeId  Id do item de menu da pagina atual.
     * @param array $path      Ids do caminho ativo (item atual + ancestrais).
     *
     * @return bool
     */
    function genericoMenuBranchHasActive($items, $activeId, $path)
    {
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }
            $id = (int) ($item->id ?? 0);
            if (!empty($item->active) || ($activeId && $id === $activeId) || ($id && in_array($id, $path, true))) {
                return true;
            }
            if (!empty($item->children) && genericoMenuBranchHasActive($item->children, $activeId, $path)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('renderMenuItems')) {
    /**
     * Recursive function to render menu items.
     *
     * @param array $items     The menu items to render.
     * @param bool  $isSubmenu Whether the items are in a submenu.
     * @param int   $activeId  Id do item de menu correspondente a pagina atual.
     * @param array $path      Ids do caminho ativo (item atual + ancestrais).
     */
    function renderMenuItems($items, $isSubmenu = false, $activeId = 0, $path = [])
    {
        if (empty($items)) {
            return;
        }
        $activeId = (int) $activeId;
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }
            $title      = isset($item->title) ? (string) $item->title : '';
            $flink      = isset($item->flink) ? (string) $item->flink : '#';
            $id         = (int) ($item->id ?? 0);
            $children   = $item->children ?? [];
            $browserNav = (int) ($item->browserNav ?? 0);

            $hasChildren    = !empty($children);
            $isDropdown     = $hasChildren && !$isSubmenu;
            $isDropdownItem = $isSubmenu;

            // "E a pagina atual" (exata): apenas o item cujo id corresponde ao
            // item de menu ativo do mod_menu. SO este recebe aria-current="page"
            // — o $path inclui os ancestrais, entao nao serve para isto.
            $isCurrent = $activeId && $id === $activeId;
            // "Esta no ramo ativo": o item atual, um ancestral (pai de dropdown,
            // presente em $path) ou um item ja marcado pela lista. Usado SO para
            // o destaque visual, nunca para aria-current.
            $inActivePath = $isCurrent
                || !empty($item->active)
                || ($id && in_array($id, $path, true));
            // "Deve destacar": o ramo ativo OU um pai de dropdown cujo filho esta
            // ativo — assim a secao inteira fica evidente na navegacao.
            $highlight = $inActivePath
                || ($hasChildren && genericoMenuBranchHasActive($children, $activeId, $path));

            $menuItemClass = 'nav-item';
            if ($highlight) {
                $menuItemClass .= ' active';
            }
            if ($isDropdown) {
                $menuItemClass .= ' dropdown';
            }

            echo '<li class="' . $menuItemClass . '">';

            // Link attributes
            $linkClass = $isDropdownItem ? 'dropdown-item' : 'nav-link';
            if ($isDropdown) {
                $linkClass .= ' dropdown-toggle';
            }
            if ($highlight) {
                $linkClass .= ' active';
            }
            $linkAttrs = [
                'class="' . $linkClass . '"',
                'title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"',
            ];
            // aria-current marca a pagina atual para tecnologias assistivas. O
            // pai de um dropdown e apenas destacado (nao e, ele mesmo, a pagina).
            if ($isCurrent) {
                $linkAttrs[] = 'aria-current="page"';
            }
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
                renderMenuItems($children, true, $activeId, $path);
                echo '</ul>';
            }

            echo '</li>';
        }
    }
}

$ulId         = $params->get('tag_id', 'main-menu-' . $module->id);
// $active_id e $path sao fornecidos pelo dispatcher do mod_menu (mesmo sinal
// usado pelo layout metismenu). Guardas evitam aviso se o contexto nao os trouxer.
// $path e normalizado para inteiros para permitir comparacao estrita (in_array ..., true).
$activeMenuId = isset($active_id) ? (int) $active_id : 0;
$activePath   = (isset($path) && is_array($path)) ? array_map('intval', $path) : [];
?>
<ul class="navbar-nav me-auto" id="<?php echo $ulId; ?>">
    <?php renderMenuItems($list, false, $activeMenuId, $activePath); ?>
</ul>
