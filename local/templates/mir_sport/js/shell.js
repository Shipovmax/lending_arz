/*
 * Мир спорта: behaviour of the site shell (header menus, search field, footer, cookie banner).
 * Extracted from design/assets/js/main.js. Page scripts (filters, product, cart) are not included:
 * Bitrix components replace them. Keep the data-* hooks in header.php / footer.php.
 */
(function () {
  'use strict';

  function init() {
    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var isMobile = function () { return window.matchMedia('(max-width: 767px)').matches; };

    /* Storage that never throws (private mode, blocked cookies). */
    var store = {
      get: function (key, fallback) {
        try { var v = window.localStorage.getItem(key); return v === null ? fallback : JSON.parse(v); } catch (e) { return fallback; }
      },
      set: function (key, value) {
        try { window.localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* ignore */ }
      }
    };
    var setIcon = function (btn, id) { var use = $('use', btn); if (use) use.setAttribute('href', '#' + id); };

    var megaBtn = $('[data-megamenu-toggle]');
    var mega = $('[data-megamenu]');
    var menuBtn = $('[data-menu-toggle]');
    var mobileMenu = $('[data-mobile-menu]');
    var search = $('[data-search]');
    var input = search && $('[data-search-input]', search);
    var suggest = search && $('[data-suggest]', search);
    var clearBtn = search && $('[data-search-clear]', search);

    function closeMega() {
      if (!mega || mega.hidden) return;
      mega.hidden = true;
      megaBtn.setAttribute('aria-expanded', 'false');
      setIcon(megaBtn, 'i-menu');
    }
    function closeMobileMenu() {
      if (!mobileMenu || mobileMenu.hidden) return;
      mobileMenu.hidden = true;
      menuBtn.setAttribute('aria-expanded', 'false');
      menuBtn.setAttribute('aria-label', 'Открыть меню');
      setIcon(menuBtn, 'i-menu');
    }
    function openSearch() {
      if (!search) return;
      search.classList.add('is-open');
      if (suggest && suggest.innerHTML.trim()) suggest.hidden = false;
    }
    function closeSearch() {
      if (!search) return;
      search.classList.remove('is-open');
      if (suggest) suggest.hidden = true;
    }

    if (megaBtn && mega) {
      megaBtn.addEventListener('click', function () {
        var open = mega.hidden;
        closeSearch();
        mega.hidden = !open;
        megaBtn.setAttribute('aria-expanded', String(open));
        setIcon(megaBtn, open ? 'i-close' : 'i-menu');
        if (open) { var current = $('[data-mm-dir][aria-selected="true"]', mega); if (current) current.focus(); }
      });
      $$('[data-mm-dir]', mega).forEach(function (tab) {
        var show = function () {
          $$('[data-mm-dir]', mega).forEach(function (t) {
            var on = t === tab;
            t.setAttribute('aria-selected', String(on));
            t.setAttribute('tabindex', on ? '0' : '-1');
            document.getElementById(t.getAttribute('data-mm-dir')).hidden = !on;
          });
        };
        tab.addEventListener('click', show);
        tab.addEventListener('mouseenter', show);
        tab.addEventListener('keydown', function (e) {
          var tabs = $$('[data-mm-dir]', mega);
          var i = tabs.indexOf(tab);
          var next = e.key === 'ArrowDown' ? tabs[(i + 1) % tabs.length] : e.key === 'ArrowUp' ? tabs[(i - 1 + tabs.length) % tabs.length] : null;
          if (!next) return;
          e.preventDefault();
          next.focus();
          next.click();
        });
      });
    }

    if (menuBtn && mobileMenu) {
      menuBtn.addEventListener('click', function () {
        var open = mobileMenu.hidden;
        closeSearch();
        mobileMenu.hidden = !open;
        menuBtn.setAttribute('aria-expanded', String(open));
        menuBtn.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        setIcon(menuBtn, open ? 'i-close' : 'i-menu');
        /* The menu follows the search and the icons in the DOM, so move focus into it or Tab would skip it. */
        if (open) { var first = $('summary, a', mobileMenu); if (first) first.focus(); }
      });
    }

    /* Search field. Suggestions are filled into [data-suggest] by bitrix:search.title once it is connected. */
    if (input) {
      input.addEventListener('focus', function () { closeMega(); closeMobileMenu(); openSearch(); });
      input.addEventListener('input', function () { clearBtn.hidden = !input.value; });
      clearBtn.addEventListener('click', function () { input.value = ''; clearBtn.hidden = true; input.focus(); });
      $('[data-search-close]', search).addEventListener('click', function () {
        input.value = ''; clearBtn.hidden = true; closeSearch();
      });
    }

    document.addEventListener('click', function (e) {
      if (search && !search.contains(e.target)) closeSearch();
      if (mega && !mega.hidden && !mega.contains(e.target) && !megaBtn.contains(e.target)) closeMega();
    });
    /* Close the mega menu when keyboard focus leaves it. */
    if (mega && megaBtn) {
      mega.addEventListener('focusout', function (e) {
        var to = e.relatedTarget;
        if (to && !mega.contains(to) && to !== megaBtn) closeMega();
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var megaWasOpen = mega && !mega.hidden;
      var mobileWasOpen = mobileMenu && !mobileMenu.hidden;
      closeSearch(); closeMega(); closeMobileMenu();
      if (megaWasOpen) megaBtn.focus();
      else if (mobileWasOpen) menuBtn.focus();
    });

    /* Favorites: product ids in localStorage (no personal account); heart buttons and the header counter. */
    var favBadge = $('[data-fav-count]');
    var favs = store.get('favorites', []).map(Number).filter(Boolean);
    function renderFavBadge() {
      if (!favBadge) return;
      favBadge.textContent = String(favs.length);
      favBadge.hidden = favs.length === 0;
    }
    $$('[data-fav]').forEach(function (btn) {
      var holder = btn.closest('[data-id]');
      var id = holder ? Number(holder.getAttribute('data-id')) : 0;
      if (!id) return;
      btn.setAttribute('aria-pressed', String(favs.indexOf(id) >= 0));
      btn.addEventListener('click', function () {
        var on = btn.getAttribute('aria-pressed') !== 'true';
        favs = favs.filter(function (x) { return x !== id; });
        if (on) favs.push(id);
        store.set('favorites', favs);
        btn.setAttribute('aria-pressed', String(on));
        renderFavBadge();
        if (!on && holder.closest('[data-fav-grid]')) { holder.remove(); renderFavoritesPage(); }
      });
    });
    renderFavBadge();

    /* Favorites page: the server renders the cards for ?ids=…, taken from localStorage. */
    var favPage = $('[data-fav-page]');
    function renderFavoritesPage() {
      if (!favPage) return;
      var grid = $('[data-fav-grid]', favPage);
      var n = grid ? $$('.card', grid).length : 0;
      var empty = $('[data-fav-empty]', favPage);
      if (empty) empty.hidden = n > 0;
      if (grid) grid.hidden = n === 0;
      var label = $('[data-fav-page-count]', favPage);
      if (label) label.textContent = n ? n + ' ' + (n % 10 === 1 && n % 100 !== 11 ? 'товар' : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)) ? 'товара' : 'товаров') : '';
    }
    if (favPage) {
      var wanted = favs.slice().sort(function (a, b) { return a - b; }).join(',');
      var params = new URLSearchParams(window.location.search);
      var shown = (params.get('ids') || '').split(',').map(Number).filter(Boolean).sort(function (a, b) { return a - b; }).join(',');
      if (wanted !== shown) {
        window.location.replace(window.location.pathname + (wanted ? '?ids=' + wanted : ''));
      } else {
        renderFavoritesPage();
      }
    }

    /* Catalog: filters panel on phones and sorting applied on change. */
    var filtersToggle = $('[data-filters-toggle]');
    var filtersPanel = $('[data-filters]');
    if (filtersToggle && filtersPanel) {
      filtersToggle.addEventListener('click', function () {
        var open = !filtersPanel.classList.contains('is-open');
        filtersPanel.classList.toggle('is-open', open);
        filtersToggle.setAttribute('aria-expanded', String(open));
        if (open) { var first = $('input, a, button', filtersPanel); if (first) first.focus(); }
      });
    }
    var sortSelect = $('[data-sort-select]');
    if (sortSelect) {
      sortSelect.addEventListener('change', function () { sortSelect.form.submit(); });
    }

    /* Info page: one section at a time, driven by the URL hash (as in the prototype). */
    var info = $('[data-info]');
    if (info) {
      var infoSections = $$('[data-info-section]', info);
      var showInfoSection = function () {
        var id = (window.location.hash || '#delivery').slice(1);
        var target = document.getElementById(id);
        if (!target || !target.hasAttribute('data-info-section')) id = 'delivery';
        infoSections.forEach(function (sec) { sec.hidden = sec.id !== id; });
        $$('[data-info-link]', info).forEach(function (a) { a.setAttribute('aria-current', String(a.getAttribute('href') === '#' + id)); });
      };
      window.addEventListener('hashchange', showInfoSection);
      showInfoSection();
      $$('[data-size-tab]', info).forEach(function (tab) {
        tab.addEventListener('click', function () {
          $$('[data-size-tab]', info).forEach(function (t) {
            var on = t === tab;
            t.setAttribute('aria-pressed', String(on));
            t.setAttribute('aria-selected', String(on));
            document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
          });
        });
      });
    }

    /* Footer groups: collapsible on phones; on desktop they stay open and their titles are not focusable. */
    var footerCols = $$('.footer__col');
    var mobileMq = window.matchMedia('(max-width: 767px)');
    function applyFooterCols() {
      footerCols.forEach(function (d) {
        var summary = $('summary', d);
        if (isMobile()) {
          d.removeAttribute('open');
          if (summary) { summary.removeAttribute('tabindex'); summary.removeAttribute('aria-disabled'); }
        } else {
          d.setAttribute('open', '');
          if (summary) { summary.setAttribute('tabindex', '-1'); summary.setAttribute('aria-disabled', 'true'); }
        }
      });
    }
    footerCols.forEach(function (d) {
      var summary = $('summary', d);
      if (summary) summary.addEventListener('click', function (e) { if (!isMobile()) e.preventDefault(); });
      d.addEventListener('toggle', function () { if (!isMobile() && !d.open) d.open = true; });
    });
    applyFooterCols();
    if (mobileMq.addEventListener) mobileMq.addEventListener('change', applyFooterCols);

    /* Cookie banner */
    var cookie = $('[data-cookie]');
    if (cookie) {
      if (!store.get('cookie-consent', null)) cookie.hidden = false;
      $$('[data-cookie-choice]', cookie).forEach(function (b) {
        b.addEventListener('click', function () {
          store.set('cookie-consent', b.getAttribute('data-cookie-choice'));
          cookie.hidden = true;
          /* Load analytics here only when the choice is "all". */
        });
      });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
