#!/bin/bash
#
# setDevEnv.sh — Ambiente de desenvolvimento do template `generico` (Joomla 5).
#
# Substitui o template instalado por LINKS SIMBÓLICOS apontando para a pasta
# `tpl_generico/` deste repositório, permitindo testar as alterações ao vivo
# (editar aqui -> recarregar o site) sem reinstalar o ZIP a cada mudança.
#
# Diferente de um componente (com_*), um template Joomla 5 vive em TRÊS lugares:
#   1) templates/generico            -> tpl_generico        (index.php, html/, *.xml, joomla.asset.json)
#   2) media/templates/site/generico -> tpl_generico/media  (css/, js/, images/ — assets do WebAssetManager)
#   3) language/<lang>/tpl_generico.ini  -> tpl_generico/language/<lang>/...
#        O templateDetails.xml declara <languages>, então o Joomla INSTALA os idiomas
#        em JPATH_BASE/language/ e o HtmlDocument os carrega de lá ANTES de
#        templates/generico/language/. Sem este link, o site usa idiomas desatualizados
#        (chaves novas aparecem cruas, ex.: TPL_GENERICO_BACK_TO_TOP).
#
# Uso:
#   ./setDevEnv.sh [PATH_JOOMLA]        # cria os symlinks (faz backup .devbak do que substituir)
#   ./setDevEnv.sh --undo [PATH_JOOMLA] # remove os symlinks e restaura os backups .devbak
#
#   PATH_JOOMLA padrão: /var/www/html/automovel   (site http://localhost:8081/automovel/)
#
set -uo pipefail

TEMPLATE_NAME="generico"
LANGS="en-GB pt-BR es-ES de-DE fr-FR it-IT ja-JP zh-CN"
LANG_FILES="tpl_generico.ini tpl_generico.sys.ini"

# ---- argumentos -------------------------------------------------------------
UNDO=0
if [ "${1:-}" = "--undo" ]; then
  UNDO=1
  shift
fi
PATH_JOOMLA="${1:-/var/www/html/automovel}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCAL_TEMPLATE="${SCRIPT_DIR}/tpl_generico"
LOCAL_MEDIA="${LOCAL_TEMPLATE}/media"

DEST_TEMPLATE="${PATH_JOOMLA}/templates/${TEMPLATE_NAME}"
DEST_MEDIA="${PATH_JOOMLA}/media/templates/site/${TEMPLATE_NAME}"

# ---- sudo apenas quando o destino-pai não for gravável ----------------------
SUDO=""
if [ "$EUID" -ne 0 ] && command -v sudo >/dev/null 2>&1; then
  SUDO="sudo"
fi
# executa "$@" direto se o diretório-pai (1º arg) for gravável; senão via sudo.
run() {
  local parent="$1"; shift
  if [ -w "$parent" ] || [ -z "$SUDO" ]; then
    "$@"
  else
    $SUDO "$@"
  fi
}

# ---- validações -------------------------------------------------------------
if [ ! -d "$PATH_JOOMLA" ]; then
  echo "ERRO: instalação Joomla não encontrada: $PATH_JOOMLA" >&2
  exit 1
fi
if [ ! -f "${LOCAL_TEMPLATE}/templateDetails.xml" ]; then
  echo "ERRO: pacote do template não encontrado em: $LOCAL_TEMPLATE" >&2
  exit 1
fi

# ---- helpers ----------------------------------------------------------------
# Backup do destino antes de linkar: symlink antigo -> remove; arquivo/pasta real -> move p/ .devbak.
backup_or_remove() {
  local dest="$1" parent
  parent="$(dirname "$dest")"
  if [ -L "$dest" ]; then
    run "$parent" rm -f "$dest"
  elif [ -e "$dest" ]; then
    run "$parent" rm -rf "${dest}.devbak"
    run "$parent" mv "$dest" "${dest}.devbak"
    echo "  backup: ${dest} -> ${dest}.devbak"
  fi
}

# Cria o symlink dest -> src (faz backup do que existir).
link_item() {
  local src="$1" dest="$2" parent
  parent="$(dirname "$dest")"
  run "$parent" mkdir -p "$parent"
  backup_or_remove "$dest"
  run "$parent" ln -s "$src" "$dest"
  echo "  link: ${dest} -> ${src}"
}

# Reverte: remove o symlink e restaura o .devbak (se houver).
restore_item() {
  local dest="$1" parent
  parent="$(dirname "$dest")"
  if [ -L "$dest" ]; then
    run "$parent" rm -f "$dest"
    echo "  removido symlink: $dest"
  fi
  if [ -e "${dest}.devbak" ]; then
    run "$parent" mv "${dest}.devbak" "$dest"
    echo "  restaurado: ${dest}.devbak -> ${dest}"
  fi
}

# ---- modo --undo ------------------------------------------------------------
if [ "$UNDO" -eq 1 ]; then
  echo "Revertendo ambiente de dev em: $PATH_JOOMLA"
  restore_item "$DEST_TEMPLATE"
  restore_item "$DEST_MEDIA"
  for L in $LANGS; do
    for F in $LANG_FILES; do
      restore_item "${PATH_JOOMLA}/language/${L}/${F}"
    done
  done
  echo "Pronto. Backups .devbak restaurados (quando existiam)."
  exit 0
fi

# ---- cria os symlinks -------------------------------------------------------
echo "Configurando ambiente de dev do template '${TEMPLATE_NAME}'"
echo "  repositório: ${LOCAL_TEMPLATE}"
echo "  joomla:      ${PATH_JOOMLA}"
echo

# 1) Arquivos do template (index.php, html/, *.xml, joomla.asset.json)
link_item "$LOCAL_TEMPLATE" "$DEST_TEMPLATE"

# 2) Assets (css/, js/, images/)
link_item "$LOCAL_MEDIA" "$DEST_MEDIA"

# 3) Idiomas instalados em JPATH_BASE/language (carregados ANTES do template)
for L in $LANGS; do
  for F in $LANG_FILES; do
    [ -f "${LOCAL_TEMPLATE}/language/${L}/${F}" ] || continue
    link_item "${LOCAL_TEMPLATE}/language/${L}/${F}" "${PATH_JOOMLA}/language/${L}/${F}"
  done
done

# 4) Permissões best-effort (no /mnt/c o chown costuma ser no-op; ignorar falhas)
if [ -n "$SUDO" ] && [ "$EUID" -ne 0 ]; then
  $SUDO chown -h www-data:www-data "$DEST_TEMPLATE" "$DEST_MEDIA" 2>/dev/null || true
fi

echo
echo "Pronto. Symlinks principais:"
ls -ld "$DEST_TEMPLATE" "$DEST_MEDIA"
echo
echo "Limpe o cache do Joomla e acesse o site para validar."
echo "Para reverter:  ./setDevEnv.sh --undo ${PATH_JOOMLA}"
