/* Shared lesson behaviors; all image assets remain external in /graphics/. */
(function () {
  'use strict';
  var root = document.documentElement;
  var knownKeys = ['apostila-tema', 'spb-periodo-composto-theme', 'spb_portais_tema', 'spb-theme'];

  function readSavedTheme() {
    /* Mantém a preferência específica da página como prioridade, sem misturar valores antigos de outra aula. */
    var preferredKeys = document.querySelector('.page-wrapper')
      ? ['spb-periodo-composto-theme', 'apostila-tema', 'spb_portais_tema', 'spb-theme']
      : ['apostila-tema', 'spb-periodo-composto-theme', 'spb_portais_tema', 'spb-theme'];
    try {
      for (var i = 0; i < preferredKeys.length; i++) {
        var value = localStorage.getItem(preferredKeys[i]);
        if (value === 'dark' || value === 'light') return value;
      }
    } catch (e) {}
    if (root.getAttribute('data-theme') === 'dark' || root.classList.contains('dark-mode')) return 'dark';
    if (root.getAttribute('data-theme') === 'light') return 'light';
    try {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
    } catch (e) {}
    return 'light';
  }

  function drawThemeIcon(button, isDark) {
    button.innerHTML = isDark
      ? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"></path></svg>'
      : '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
    var label = isDark ? 'Alternar para tema claro' : 'Alternar para tema escuro';
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    button.setAttribute('aria-pressed', String(isDark));
    if (button.hasAttribute('aria-checked')) button.setAttribute('aria-checked', String(isDark));
  }

  function setupThemeToggle() {
    var button = document.querySelector('#themeToggle, #botao-alternar-tema, .botao-tema, .theme-switch-btn, .lesson-ui-theme-toggle');
    if (!button) {
      button = document.createElement('button');
      button.type = 'button';
      button.setAttribute('aria-label', 'Alternar para tema escuro');
    } else {
      /* Clone para remover ouvintes legados e evitar que dois controladores alternem o tema. */
      var replacement = button.cloneNode(true);
      button.parentNode.replaceChild(replacement, button);
      button = replacement;
    }
    button.id = 'themeToggle';
    button.classList.add('lesson-ui-theme-toggle');
    /* Posição no topo do documento, não na viewport; o controle sai da tela ao rolar. */
    if (button.parentNode !== document.body || document.body.firstElementChild !== button) {
      document.body.insertBefore(button, document.body.firstChild);
    }

    var theme = readSavedTheme();
    function applyTheme(nextTheme, persist) {
      var dark = nextTheme === 'dark';
      root.classList.toggle('dark-mode', dark);
      root.setAttribute('data-theme', dark ? 'dark' : 'light');
      theme = dark ? 'dark' : 'light';
      drawThemeIcon(button, dark);
      if (persist) {
        try { knownKeys.forEach(function (key) { localStorage.setItem(key, theme); }); } catch (e) {}
      }
    }
    applyTheme(theme, false);
    button.addEventListener('click', function () {
      applyTheme(theme === 'dark' ? 'light' : 'dark', true);
    });
  }

  function getHeadingCandidates() {
    var main = document.querySelector('main') || document.body;
    /* Primeiro: capítulos numerados isolados no padrão das apostilas mais antigas. */
    var paragraphs = Array.prototype.slice.call(main.querySelectorAll('section.para'));
    var numbered = paragraphs.filter(function (el) {
      var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
      var directStrong = el.querySelector(':scope > strong');
      return !!directStrong && /^\d{1,2}\.\s+/.test(text) && text.length <= 150;
    });
    if (numbered.length >= 2) return numbered;

    /* Segundo: títulos H2 existentes; H3 é alternativa para modelos sem H2. */
    var h2 = Array.prototype.slice.call(main.querySelectorAll('h2'))
      .filter(function (el) { return (el.textContent || '').trim().length > 3; });
    if (h2.length >= 2) return h2;
    var h3 = Array.prototype.slice.call(main.querySelectorAll('h3'))
      .filter(function (el) { return (el.textContent || '').trim().length > 3; });
    return h3.length >= 2 ? h3 : [];
  }

  function setupToc() {
    var nav = document.querySelector('.slim-toc');
    if (!nav) {
      var headings = getHeadingCandidates();
      if (headings.length < 2) return;
      nav = document.createElement('nav');
      nav.className = 'slim-toc';
      nav.setAttribute('aria-label', 'Sumário de navegação rápida');
      var list = document.createElement('ul');
      list.className = 'slim-toc-list';
      nav.appendChild(list);
      headings.forEach(function (heading, index) {
        if (!heading.id) heading.id = 'lesson-section-' + (index + 1);
        var match = (heading.textContent || '').replace(/\s+/g, ' ').trim().match(/^(\d{1,2})\.\s+(.*)$/);
        var number = match ? match[1] : String(index + 1);
        var label = match ? match[2] : (heading.textContent || '').replace(/\s+/g, ' ').trim();
        var item = document.createElement('li');
        var link = document.createElement('a');
        link.className = 'slim-toc-link';
        link.href = '#' + heading.id;
        link.title = number + '. ' + label;
        var badge = document.createElement('span');
        badge.className = 'toc-num';
        badge.textContent = number;
        var text = document.createElement('span');
        text.className = 'toc-text';
        text.textContent = label;
        link.appendChild(badge);
        link.appendChild(text);
        item.appendChild(link);
        list.appendChild(item);
      });
      document.body.insertBefore(nav, document.body.firstChild);
    }

    var links = Array.prototype.slice.call(nav.querySelectorAll('.slim-toc-link'));
    if (!links.length) return;
    var targets = links.map(function (link) {
      var href = link.getAttribute('href') || '';
      if (href.charAt(0) !== '#') return null;
      return document.getElementById(href.slice(1));
    }).filter(Boolean);
    function updateActive() {
      var y = window.scrollY + Math.max(100, window.innerHeight * 0.2);
      var active = targets[0];
      targets.forEach(function (target) {
        if (target.getBoundingClientRect().top + window.scrollY <= y) active = target;
      });
      links.forEach(function (link) {
        link.classList.toggle('active', !!active && link.getAttribute('href') === '#' + active.id);
      });
    }
    window.addEventListener('scroll', updateActive, { passive: true });
    window.addEventListener('resize', updateActive, { passive: true });
    updateActive();
  }

  function init() {
    setupThemeToggle();
    setupToc();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
