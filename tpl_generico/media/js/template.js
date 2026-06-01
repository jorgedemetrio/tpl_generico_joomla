/**
 * tpl_generico — comportamento do header.
 *
 * Vanilla JS, sem dependencia de jQuery. O Joomla 5 nao embarca jQuery por
 * padrao e o template nao precisa dele.
 *
 * - Mantem a variavel CSS --generico-header-height sincronizada com a altura
 *   real do header, usada por scroll-padding-top (offset de ancoras #id).
 * - Adiciona a classe .is-scrolled ao header quando a pagina rola, permitindo
 *   o efeito "encolher ao rolar" (estilizado no CSS).
 *
 * O header usa position: sticky (classe .sticky-top do Bootstrap), que ocupa
 * espaco no fluxo normal — por isso NAO ajustamos padding-top do conteudo.
 */
(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  onReady(function () {
    var header = document.getElementById('header');
    if (!header) {
      return;
    }

    var ticking = false;

    function syncHeaderHeight() {
      document.documentElement.style.setProperty(
        '--generico-header-height',
        header.offsetHeight + 'px'
      );
    }

    function onScroll() {
      if (ticking) {
        return;
      }
      ticking = true;
      window.requestAnimationFrame(function () {
        header.classList.toggle('is-scrolled', window.scrollY > 10);
        ticking = false;
      });
    }

    // Altura inicial e em mudancas de viewport/orientacao.
    syncHeaderHeight();
    window.addEventListener('resize', syncHeaderHeight, { passive: true });
    window.addEventListener('orientationchange', syncHeaderHeight, { passive: true });

    // Efeito de encolher apenas quando o header e fixo.
    if (header.classList.contains('sticky-top')) {
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    }
  });
})();
