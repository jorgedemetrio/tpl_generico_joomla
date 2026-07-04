<?php
/**
 * Template generico - campo "Backup e atualizacao" (aba Manutencao).
 *
 * Renderiza, na edicao do estilo do template, as ferramentas de manutencao:
 *  - exportar as configuracoes do estilo como JSON (download ou visualizacao);
 *  - importar um JSON exportado, preenchendo o formulario para revisao/Salvar;
 *  - verificar e aplicar atualizacao do template pelo PROPRIO com_installer do
 *    Joomla (tasks update.ajax / update.update). O core baixa o pacote, troca
 *    os arquivos (templates/ e media/) e preserva os estilos — nenhum codigo
 *    do template roda durante a copia, entao nao ha risco de um script se
 *    apagar no meio da propria atualizacao.
 *
 * A logica de tela vive em media/js/admin-configtools.js; este campo apenas
 * monta o markup, o bloco JSON de configuracao (token CSRF, URLs e textos ja
 * traduzidos) e a tag <script> com cache-buster. O campo NAO emite nenhum
 * <input> com name jform[...], portanto nada e gravado nos params por ele.
 *
 * A tag <script src> e emitida direto no HTML do campo (e nao via Web Asset
 * Manager) porque, no admin, o registro de assets do template de SITE
 * (joomla.asset.json) nao esta carregado — o caminho fisico em
 * media/templates/site/generico/ e resolvido aqui mesmo.
 *
 * @package   Templates.generico
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Campo custom type="genericoconfigtools" (carregado via addfieldpath).
 */
class JFormFieldGenericoConfigTools extends FormField
{
    /**
     * Tipo do campo (casa com o type no templateDetails.xml).
     *
     * @var string
     */
    protected $type = 'genericoconfigtools';

    /** Elemento do template no #__extensions (client site). */
    private const TEMPLATE = 'generico';

    /** Caminho, relativo a raiz do site, do JS deste painel. */
    private const JS_PATH = 'media/templates/site/generico/js/admin-configtools.js';

    /**
     * Monta o painel de manutencao (backup + atualizacao).
     *
     * @return string
     */
    protected function getInput()
    {
        $extension = $this->getExtensionInfo();
        $config    = $this->buildJsConfig($extension);
        $json      = json_encode(
            $config,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );

        $html   = [];
        $html[] = '<div class="generico-configtools" data-gct-root>';
        $html[] = '<script type="application/json" data-gct-config>' . $json . '</script>';

        // Backup: exportar (download), ver (textarea) e importar (upload).
        $html[] = '<div class="btn-toolbar gap-2 mb-2">';
        $html[] = '<button type="button" class="btn btn-primary" data-gct-action="export">'
            . Text::_('TPL_GENERICO_CT_EXPORT_BTN') . '</button>';
        $html[] = '<button type="button" class="btn btn-outline-primary" data-gct-action="view">'
            . Text::_('TPL_GENERICO_CT_VIEW_BTN') . '</button>';
        $html[] = '<button type="button" class="btn btn-outline-secondary" data-gct-action="import">'
            . Text::_('TPL_GENERICO_CT_IMPORT_BTN') . '</button>';
        $html[] = '<input type="file" accept=".json,application/json" hidden data-gct-file>';
        $html[] = '</div>';
        $html[] = '<textarea class="form-control font-monospace" rows="12" readonly hidden data-gct-json aria-label="'
            . htmlspecialchars(Text::_('TPL_GENERICO_CT_VIEW_BTN'), ENT_QUOTES, 'UTF-8') . '"></textarea>';
        $html[] = '<div role="status" data-gct-report hidden></div>';

        // Atualizacao: apenas para quem pode gerenciar o com_installer.
        if ($config['ajaxUrl'] !== '') {
            $html[] = $this->buildUpdateBlock($config['version']);
        }

        $html[] = '</div>';
        $html[] = $this->buildScriptTag();

        return implode("\n", $html);
    }

    /**
     * Bloco de atualizacao: versao instalada, verificar/atualizar e o link
     * para o Gerenciador de Atualizacoes (caminho manual equivalente).
     *
     * @param  string  $version  Versao instalada (do manifest_cache)
     * @return string
     */
    private function buildUpdateBlock(string $version): string
    {
        $managerUrl = Uri::base(true) . '/index.php?option=com_installer&view=update';

        $html   = [];
        $html[] = '<hr>';
        $html[] = '<p class="mb-2">' . Text::sprintf(
            'TPL_GENERICO_CT_INSTALLED_VERSION',
            '<strong>' . htmlspecialchars($version !== '' ? $version : '?', ENT_QUOTES, 'UTF-8') . '</strong>'
        ) . '</p>';
        $html[] = '<div class="btn-toolbar gap-2">';
        $html[] = '<button type="button" class="btn btn-outline-primary" data-gct-action="check">'
            . Text::_('TPL_GENERICO_CT_CHECK_BTN') . '</button>';
        $html[] = '<button type="button" class="btn btn-danger" data-gct-action="update" hidden>'
            . Text::_('TPL_GENERICO_CT_UPDATE_BTN') . '</button>';
        $html[] = '</div>';
        $html[] = '<div aria-live="polite" data-gct-update-status hidden></div>';
        $html[] = '<p class="mt-2 mb-0"><a href="' . htmlspecialchars($managerUrl, ENT_QUOTES, 'UTF-8') . '">'
            . Text::_('TPL_GENERICO_CT_OPEN_MANAGER') . '</a></p>';

        return implode("\n", $html);
    }

    /**
     * Configuracao consumida pelo admin-configtools.js: URLs do com_installer
     * (com token CSRF do admin), versao instalada e textos traduzidos.
     *
     * @param  array  $extension  ['id' => int, 'version' => string] ou []
     * @return array
     */
    private function buildJsConfig(array $extension): array
    {
        $token = Session::getFormToken();
        $admin = Uri::base(true);
        $eid   = (int) ($extension['id'] ?? 0);

        // Sem extension_id ou sem permissao, o bloco de atualizacao nao aparece.
        $ajaxUrl = '';
        if ($eid > 0 && $this->canManageUpdates()) {
            $ajaxUrl = $admin . '/index.php?option=com_installer&view=update&task=update.ajax'
                . '&eid=' . $eid . '&cache_timeout=0&' . $token . '=1';
        }

        return [
            'template'  => self::TEMPLATE,
            'version'   => (string) ($extension['version'] ?? ''),
            'ajaxUrl'   => $ajaxUrl,
            'updateUrl' => $admin . '/index.php?option=com_installer&task=update.update',
            'token'     => $token,
            'text'      => [
                'checking'        => Text::_('TPL_GENERICO_CT_MSG_CHECKING'),
                'uptodate'        => Text::_('TPL_GENERICO_CT_MSG_UPTODATE'),
                'available'       => Text::_('TPL_GENERICO_CT_MSG_UPDATE_AVAILABLE'),
                'confirm'         => Text::_('TPL_GENERICO_CT_MSG_UPDATE_CONFIRM'),
                'updateError'     => Text::_('TPL_GENERICO_CT_MSG_UPDATE_ERROR'),
                'importInvalid'   => Text::_('TPL_GENERICO_CT_MSG_IMPORT_INVALID'),
                'importApplied'   => Text::_('TPL_GENERICO_CT_MSG_IMPORT_APPLIED'),
                'importIgnored'   => Text::_('TPL_GENERICO_CT_MSG_IMPORT_IGNORED'),
                'importReadError' => Text::_('TPL_GENERICO_CT_MSG_IMPORT_READ_ERROR'),
                'versionDiff'     => Text::_('TPL_GENERICO_CT_MSG_VERSION_DIFF'),
            ],
        ];
    }

    /**
     * Le extension_id e versao instalada do #__extensions (manifest_cache).
     * Falha de banco nao pode derrubar a tela de edicao: retorna [].
     *
     * @return array
     */
    private function getExtensionInfo(): array
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName(['extension_id', 'manifest_cache']))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('template'))
                ->where($db->quoteName('element') . ' = ' . $db->quote(self::TEMPLATE))
                ->where($db->quoteName('client_id') . ' = 0');
            $row = $db->setQuery($query)->loadObject();
        } catch (\Throwable $e) {
            return [];
        }

        if (!$row) {
            return [];
        }

        $manifest = json_decode((string) $row->manifest_cache, true);

        return [
            'id'      => (int) $row->extension_id,
            'version' => is_array($manifest) ? (string) ($manifest['version'] ?? '') : '',
        ];
    }

    /**
     * True quando o usuario logado pode gerenciar atualizacoes de extensoes.
     *
     * @return boolean
     */
    private function canManageUpdates(): bool
    {
        try {
            $user = Factory::getApplication()->getIdentity();

            return $user !== null && $user->authorise('core.manage', 'com_installer');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Tag <script> do painel com cache-buster por filemtime (o admin nao
     * carrega o joomla.asset.json do template de site — ver docblock da classe).
     *
     * @return string
     */
    private function buildScriptTag(): string
    {
        $file  = JPATH_ROOT . '/' . self::JS_PATH;
        $stamp = is_file($file) ? '?v=' . filemtime($file) : '';
        $src   = Uri::root(true) . '/' . self::JS_PATH . $stamp;

        return '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" defer></script>';
    }
}
