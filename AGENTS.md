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
