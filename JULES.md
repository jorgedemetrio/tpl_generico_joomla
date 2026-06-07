# JULES.md - Regras de Validação e Qualidade

Orientações de qualidade para o template Joomla 5.

## Validações Obrigatórias
1. **PHP Lint**: `php -l` em todos os arquivos modificados.
2. **PHPMD**: Análise não bloqueante para melhoria contínua.
3. **Sonar**: Verificação de segurança e vulnerabilidades.
4. **Responsividade**: Validação da nova área `mobile-menu` e ocultação de sidebars em telas pequenas.

## Informações de Produção
XML de atualização: `https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml`

## Validador local (gate de master)
Rode `.claude/skills/validacao-pre-producao/validar.sh` antes de entregar na
`master`/criar tag `v*`. FAIL bloqueia: `php -l`, paridade i18n (8 idiomas),
`<files>` do manifesto, `joomla.asset.json` válido. CI:
`.github/workflows/validacao-pre-master.yml`. Use classes namespaced do Joomla 5
(sem objetos legados `J*`). Responsividade: `mobile-menu` full-width + sidebars
ocultas abaixo de `lg`.
