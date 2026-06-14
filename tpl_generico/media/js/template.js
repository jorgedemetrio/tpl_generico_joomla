/**
 * tpl_generico — comportamentos de frontend.
 *
 * Vanilla JS, sem dependencia de jQuery. O Joomla 5 nao embarca jQuery por
 * padrao e o template nao precisa dele.
 *
 * Funcionalidades:
 *  - Header: mantem --generico-header-height sincronizada com a altura real do
 *    header (usada por scroll-padding-top) e aplica .is-scrolled ao rolar.
 *  - Tema: botao de alternancia claro/escuro, persistido em localStorage.
 *  - Sidebars: desativa o "sticky" quando a coluna e mais alta que a tela, para
 *    nao deixar o ultimo item do menu lateral inalcancavel.
 *  - Voltar ao topo: botao que aparece em paginas longas no desktop.
 *  - Imagens lazy: remove o efeito shimmer (skeleton) quando a imagem carrega.
 *
 * Cada inicializador e independente: se um elemento nao existir, apenas aquele
 * recurso e ignorado, sem afetar os demais.
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

  var prefersReducedMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------------------------------------------------------------------------
  // Header: altura sincronizada + efeito "encolher ao rolar".
  // O header usa position: sticky (classe .sticky-top do Bootstrap), que ocupa
  // espaco no fluxo normal — por isso NAO ajustamos padding-top do conteudo.
  // ---------------------------------------------------------------------------
  function initHeader() {
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
  }

  // ---------------------------------------------------------------------------
  // Tema claro/escuro: botao no header, escolha persistida em localStorage.
  // O tema inicial ja e aplicado por um script inline no <head> (index.php),
  // evitando flash; aqui so tratamos o clique e o estado do botao.
  // ---------------------------------------------------------------------------
  function initThemeToggle() {
    var btn = document.getElementById('themeToggle');
    if (!btn) {
      return;
    }

    var KEY = 'generico-theme';
    var root = document.documentElement;

    function reflect() {
      var theme = root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
      var icon = btn.querySelector('i');
      if (icon) {
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
      }
      btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }

    reflect();

    btn.addEventListener('click', function () {
      var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-bs-theme', next);
      try {
        localStorage.setItem(KEY, next);
      } catch (e) {}
      reflect();
    });
  }

  // ---------------------------------------------------------------------------
  // Sidebars: o sticky so faz sentido se a coluna couber na area visivel. Se for
  // mais alta, marcamos .is-tall e o CSS devolve a coluna ao fluxo normal (rola
  // junto com a pagina), evitando o item final inalcancavel.
  // ---------------------------------------------------------------------------
  function initStickySidebars() {
    var sidebars = document.querySelectorAll('.sidebar-content');
    if (!sidebars.length) {
      return;
    }

    function evaluate() {
      var headerEl = document.getElementById('header');
      var headerH = headerEl ? headerEl.offsetHeight : 0;
      var available = window.innerHeight - headerH - 24; // ~1.5rem de folga
      Array.prototype.forEach.call(sidebars, function (el) {
        el.classList.toggle('is-tall', el.scrollHeight > available);
      });
    }

    evaluate();
    window.addEventListener('resize', evaluate, { passive: true });
    window.addEventListener('orientationchange', evaluate, { passive: true });
    // Recalcula apos imagens/fontes carregarem (a altura pode mudar).
    window.addEventListener('load', evaluate);
  }

  // ---------------------------------------------------------------------------
  // Voltar ao topo: so em paginas longas e fora do mobile (largura >= 768px).
  // ---------------------------------------------------------------------------
  function initBackToTop() {
    var btn = document.getElementById('backToTop');
    if (!btn) {
      return;
    }

    var MOBILE_MAX = 768; // abaixo disso e considerado celular
    var ticking = false;

    function isEligible() {
      return window.innerWidth >= MOBILE_MAX &&
        document.documentElement.scrollHeight > window.innerHeight * 2;
    }

    function update() {
      var show = isEligible() && window.scrollY > window.innerHeight * 0.6;
      btn.classList.toggle('is-visible', show);
    }

    function onScroll() {
      if (ticking) {
        return;
      }
      ticking = true;
      window.requestAnimationFrame(function () {
        update();
        ticking = false;
      });
    }

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    });
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    update();
  }

  // ---------------------------------------------------------------------------
  // Imagens lazy: remove o shimmer (skeleton) quando a imagem termina de carregar.
  // ---------------------------------------------------------------------------
  function initLazyImages() {
    var imgs = document.querySelectorAll('img[loading="lazy"]');
    if (!imgs.length) {
      return;
    }

    Array.prototype.forEach.call(imgs, function (img) {
      if (img.complete && img.naturalWidth > 0) {
        img.classList.add('is-loaded');
        return;
      }
      var done = function () { img.classList.add('is-loaded'); };
      img.addEventListener('load', done, { once: true });
      img.addEventListener('error', done, { once: true });
    });
  }

  // ---------------------------------------------------------------------------
  // Aviso de cookies: banner discreto no rodape. So aparece se ainda nao houver
  // o cookie de consentimento; aceita no clique ou automaticamente apos o tempo
  // configurado, gravando um cookie de longa duracao para nao repetir. Nao ha
  // opcao de recusar — o site depende de cookies essenciais.
  // ---------------------------------------------------------------------------
  function initCookieNotice() {
    var el = document.getElementById('cookieNotice');
    if (!el) {
      return;
    }
    var KEY = 'generico_cookie_consent';
    var already = document.cookie.split('; ').some(function (c) {
      return c.indexOf(KEY + '=') === 0;
    });
    if (already) {
      return;
    }

    var btn = document.getElementById('cookieAccept');
    var countEl = el.querySelector('.cookie-notice-countdown');
    var timeout = parseInt(el.getAttribute('data-timeout'), 10);
    if (isNaN(timeout) || timeout < 0) {
      timeout = 20;
    }
    var remaining = timeout;
    var timerId = null;

    function persist() {
      var d = new Date();
      d.setTime(d.getTime() + 365 * 24 * 60 * 60 * 1000);
      var secure = window.location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = KEY + '=1; expires=' + d.toUTCString() + '; path=/; SameSite=Lax' + secure;
    }

    function dismiss() {
      if (timerId) {
        window.clearInterval(timerId);
        timerId = null;
      }
      el.classList.remove('is-visible');
      window.setTimeout(function () {
        el.setAttribute('hidden', '');
      }, 300);
    }

    function accept() {
      persist();
      dismiss();
    }

    function tick() {
      remaining -= 1;
      if (countEl && remaining > 0) {
        countEl.textContent = ' (' + remaining + ')';
      }
      if (remaining <= 0) {
        accept();
      }
    }

    el.removeAttribute('hidden');
    if (countEl && timeout > 0) {
      countEl.textContent = ' (' + remaining + ')';
    }
    window.requestAnimationFrame(function () {
      el.classList.add('is-visible');
    });

    if (btn) {
      btn.addEventListener('click', accept);
    }
    if (timeout > 0) {
      timerId = window.setInterval(tick, 1000);
    }
  }

  // ---------------------------------------------------------------------------
  // Loader de navegacao: overlay central com spinner quando a pagina vai sair
  // (clique em link interno, envio de formulario ou unload). Some sozinho na
  // pagina seguinte (markup nasce oculto) e ao voltar pelo bfcache (pageshow).
  // ---------------------------------------------------------------------------
  function initPageLoader() {
    var el = document.getElementById('pageLoader');
    if (!el) {
      return;
    }
    var safetyTimer = null;

    function hide() {
      if (safetyTimer) {
        window.clearTimeout(safetyTimer);
        safetyTimer = null;
      }
      el.classList.remove('is-active');
      el.setAttribute('hidden', '');
    }

    function show() {
      if (!el.hasAttribute('hidden') && el.classList.contains('is-active')) {
        return;
      }
      el.removeAttribute('hidden');
      void el.offsetWidth; // forca reflow para a transicao de opacidade valer
      el.classList.add('is-active');
      if (safetyTimer) {
        window.clearTimeout(safetyTimer);
      }
      // Rede de seguranca: se a navegacao nao acontecer (ex.: download), esconde.
      safetyTimer = window.setTimeout(hide, 12000);
    }

    function isInternalNav(a) {
      if (!a || a.hasAttribute('download')) {
        return false;
      }
      var target = a.getAttribute('target');
      if (target && target !== '_self') {
        return false;
      }
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#') {
        return false;
      }
      var proto = (a.protocol || '').toLowerCase();
      if (proto === 'mailto:' || proto === 'tel:' || proto === 'javascript:') {
        return false;
      }
      // Mudanca apenas de hash na mesma URL nao recarrega a pagina.
      return a.href.split('#')[0] !== window.location.href.split('#')[0];
    }

    document.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return;
      }
      var a = e.target.closest ? e.target.closest('a[href]') : null;
      if (isInternalNav(a)) {
        show();
      }
    });

    document.addEventListener('submit', function (e) {
      if (e.defaultPrevented) {
        return;
      }
      var target = e.target && e.target.getAttribute ? e.target.getAttribute('target') : null;
      if (target && target !== '_self') {
        return;
      }
      show();
    });

    window.addEventListener('beforeunload', show);
    // Ao voltar pelo bfcache a pagina pode ser restaurada com o loader ativo.
    window.addEventListener('pageshow', hide);
  }

  onReady(function () {
    initHeader();
    initThemeToggle();
    initStickySidebars();
    initBackToTop();
    initLazyImages();
    initCookieNotice();
    initPageLoader();
  });
})();
