# AGENTS.md - Regras de Validação e Qualidade

Este documento define as regras de validação para agentes que colaboram neste repositório.

## Regras de Validação (CI/CD)
- **Sintaxe PHP**: Todos os arquivos `.php` devem ser validados com `php -l`.
- **Análise Estática (PHPMD)**: O projeto utiliza `phpmd` para detectar códigos complexos, não utilizados ou com nomenclatura fora do padrão.
  - No Joomla 5, ignore avisos sobre objetos que não possuem o prefixo `J`.
- **SonarQube**: Verificação de segurança, bugs e code smells.
- **Performance e Responsividade**: Foco em mobile-first e otimização de assets.

## Versão em Produção
A versão oficial em produção pode ser consultada no arquivo:
`https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml`
(Busque sempre a versão com o maior valor numérico).

## Validador local (gate de master)
Antes de mergear/abrir PR para `master` ou criar a tag `v*`, rode:
`.claude/skills/validacao-pre-producao/validar.sh` (FAIL bloqueia: `php -l`,
paridade i18n nos 8 idiomas, `<files>` do manifesto completo, `joomla.asset.json`
válido). Roda também na CI em `.github/workflows/validacao-pre-master.yml`.
No Joomla 5 use classes COM namespace (`Joomla\CMS\Factory`, `HTMLHelper`,
`Text`, `Route`, `Uri`, `ModuleHelper`) — nada de `JFactory`/`JText`/`JRoute`.
Responsividade: posição `mobile-menu` (offcanvas full-width) + sidebars
`d-none d-lg-block`.
