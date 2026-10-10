/*
 * Мир спорта — prototype behaviour. Vanilla JS, no dependencies.
 * In Bitrix most of this is replaced by components (search.title, smart.filter,
 * catalog.element, sale.order.ajax). Keep the data-* hooks when porting the markup.
 */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var rub = function (n) { return n.toLocaleString('ru-RU').replace(/\s/g, ' ') + ' ₽'; };
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

  var escapeHtml = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  };
  var highlight = function (text, q) {
    var i = text.toLowerCase().indexOf(q);
    if (i < 0) return escapeHtml(text);
    return escapeHtml(text.slice(0, i)) + '<mark>' + escapeHtml(text.slice(i, i + q.length)) + '</mark>' + escapeHtml(text.slice(i + q.length));
  };
  var setIcon = function (btn, id) { var use = $('use', btn); if (use) use.setAttribute('href', '#' + id); };

  /* ------------------------------------------------------------------ */
  /* Header: mega menu, mobile menu, search                              */
  /* ------------------------------------------------------------------ */
  var megaBtn = $('[data-megamenu-toggle]');
  var mega = $('[data-megamenu]');
  var menuBtn = $('[data-menu-toggle]');
  var mobileMenu = $('[data-mobile-menu]');
  var search = $('[data-search]');

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

  if (megaBtn && mega) {
    megaBtn.addEventListener('click', function () {
      var open = mega.hidden;
      closeSearch();
      mega.hidden = !open;
      megaBtn.setAttribute('aria-expanded', String(open));
      setIcon(megaBtn, open ? 'i-close' : 'i-menu');
    });
    $$('[data-mm-dir]', mega).forEach(function (tab) {
      var show = function () {
        $$('[data-mm-dir]', mega).forEach(function (t) {
          var on = t === tab;
          t.setAttribute('aria-selected', String(on));
          document.getElementById(t.getAttribute('data-mm-dir')).hidden = !on;
        });
      };
      tab.addEventListener('click', show);
      tab.addEventListener('mouseenter', show);
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
    });
  }

  /* Search suggestions. Demo data; in Bitrix use bitrix:search.title (AJAX). */
  var SUGGEST = {
    popular: ['Кроссовки', 'Термобельё', 'Футбольный мяч', 'Спиннинг', 'Воблеры', 'Куртка'],
    categories: [
      ['Кроссовки', 'Обувь'], ['Кеды', 'Обувь'], ['Куртки мужские', 'Мужчинам'], ['Куртки женские', 'Женщинам'],
      ['Костюмы спортивные', 'Одежда'], ['Мячи', 'Инвентарь'], ['Спиннинги', 'Рыбалка · Удилища'],
      ['Катушки', 'Рыбалка'], ['Воблеры', 'Рыбалка · Приманки'], ['Термобельё', 'Одежда']
    ],
    products: [
      ['Кроссовки беговые мужские', 4290], ['Кроссовки для зала женские', 3790], ['Кеды мужские', 2590],
      ['Футбольный мяч, размер 5', 1490], ['Спиннинг 2,4 м, тест 5–20 г', 3190], ['Катушка безынерционная 2500', 2450],
      ['Набор воблеров, 5 шт.', 1190], ['Куртка спортивная женская', 3890]
    ]
  };
  var input = search && $('[data-search-input]', search);
  var suggest = search && $('[data-suggest]', search);
  var clearBtn = search && $('[data-search-clear]', search);

  function closeSearch() {
    if (!search || suggest.hidden) return;
    suggest.hidden = true;
    search.classList.remove('is-open');
    input.setAttribute('aria-expanded', 'false');
  }
  function renderSuggest() {
    var q = input.value.trim().toLowerCase();
    clearBtn.hidden = !q;
    var html = '';
    if (!q) {
      html += '<div><div class="suggest__title">Часто ищут</div><div class="suggest__chips">' +
        SUGGEST.popular.map(function (t) { return '<button class="pill" type="button" data-suggest-term="' + escapeHtml(t) + '">' + escapeHtml(t) + '</button>'; }).join('') +
        '</div></div>';
    } else {
      var cats = SUGGEST.categories.filter(function (c) { return c[0].toLowerCase().indexOf(q) >= 0; }).slice(0, 4);
      var prods = SUGGEST.products.filter(function (p) { return p[0].toLowerCase().indexOf(q) >= 0; }).slice(0, 4);
      if (cats.length) {
        html += '<div><div class="suggest__title">Категории</div>' + cats.map(function (c) {
          return '<a class="suggest__row" href="catalog.html"><span>' + highlight(c[0], q) + '</span><span class="suggest__path">' + escapeHtml(c[1]) + '</span></a>';
        }).join('') + '</div>';
      }
      if (prods.length) {
        html += '<div><div class="suggest__title">Товары</div>' + prods.map(function (p) {
          return '<a class="suggest__row" href="product.html"><span class="suggest__thumb" aria-hidden="true"></span><span>' + highlight(p[0], q) + '</span><span class="suggest__price">' + rub(p[1]) + '</span></a>';
        }).join('') + '</div>';
      }
      if (!cats.length && !prods.length) {
        html += '<p class="muted">По запросу «' + escapeHtml(input.value.trim()) + '» ничего не нашлось. Проверьте написание или поищите по артикулу.</p>';
      }
      html += '<div class="suggest__footer"><a class="caps link-line" href="catalog.html?q=' + encodeURIComponent(input.value.trim()) + '">Все результаты по «' + escapeHtml(input.value.trim()) + '»</a></div>';
    }
    suggest.innerHTML = html;
    suggest.hidden = false;
    search.classList.add('is-open');
    input.setAttribute('aria-expanded', 'true');
  }
  if (input) {
    input.addEventListener('focus', function () { closeMega(); closeMobileMenu(); renderSuggest(); });
    input.addEventListener('input', renderSuggest);
    clearBtn.addEventListener('click', function () { input.value = ''; renderSuggest(); input.focus(); });
    $('[data-search-close]', search).addEventListener('click', function () { input.value = ''; clearBtn.hidden = true; closeSearch(); });
    suggest.addEventListener('click', function (e) {
      var term = e.target.closest('[data-suggest-term]');
      if (term) { input.value = term.getAttribute('data-suggest-term'); renderSuggest(); input.focus(); }
    });
  }

  document.addEventListener('click', function (e) {
    if (search && !search.contains(e.target)) closeSearch();
    if (mega && !mega.hidden && !mega.contains(e.target) && !megaBtn.contains(e.target)) closeMega();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    closeSearch(); closeMega(); closeMobileMenu();
  });

  /* ------------------------------------------------------------------ */
  /* Favorites and cart counters                                          */
  /* ------------------------------------------------------------------ */
  var favBadge = $('[data-fav-count]');
  var cartBadge = $('[data-cart-count]');
  var favs = store.get('favorites', []);
  var cartCount = store.get('cart-count', 2);

  function renderCounters() {
    if (favBadge) { favBadge.textContent = String(favs.length); favBadge.hidden = favs.length === 0; }
    if (cartBadge) { cartBadge.textContent = String(cartCount); cartBadge.hidden = cartCount === 0; }
  }
  function setCart(n) { cartCount = Math.max(0, n); store.set('cart-count', cartCount); renderCounters(); }
  function toggleFav(id, on) {
    favs = favs.filter(function (x) { return x !== id; });
    if (on) favs.push(id);
    store.set('favorites', favs);
    renderCounters();
  }

  $$('[data-fav]').forEach(function (btn) {
    var holder = btn.closest('[data-id]');
    var id = holder ? Number(holder.getAttribute('data-id')) : 0;
    if (id && favs.indexOf(id) >= 0) btn.setAttribute('aria-pressed', 'true');
    btn.addEventListener('click', function () {
      var on = btn.getAttribute('aria-pressed') !== 'true';
      btn.setAttribute('aria-pressed', String(on));
      if (id) toggleFav(id, on);
      if (!on && holder && holder.closest('[data-fav-grid]')) { holder.remove(); renderFavoritesPage(); }
    });
  });

  $$('[data-buy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var on = !btn.classList.contains('is-active');
      btn.classList.toggle('is-active', on);
      btn.textContent = on ? 'В корзине' : 'В корзину';
      setCart(cartCount + (on ? 1 : -1));
    });
  });

  /* Favorites page */
  var favGrid = $('[data-fav-grid]');
  function renderFavoritesPage() {
    if (!favGrid) return;
    var n = $$('.card', favGrid).length;
    $('[data-fav-empty]').hidden = n > 0;
    favGrid.hidden = n === 0;
    var label = $('[data-fav-page-count]');
    if (label) label.textContent = n ? n + ' ' + (n === 1 ? 'товар' : n < 5 ? 'товара' : 'товаров') : '';
  }
  renderFavoritesPage();
  renderCounters();

  /* ------------------------------------------------------------------ */
  /* Catalog filters (demo: filters the cards on the page)                */
  /* ------------------------------------------------------------------ */
  var grid = $('[data-grid]');
  if (grid) {
    var params = new URLSearchParams(window.location.search);
    var state = { dir: params.get('dir') === 'fish' ? 'fish' : 'sport', stock: false, sale: params.get('sale') === '1', size: null };
    var titles = { sport: 'Спорттовары', fish: 'Рыбалка', all: 'Все товары' };
    var filtersPanel = $('[data-filters]');
    var filtersToggle = $('[data-filters-toggle]');

    var applyFilters = function () {
      var shown = 0;
      $$('.card', grid).forEach(function (card) {
        var ok = (state.dir === 'all' || card.getAttribute('data-dir') === state.dir) &&
          (!state.stock || card.getAttribute('data-stock') === '1') &&
          (!state.sale || card.getAttribute('data-sale') === '1') &&
          (!state.size || card.getAttribute('data-sizes').split(',').indexOf(state.size) >= 0);
        card.hidden = !ok;
        if (ok) shown++;
      });
      $('[data-count]').textContent = String(shown);
      $('[data-empty]').hidden = shown > 0;
      $('[data-dir-title]').textContent = titles[state.dir];
      $('[data-dir-crumb]').textContent = titles[state.dir];
      $$('[data-dir]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-dir') === state.dir)); });
      $$('[data-quick]').forEach(function (b) { b.setAttribute('aria-pressed', String(!!state[b.getAttribute('data-quick')])); });
      $$('[data-size]', $('[data-size-filter]')).forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-size') === state.size)); });

      var chips = [];
      if (state.stock) chips.push(['stock', 'В наличии в Арзамасе']);
      if (state.sale) chips.push(['sale', 'Со скидкой']);
      if (state.size) chips.push(['size', 'Размер ' + state.size]);
      var chipsBox = $('[data-chips]');
      chipsBox.innerHTML = chips.map(function (c) { return '<button class="chip" type="button" data-chip="' + c[0] + '" aria-label="Убрать фильтр: ' + c[1] + '">' + c[1] + ' <span aria-hidden="true">×</span></button>'; }).join('');
      chipsBox.hidden = chips.length === 0;
      var badge = $('[data-filter-badge]');
      if (badge) badge.textContent = chips.length ? ' · ' + chips.length : '';
    };

    $$('[data-dir]').forEach(function (b) { b.addEventListener('click', function () { state.dir = b.getAttribute('data-dir'); state.size = null; applyFilters(); }); });
    $$('[data-quick]').forEach(function (b) { b.addEventListener('click', function () { var k = b.getAttribute('data-quick'); state[k] = !state[k]; applyFilters(); }); });
    $$('[data-size]', $('[data-size-filter]')).forEach(function (b) { b.addEventListener('click', function () { var s = b.getAttribute('data-size'); state.size = state.size === s ? null : s; applyFilters(); }); });
    $$('[data-filters-reset]').forEach(function (b) { b.addEventListener('click', function () { state.stock = false; state.sale = false; state.size = null; applyFilters(); }); });
    $('[data-chips]').addEventListener('click', function (e) {
      var c = e.target.closest('[data-chip]');
      if (!c) return;
      var k = c.getAttribute('data-chip');
      state[k] = k === 'size' ? null : false;
      applyFilters();
    });
    if (filtersToggle) {
      filtersToggle.addEventListener('click', function () {
        var open = !filtersPanel.classList.contains('is-open');
        filtersPanel.classList.toggle('is-open', open);
        filtersToggle.setAttribute('aria-expanded', String(open));
      });
    }
    applyFilters();
  }

  /* ------------------------------------------------------------------ */
  /* Product page                                                         */
  /* ------------------------------------------------------------------ */
  var pdp = $('[data-product]');
  if (pdp) {
    var photo = 1;
    var thumbs = $$('[data-thumb]', pdp);
    var showPhoto = function (n) {
      photo = ((n - 1 + thumbs.length) % thumbs.length) + 1;
      $('[data-photo-index]', pdp).textContent = String(photo);
      thumbs.forEach(function (t) { t.setAttribute('aria-current', String(Number(t.getAttribute('data-thumb')) === photo)); });
    };
    thumbs.forEach(function (t) { t.addEventListener('click', function () { showPhoto(Number(t.getAttribute('data-thumb'))); }); });
    $('[data-photo-prev]', pdp).addEventListener('click', function () { showPhoto(photo - 1); });
    $('[data-photo-next]', pdp).addEventListener('click', function () { showPhoto(photo + 1); });

    $$('[data-color]', pdp).forEach(function (sw) {
      sw.addEventListener('click', function () {
        $$('[data-color]', pdp).forEach(function (x) { x.setAttribute('aria-pressed', String(x === sw)); });
        $('[data-color-name]', pdp).textContent = sw.getAttribute('data-color');
      });
    });

    var chosen = null;
    var addBtn = $('[data-add-to-cart]', pdp);
    var added = $('[data-added]', pdp);
    var sizeErr = $('[data-size-error]', pdp);
    $$('.size', pdp).forEach(function (b) {
      b.addEventListener('click', function () {
        chosen = b.getAttribute('data-size');
        var left = Number(b.getAttribute('data-left'));
        $$('.size', pdp).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        $('[data-stock-line]', pdp).textContent = 'Размер ' + chosen + ': ' + (left > 2 ? 'в наличии' : 'осталось ' + left + ' шт.') + ' в магазине';
        sizeErr.hidden = true;
        added.hidden = true;
        addBtn.textContent = 'Добавить в корзину';
        addBtn.disabled = false;
      });
    });
    addBtn.addEventListener('click', function () {
      if (!chosen) { sizeErr.hidden = false; return; }
      $('[data-added-size]', pdp).textContent = chosen;
      added.hidden = false;
      addBtn.textContent = 'В корзине';
      addBtn.disabled = true;
      setCart(cartCount + 1);
    });
  }

  /* Tabs (WAI-ARIA tabs pattern) */
  $$('[data-tabs]').forEach(function (box) {
    var tabs = $$('[role="tab"]', box);
    var select = function (tab) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
    };
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(t); });
      t.addEventListener('keydown', function (e) {
        var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
        if (!d) return;
        var next = tabs[(i + d + tabs.length) % tabs.length];
        select(next); next.focus();
      });
    });
  });

  /* ------------------------------------------------------------------ */
  /* Cart and checkout                                                    */
  /* ------------------------------------------------------------------ */
  var checkout = $('[data-checkout]');
  if (checkout) {
    var list = $('[data-cart-list]', checkout);
    var recalc = function () {
      var items = $$('[data-item]', list);
      var sum = 0, count = 0;
      items.forEach(function (it) {
        var q = Number($('[data-qty-value]', it).textContent);
        var line = q * Number(it.getAttribute('data-price'));
        $('[data-item-total]', it).textContent = rub(line);
        sum += line; count += q;
      });
      $('[data-sum-count]', checkout).textContent = String(count);
      $('[data-sum-total]', checkout).textContent = rub(sum);
      $('[data-grand-total]', checkout).textContent = rub(sum);
      $('[data-cart-empty]', checkout).hidden = items.length > 0;
      setCart(count);
    };
    list.addEventListener('click', function (e) {
      var it = e.target.closest('[data-item]');
      if (!it) return;
      var q = e.target.closest('[data-qty]');
      if (q) {
        var out = $('[data-qty-value]', it);
        out.textContent = String(Math.min(10, Math.max(1, Number(out.textContent) + Number(q.getAttribute('data-qty')))));
      }
      if (e.target.closest('[data-remove]')) it.remove();
      recalc();
    });

    $$('[data-choices]', checkout).forEach(function (group) {
      group.addEventListener('click', function (e) {
        var c = e.target.closest('.choice');
        if (!c) return;
        $$('.choice', group).forEach(function (x) { x.setAttribute('aria-checked', String(x === c)); });
        if (group.getAttribute('data-choices') === 'delivery') {
          $('[data-delivery-cost]', checkout).textContent = c.getAttribute('data-cost');
          var label = c.getAttribute('data-label');
          var field = $('[data-address-field]', checkout);
          field.hidden = !label;
          $('[data-address-label]', checkout).textContent = label;
          $('[data-address-input]', checkout).placeholder = c.getAttribute('data-address');
        }
      });
    });

    var form = $('[data-checkout-form]', checkout);
    var consent = $('[data-consent]', checkout);
    var check = function (name) {
      var wrap = $('[data-field="' + name + '"]', form);
      var bad = !$('input', wrap).value.trim();
      wrap.classList.toggle('is-invalid', bad);
      $('.err', wrap).hidden = !bad;
      return !bad;
    };
    ['name', 'phone'].forEach(function (n) { $('[data-field="' + n + '"] input', form).addEventListener('input', function () { check(n); }); });
    consent.addEventListener('change', function () { $('[data-consent-error]', checkout).hidden = consent.checked; });
    $('[data-submit]', checkout).addEventListener('click', function () {
      var ok = check('name') & check('phone');
      $('[data-consent-error]', checkout).hidden = consent.checked;
      if (!ok || !consent.checked || !$$('[data-item]', list).length) {
        var firstBad = $('.is-invalid input', form);
        if (firstBad) firstBad.focus();
        return;
      }
      $('[data-success-name]', checkout).textContent = $('[name="name"]', form).value.trim();
      $('[data-success-phone]', checkout).textContent = $('[name="phone"]', form).value.trim();
      $('[data-checkout-form-wrap]', checkout).hidden = true;
      $('[data-success]', checkout).hidden = false;
      window.scrollTo(0, 0);
    });
    recalc();
  }

  /* ------------------------------------------------------------------ */
  /* Info page: one section at a time, driven by the URL hash             */
  /* ------------------------------------------------------------------ */
  var info = $('[data-info]');
  if (info) {
    var sections = $$('[data-info-section]', info);
    var showSection = function () {
      var id = (window.location.hash || '#delivery').slice(1);
      if (!document.getElementById(id) || !document.getElementById(id).hasAttribute('data-info-section')) id = 'delivery';
      sections.forEach(function (s) { s.hidden = s.id !== id; });
      $$('[data-info-link]', info).forEach(function (a) { a.setAttribute('aria-current', String(a.getAttribute('href') === '#' + id)); });
    };
    window.addEventListener('hashchange', showSection);
    showSection();

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

  /* Footer groups collapse on phones */
  if (isMobile()) $$('.footer__col').forEach(function (d) { d.removeAttribute('open'); });

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
})();
