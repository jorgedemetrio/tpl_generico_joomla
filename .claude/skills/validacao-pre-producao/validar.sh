#!/usr/bin/env bash
#
# validar.sh — Validações de pré-publicação do template tpl_generico (Joomla 5).
#
# GATE de entrega na master: roda as checagens automatizáveis de qualidade e
# padronização do template. Quebras objetivas geram FAIL (bloqueiam); heurísticas
# geram WARN (revisar à mão).
#
# Foco: empacotamento correto (armadilha do <files>), paridade de i18n nos 8
# idiomas, assets válidos, posições coerentes e sintaxe PHP — tudo nas convenções
# do Joomla 5 (classes COM namespace; nada de objetos legados "J*").
#
# Uso:
#   .claude/skills/validacao-pre-producao/validar.sh           # tudo
#   .claude/skills/validacao-pre-producao/validar.sh --quick   # pula PHPMD
#
# Sai com código != 0 se houver qualquer FAIL.

set -uo pipefail

# ---- raiz do repo (independe do diretório de invocação) --------------------
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT" || exit 2

TPL="tpl_generico"
XML="$TPL/templateDetails.xml"
ASSET="$TPL/joomla.asset.json"
INDEX="$TPL/index.php"
LANG_DIR="$TPL/language"
LANGS=(pt-BR en-GB es-ES de-DE fr-FR it-IT ja-JP zh-CN)

QUICK=0
for a in "$@"; do
  case "$a" in
    --quick) QUICK=1 ;;
  esac
done

FAILS=0; WARNS=0; PASSES=0
c_red()  { printf '\033[31m%s\033[0m' "$1"; }
c_grn()  { printf '\033[32m%s\033[0m' "$1"; }
c_ylw()  { printf '\033[33m%s\033[0m' "$1"; }
section(){ printf '\n=== %s ===\n' "$1"; }
pass()   { PASSES=$((PASSES+1)); printf '  [%s] %s\n' "$(c_grn PASS)" "$1"; }
warn()   { WARNS=$((WARNS+1));  printf '  [%s] %s\n' "$(c_ylw WARN)" "$1"; }
fail()   { FAILS=$((FAILS+1));  printf '  [%s] %s\n' "$(c_red FAIL)" "$1"; }

# ---------------------------------------------------------------------------
section "1. Sintaxe PHP (php -l)"
if command -v php >/dev/null 2>&1; then
  mapfile -t PHP_FILES < <(find "$TPL" -name '*.php' -not -path '*/node_modules/*'; ls installTemplate.php 2>/dev/null)
  errs=0
  for f in "${PHP_FILES[@]}"; do
    [ -f "$f" ] || continue
    if ! out="$(php -l "$f" 2>&1)"; then
      fail "php -l: $f"; printf '       %s\n' "$out"; errs=1
    fi
  done
  [ "$errs" -eq 0 ] && pass "${#PHP_FILES[@]} arquivo(s) PHP sem erro de sintaxe"
else
  warn "php não encontrado no PATH — pulei php -l"
fi

# ---------------------------------------------------------------------------
section "2. Análise estática (PHPMD — advisory)"
if [ "$QUICK" -eq 1 ]; then
  warn "--quick: PHPMD pulado"
else
  PHPMD=""
  for cand in phpmd ~/.composer/vendor/bin/phpmd ~/.config/composer/vendor/bin/phpmd vendor/bin/phpmd; do
    if command -v "$cand" >/dev/null 2>&1 || [ -x "$cand" ]; then PHPMD="$cand"; break; fi
  done
  if [ -n "$PHPMD" ]; then
    RULESET="phpmd.xml"; [ -f "$RULESET" ] || RULESET="cleancode,codesize,unusedcode"
    if "$PHPMD" "$TPL" text "$RULESET" >/tmp/phpmd_tpl.txt 2>&1; then
      pass "PHPMD sem violações ($RULESET)"
    else
      n="$(grep -cE ':[0-9]+' /tmp/phpmd_tpl.txt 2>/dev/null)"; n="${n:-?}"
      warn "PHPMD apontou ~$n itens (advisory, $RULESET) — veja /tmp/phpmd_tpl.txt"
    fi
  else
    warn "PHPMD não instalado — roda na CI (build.yml)"
  fi
fi

# ---------------------------------------------------------------------------
# helper: extrai chaves de um .ini (linhas CHAVE=) ignorando comentários
ini_keys() { grep -aoE '^[A-Z0-9_]+[[:space:]]*=' "$1" 2>/dev/null | sed -E 's/[[:space:]]*=$//' | sort -u; }

check_parity() {
  local suffix="$1" label="$2"
  local base="$LANG_DIR/pt-BR/$suffix"
  [ -f "$base" ] || { fail "$label: base pt-BR ausente ($base)"; return; }
  local basekeys; basekeys="$(ini_keys "$base")"
  local nbase; nbase="$(printf '%s\n' "$basekeys" | grep -c . )"
  local ok=1
  for lang in "${LANGS[@]}"; do
    [ "$lang" = "pt-BR" ] && continue
    local f="$LANG_DIR/$lang/$suffix"
    if [ ! -f "$f" ]; then fail "$label: falta arquivo $f"; ok=0; continue; fi
    if command -v php >/dev/null 2>&1; then
      php -r '$r=@parse_ini_file($argv[1]); exit($r===false?1:0);' "$f" 2>/dev/null \
        || { fail "$label/$lang: parse_ini_file FALHOU em $f"; ok=0; }
    fi
    local missing; missing="$(comm -23 <(printf '%s\n' "$basekeys") <(ini_keys "$f"))"
    local nmiss; nmiss="$(printf '%s\n' "$missing" | grep -c . )"
    if [ "$nmiss" -gt 0 ]; then
      fail "$label/$lang: $nmiss chave(s) faltando vs pt-BR (ex.: $(printf '%s' "$missing" | head -3 | tr '\n' ' '))"; ok=0
    fi
  done
  [ "$ok" -eq 1 ] && pass "$label: paridade verificada contra pt-BR ($nbase chaves nos 8 idiomas)"
}

section "3. i18n — paridade de chaves (8 idiomas)"
check_parity "tpl_generico.ini"     "frontend .ini"
check_parity "tpl_generico.sys.ini" "sys .sys.ini"

# ---------------------------------------------------------------------------
section "4. Empacotamento — todo .php da raiz declarado em <files> do manifesto"
# Armadilha clássica do Joomla: arquivo no ZIP mas fora de <files> NÃO é instalado
# (ex.: helper.php não declarado -> fatal em produção).
if [ -f "$XML" ]; then
  declared="$(grep -oE '<filename>[^<]+</filename>' "$XML" | sed -E 's:</?filename>::g')"
  missing=0
  while IFS= read -r f; do
    base="$(basename "$f")"
    if ! printf '%s\n' "$declared" | grep -qx "$base"; then
      fail "manifesto: '$base' existe em $TPL/ mas não está em <files> (não será instalado)"; missing=1
    fi
  done < <(find "$TPL" -maxdepth 1 -name '*.php' -printf '%f\n')
  [ "$missing" -eq 0 ] && pass "Todo .php da raiz do template está declarado em <files>"
else
  fail "manifesto não encontrado: $XML"
fi

# ---------------------------------------------------------------------------
section "5. joomla.asset.json — JSON válido"
if [ -f "$ASSET" ]; then
  if command -v php >/dev/null 2>&1; then
    if php -r 'exit(json_decode(file_get_contents($argv[1]))===null?1:0);' "$ASSET" 2>/dev/null; then
      pass "joomla.asset.json é JSON válido"
    else
      fail "joomla.asset.json é JSON inválido"
    fi
  else
    warn "php ausente — pulei validação JSON"
  fi
else
  fail "joomla.asset.json não encontrado: $ASSET"
fi

# ---------------------------------------------------------------------------
section "6. Posições usadas no index.php declaradas no manifesto"
if [ -f "$XML" ] && [ -f "$INDEX" ]; then
  decl_pos="$(grep -oE '<position>[^<]+</position>' "$XML" | sed -E 's:</?position>::g' | sort -u)"
  used_pos="$(grep -oE "name=\"[a-z0-9-]+\"" "$INDEX" | sed -E 's:name="(.*)":\1:' | sort -u)"
  undeclared=0
  while IFS= read -r p; do
    [ -z "$p" ] && continue
    if ! printf '%s\n' "$decl_pos" | grep -qx "$p"; then
      warn "posição '$p' usada no index.php não está em <positions> do manifesto"; undeclared=1
    fi
  done <<< "$used_pos"
  [ "$undeclared" -eq 0 ] && pass "Posições do index.php declaradas no manifesto"
else
  warn "index.php ou manifesto ausente — pulei checagem de posições"
fi

# ---------------------------------------------------------------------------
section "7. Convenção Joomla 5 — sem objetos legados 'J*' no PHP"
# No Joomla 5 não existem JFactory/JText/JRoute/JHtml/JUri/JModuleHelper. Heurística.
legacy="$(grep -rnE '\b(JFactory|JText|JRoute|JHtml|JUri|JModuleHelper|JModuleEvent|jimport)\b' \
         "$TPL" --include='*.php' 2>/dev/null || true)"
if [ -n "$legacy" ]; then
  warn "Possível uso de objeto legado do Joomla 3 (use a classe namespaced do J5):"
  printf '%s\n' "$legacy" | head -8 | sed 's/^/       /'
else
  pass "Nenhum objeto legado 'J*' do Joomla 3 no PHP"
fi

# ---------------------------------------------------------------------------
section "8. index.html nas pastas de mídia (anti directory listing)"
missing_idx="$(find "$TPL/media" -type d \
  -exec sh -c '[ -f "$1/index.html" ] || echo "$1"' _ {} \; 2>/dev/null)"
if [ -n "$missing_idx" ]; then
  n="$(printf '%s\n' "$missing_idx" | grep -c .)"
  warn "$n pasta(s) de mídia sem index.html (ex.: $(printf '%s' "$missing_idx" | head -3 | tr '\n' ' '))"
else
  pass "Todas as pastas de mídia têm index.html"
fi

# ---------------------------------------------------------------------------
section "RESUMO"
printf '  %s passes / %s warns / %s fails\n' \
  "$(c_grn "$PASSES")" "$(c_ylw "$WARNS")" "$(c_red "$FAILS")"
if [ "$FAILS" -gt 0 ]; then
  printf '\n%s — corrija os FAIL antes de gerar o pacote/tag de produção.\n' "$(c_red 'BLOQUEADO')"
  exit 1
fi
printf '\n%s — sem FAIL. Revise os WARN manualmente (são heurísticas).\n' "$(c_grn 'OK')"
exit 0
