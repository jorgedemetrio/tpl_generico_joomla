# GEMINI.md - Regras de Validação e Qualidade

Instruções para o Gemini CLI neste projeto.

## Diretrizes de Qualidade
- **Joomla 5 Ready**: Utilize classes e objetos modernos do Joomla 5 (evite prefixos `J` legados).
- **CI/CD**: Garanta que o workflow de build passe nas etapas de `php -l` e `phpmd`.
- **Responsividade**: Priorize a posição `mobile-menu` para dispositivos móveis e garanta que as sidebars usem `d-none d-lg-block`.
- **Performance**: Foco em carregamento rápido e otimização de assets.

## Monitoramento de Produção
Acompanhe a versão atual por meio de:
`https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml`

## Validador local (gate de master)
Execute `.claude/skills/validacao-pre-producao/validar.sh` antes de entregar na
`master` ou gerar a tag `v*`. FAIL bloqueia: `php -l`, paridade i18n nos 8
idiomas, `<files>` do manifesto completo e `joomla.asset.json` válido. CI:
`.github/workflows/validacao-pre-master.yml`. Joomla 5 = classes COM namespace
(`Joomla\CMS\...`), sem `JFactory`/`JText`/`JRoute`. Responsividade via posição
`mobile-menu` (offcanvas full-width) e sidebars `d-none d-lg-block`.
