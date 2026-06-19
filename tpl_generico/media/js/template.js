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

  // Duracao (ms) do fade-out antes de ocultar overlays (aviso de cookies e modal
  // de newsletter). Deve casar com a `transition` definida para essas classes no
  // template.css — por isso fica nomeada e num lugar so.
  var HIDE_TRANSITION_MS = 300;

  // ---------------------------------------------------------------------------
  // Helpers compartilhados (evitam repetir os mesmos padroes em cada recurso).
  // ---------------------------------------------------------------------------

  /** document.getElementById, mais curto. */
  function byId(id) {
    return document.getElementById(id);
  }

  /** Itera uma NodeList/colecao (compativel com navegadores antigos). */
  function forEach(list, fn) {
    Array.prototype.forEach.call(list, fn);
  }

  /** Forca um reflow para que a transicao seguinte de fato anime. */
  function forceReflow(el) {
    void el.offsetWidth;
  }

  /** Reage a mudancas de viewport (redimensionar + girar o aparelho). */
  function onViewportChange(fn) {
    window.addEventListener('resize', fn, { passive: true });
    window.addEventListener('orientationchange', fn, { passive: true });
  }

  /** Limita uma callback a no maximo uma execucao por frame (scroll/resize). */
  function rafThrottle(fn) {
    var ticking = false;
    return function () {
      if (ticking) {
        return;
      }
      ticking = true;
      window.requestAnimationFrame(function () {
        fn();
        ticking = false;
      });
    };
  }

  /** Le um atributo inteiro com fallback (NaN ou negativo -> fallback). */
  function intAttr(el, name, fallback) {
    var n = parseInt(el.getAttribute(name), 10);
    return (isNaN(n) || n < 0) ? fallback : n;
  }

  /** True quando o alvo (link/form) abre na mesma aba (sem target ou _self). */
  function opensInSameTab(el) {
    if (!el || typeof el.getAttribute !== 'function') {
      return false;
    }
    var target = el.getAttribute('target');
    return !(target && target !== '_self');
  }

  /** Acesso ao localStorage tolerante a falhas (modo privado, cota, etc.). */
  var safeStorage = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  // ---------------------------------------------------------------------------
  // Header: altura sincronizada + efeito "encolher ao rolar".
  // O header usa position: sticky (classe .sticky-top do Bootstrap), que ocupa
  // espaco no fluxo normal — por isso NAO ajustamos padding-top do conteudo.
  // ---------------------------------------------------------------------------
  function initHeader() {
    var header = byId('header');
    if (!header) {
      return;
    }

    function syncHeaderHeight() {
      document.documentElement.style.setProperty(
        '--generico-header-height',
        header.offsetHeight + 'px'
      );
    }

    var SCROLLED_AFTER_PX = 10; // aplica .is-scrolled apos rolar este tanto
    var onScroll = rafThrottle(function () {
      header.classList.toggle('is-scrolled', window.scrollY > SCROLLED_AFTER_PX);
    });

    // Altura inicial e em mudancas de viewport/orientacao.
    syncHeaderHeight();
    onViewportChange(syncHeaderHeight);

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
    var btn = byId('themeToggle');
    if (!btn) {
      return;
    }

    var root = document.documentElement;
    // Chave do localStorage vem do PHP via data-theme-key (fonte unica da
    // verdade, evita drift PHP<->JS). Fallback so para markup sem o atributo.
    var KEY = root.getAttribute('data-theme-key') || 'generico-theme';

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
      safeStorage.set(KEY, next);
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
      var headerEl = byId('header');
      var headerH = headerEl ? headerEl.offsetHeight : 0;
      var VIEWPORT_GAP_PX = 24; // ~1.5rem de folga abaixo do header
      var available = window.innerHeight - headerH - VIEWPORT_GAP_PX;
      forEach(sidebars, function (el) {
        el.classList.toggle('is-tall', el.scrollHeight > available);
      });
    }

    evaluate();
    onViewportChange(evaluate);
    // Recalcula apos imagens/fontes carregarem (a altura pode mudar).
    window.addEventListener('load', evaluate);
  }

  // ---------------------------------------------------------------------------
  // Voltar ao topo: so em paginas longas e fora do mobile (largura >= 768px).
  // ---------------------------------------------------------------------------
  function initBackToTop() {
    var btn = byId('backToTop');
    if (!btn) {
      return;
    }

    var MOBILE_MAX = 768;       // abaixo disso e considerado celular
    var LONG_PAGE_FACTOR = 2;   // pagina "longa" = mais de 2x a altura visivel
    var SHOW_AFTER_FACTOR = 0.6; // so aparece apos rolar 60% da altura visivel

    function isEligible() {
      return window.innerWidth >= MOBILE_MAX &&
        document.documentElement.scrollHeight > window.innerHeight * LONG_PAGE_FACTOR;
    }

    function update() {
      var show = isEligible() && window.scrollY > window.innerHeight * SHOW_AFTER_FACTOR;
      btn.classList.toggle('is-visible', show);
    }

    var onScroll = rafThrottle(update);

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

    forEach(imgs, function (img) {
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
    var el = byId('cookieNotice');
    if (!el) {
      return;
    }
    var KEY = 'generico_cookie_consent';
    var COOKIE_MAX_AGE_MS = 365 * 24 * 60 * 60 * 1000; // consentimento dura ~1 ano
    var COUNTDOWN_TICK_MS = 1000;                      // 1s entre os passos da contagem
    var already = document.cookie.split('; ').some(function (c) {
      return c.indexOf(KEY + '=') === 0;
    });
    if (already) {
      return;
    }

    var btn = byId('cookieAccept');
    var countEl = el.querySelector('.cookie-notice-countdown');
    var timeout = intAttr(el, 'data-timeout', 20);
    var remaining = timeout;
    var timerId = null;

    function persist() {
      var d = new Date();
      d.setTime(d.getTime() + COOKIE_MAX_AGE_MS);
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
      }, HIDE_TRANSITION_MS);
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
      timerId = window.setInterval(tick, COUNTDOWN_TICK_MS);
    }
  }

  // ---------------------------------------------------------------------------
  // Loader de navegacao: overlay central com spinner quando a pagina vai sair
  // (clique em link interno, envio de formulario ou unload). Some sozinho na
  // pagina seguinte (markup nasce oculto) e ao voltar pelo bfcache (pageshow).
  // ---------------------------------------------------------------------------
  function initPageLoader() {
    var el = byId('pageLoader');
    if (!el) {
      return;
    }
    var LOADER_SAFETY_MS = 12000; // se a navegacao nao acontecer, esconde sozinho
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
      forceReflow(el); // para a transicao de opacidade valer
      el.classList.add('is-active');
      if (safetyTimer) {
        window.clearTimeout(safetyTimer);
      }
      // Rede de seguranca: se a navegacao nao acontecer (ex.: download), esconde.
      safetyTimer = window.setTimeout(hide, LOADER_SAFETY_MS);
    }

    function isInternalNav(a) {
      if (!a || a.hasAttribute('download') || !opensInSameTab(a)) {
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
      if (!opensInSameTab(e.target)) {
        return;
      }
      show();
    });

    window.addEventListener('beforeunload', show);
    // Ao voltar pelo bfcache a pagina pode ser restaurada com o loader ativo.
    window.addEventListener('pageshow', hide);
  }

  // ---------------------------------------------------------------------------
  // Newsletter: convite (modal) para o visitante se cadastrar. So aparece para
  // quem NAO esta logado (o index.php so renderiza o markup nesse caso), no
  // PRIMEIRO acesso e depois de um tempo minimo no site (data-delay, em segundos,
  // medido a partir do primeiro acesso e acumulado entre paginas). Mostra no
  // maximo UMA vez por navegador. Ao enviar, valida o e-mail em JS e so entao
  // redireciona para a tela de cadastro (o e-mail vai como parametro na URL).
  // ---------------------------------------------------------------------------
  function initNewsletterModal() {
    var el = byId('newsletterModal');
    if (!el) {
      return;
    }

    var DONE_KEY = 'generico_newsletter';        // 'done' => ja mostrado/decidido
    var FIRST_KEY = 'generico_newsletter_first'; // timestamp do primeiro acesso

    // Mostra apenas no primeiro acesso: se ja foi exibido/decidido, nao repete.
    if (safeStorage.get(DONE_KEY) === 'done') {
      return;
    }

    var delay = intAttr(el, 'data-delay', 60);

    // Tempo acumulado desde o primeiro acesso (sobrevive a navegacao entre paginas).
    var now = Date.now();
    var first = parseInt(safeStorage.get(FIRST_KEY), 10);
    if (isNaN(first)) {
      first = now;
      safeStorage.set(FIRST_KEY, String(first));
    }
    var elapsed = Math.floor((now - first) / 1000);
    var remaining = Math.max(0, delay - elapsed);

    var form = el.querySelector('.newsletter-modal-form');
    var emailInput = el.querySelector('input[type="email"]');
    var errorEl = el.querySelector('.newsletter-modal-error');
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var lastFocused = null;

    function onKeydown(e) {
      if (e.key === 'Escape' || e.keyCode === 27) {
        close();
      }
    }

    function open() {
      if (!el.hasAttribute('hidden')) {
        return;
      }
      lastFocused = document.activeElement;
      el.removeAttribute('hidden');
      forceReflow(el); // para a transicao valer
      el.classList.add('is-visible');
      document.addEventListener('keydown', onKeydown);
      // So mostra uma vez: marca como concluido assim que abre.
      safeStorage.set(DONE_KEY, 'done');
      if (emailInput) {
        emailInput.focus();
      }
    }

    function close() {
      el.classList.remove('is-visible');
      document.removeEventListener('keydown', onKeydown);
      window.setTimeout(function () { el.setAttribute('hidden', ''); }, HIDE_TRANSITION_MS);
      if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
    }

    function showError(show) {
      if (emailInput) {
        emailInput.classList.toggle('is-invalid', show);
        emailInput.setAttribute('aria-invalid', show ? 'true' : 'false');
      }
      if (errorEl) {
        if (show) {
          errorEl.removeAttribute('hidden');
        } else {
          errorEl.setAttribute('hidden', '');
        }
      }
    }

    // Fecha ao clicar no fundo (fora do dialogo) ou nos botoes de dispensar.
    el.addEventListener('click', function (e) {
      if (e.target === el || (e.target.closest && e.target.closest('[data-newsletter-dismiss]'))) {
        close();
      }
    });

    if (emailInput) {
      emailInput.addEventListener('input', function () { showError(false); });
    }

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = emailInput ? emailInput.value.trim() : '';
        // Valida o e-mail em JS antes de enviar para o Joomla.
        var valid = email !== '' && emailRe.test(email) &&
          (!emailInput || typeof emailInput.checkValidity !== 'function' || emailInput.checkValidity());
        if (!valid) {
          showError(true);
          if (emailInput) { emailInput.focus(); }
          return;
        }
        var url = form.getAttribute('action') || '';
        var param = form.getAttribute('data-email-param') || 'email';
        safeStorage.set(DONE_KEY, 'done');
        if (!url) {
          close();
          return;
        }
        url += (url.indexOf('?') === -1 ? '?' : '&') +
          encodeURIComponent(param) + '=' + encodeURIComponent(email);
        window.location.assign(url);
      });
    }

    window.setTimeout(open, remaining * 1000);
  }

  onReady(function () {
    initHeader();
    initThemeToggle();
    initStickySidebars();
    initBackToTop();
    initLazyImages();
    initCookieNotice();
    initPageLoader();
    initNewsletterModal();
  });
})();
