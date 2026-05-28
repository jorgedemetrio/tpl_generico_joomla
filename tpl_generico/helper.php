<?php
/**
 * Template generico - helper de compatibilidade.
 *
 * Mantido APENAS por compatibilidade com instalações antigas cujo
 * `index.php` ainda executa `require_once 'templates/generico/helper.php'`
 * e/ou referencia a classe `TplGenericoHelper`.
 *
 * O `index.php` atual deste template NÃO depende deste arquivo nem desta
 * classe. Eles existem somente para impedir erros fatais
 * ("Failed to open stream: helper.php" / "Class TplGenericoHelper not found")
 * durante a transição entre versões publicadas, até que o novo `index.php`
 * sobrescreva o antigo no servidor.
 *
 * @package   Templates.generico
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

if (!class_exists('TplGenericoHelper', false)) {
    /**
     * Stub de compatibilidade. Qualquer chamada estática ou de instância
     * retorna string vazia, evitando fatais em versões antigas do template.
     */
    class TplGenericoHelper
    {
        public static function __callStatic($name, $arguments)
        {
            return '';
        }

        public function __call($name, $arguments)
        {
            return '';
        }
    }
}
