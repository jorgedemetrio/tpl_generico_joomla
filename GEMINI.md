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
