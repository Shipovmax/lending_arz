/*
 * Product page: color and size choice (trade offers), price, gallery, tabs, add to cart.
 * Data comes from data-product-json (see template.php). Adding uses the component's ADD2BASKET action.
 */
(function () {
  'use strict';

  function init() {
    var root = document.querySelector('[data-product-json]');
    if (!root) return;
    var $ = function (sel, ctx) { return (ctx || root).querySelector(sel); };
    var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || root).querySelectorAll(sel)); };
    var data = JSON.parse(root.getAttribute('data-product-json'));
    var offers = data.offers || [];
    var state = { color: data.color || '', size: '' };

    /* Gallery */
    var photos = [];
    var photoIndex = 0;
    var mainImg = $('[data-photo]');
    var thumbs = $('[data-thumbs]');
    var nav = $('[data-photo-nav]');

    function readGallery() {
      photos = $$('[data-thumb] img', thumbs).map(function (img, i) {
        return { thumb: img.getAttribute('src'), main: i === 0 && mainImg ? mainImg.getAttribute('src') : null };
      });
    }
    function showPhoto(i) {
      if (!photos.length || !mainImg) return;
      photoIndex = (i + photos.length) % photos.length;
      if (photos[photoIndex].main) mainImg.setAttribute('src', photos[photoIndex].main);
      $$('[data-thumb]', thumbs).forEach(function (t, n) {
        if (n === photoIndex) t.setAttribute('aria-current', 'true'); else t.removeAttribute('aria-current');
      });
    }
    function setGallery(list) {
      if (!list || !list.length || !mainImg) return;
      photos = list.map(function (p) { return { thumb: p.thumb, main: p.main }; });
      thumbs.innerHTML = '';
      photos.forEach(function (p, i) {
        var b = document.createElement('button');
        b.className = 'gallery__thumb photo';
        b.type = 'button';
        b.setAttribute('aria-label', 'Фото ' + (i + 1));
        b.setAttribute('data-thumb', String(i));
        var img = document.createElement('img');
        img.src = p.thumb;
        img.alt = '';
        b.appendChild(img);
        thumbs.appendChild(b);
      });
      nav.hidden = photos.length < 2;
      showPhoto(0);
    }
    // 1C sends small photos (about 300 px): enlarge them at most twice, a bigger stretch only blurs them.
    function limitPhoto() {
      if (!mainImg || !mainImg.naturalWidth) return;
      mainImg.style.maxWidth = (mainImg.naturalWidth * 2) + 'px';
      mainImg.style.maxHeight = (mainImg.naturalHeight * 2) + 'px';
    }
    if (mainImg) {
      mainImg.addEventListener('load', limitPhoto);
      if (mainImg.complete) limitPhoto();
    }
    readGallery();
    if (data.gallery && data.gallery.length) {
      photos = data.gallery.map(function (p) { return { thumb: p.thumb, main: p.main }; });
    }
    // Main-size URLs for the server-rendered thumbnails come from the default color's data.
    if (data.colors && data.colors[state.color] && data.colors[state.color].photos.length) {
      photos = data.colors[state.color].photos.map(function (p) { return { thumb: p.thumb, main: p.main }; });
    }
    if (thumbs) {
      thumbs.addEventListener('click', function (e) {
        var t = e.target.closest('[data-thumb]');
        if (t) showPhoto(Number(t.getAttribute('data-thumb')));
      });
    }
    var prev = $('[data-photo-prev]');
    var next = $('[data-photo-next]');
    if (prev) prev.addEventListener('click', function () { showPhoto(photoIndex - 1); });
    if (next) next.addEventListener('click', function () { showPhoto(photoIndex + 1); });

    /* Offers */
    var priceEl = $('[data-price]');
    var oldWrap = $('[data-price-old]');
    var oldValue = $('[data-price-old-value]');
    var sizeError = $('[data-size-error]');
    var hasSizes = $$('[data-size]').length > 0;

    function offerFor(color, size) {
      for (var i = 0; i < offers.length; i++) {
        if (offers[i].color === color && offers[i].size === size) return offers[i];
      }
      return null;
    }
    function setPrice(offer, from) {
      if (!offer || !priceEl) return;
      priceEl.textContent = (from ? 'от ' : '') + offer.price.replace(/&nbsp;/g, ' ');
      syncBar();
      oldWrap.hidden = !offer.old;
      if (offer.old) oldValue.textContent = offer.old;
    }
    function cheapest(color) {
      var best = null;
      var values = {};
      offers.forEach(function (o) {
        if (color && o.color !== color) return;
        values[o.value] = true;
        if (!best || o.value < best.value) best = o;
      });
      return { offer: best, from: Object.keys(values).length > 1 };
    }
    function refreshSizes() {
      $$('[data-size]').forEach(function (b) {
        var size = b.getAttribute('data-size');
        var offer = offerFor(state.color, size);
        var ok = !!(offer && offer.canBuy);
        b.disabled = !ok;
        if (ok) b.removeAttribute('aria-label'); else b.setAttribute('aria-label', size + ', нет в наличии');
        if (!ok && state.size === size) state.size = '';
        b.setAttribute('aria-pressed', String(state.size === size));
      });
    }
    var stockEl = $('[data-stock]');
    function refreshStock() {
      if (!stockEl || !offers.length) return;
      var exact = state.size ? offerFor(state.color, state.size) : null;
      var ok = exact ? exact.canBuy : offers.some(function (o) { return (!state.color || o.color === state.color) && o.canBuy; });
      stockEl.textContent = ok ? 'В наличии' : 'Нет в наличии';
      stockEl.classList.toggle('stock--out', !ok);
    }
    function refreshPrice() {
      var exact = offerFor(state.color, state.size);
      if (exact) { setPrice(exact, false); return; }
      var c = cheapest(state.color);
      setPrice(c.offer, c.from);
    }

    $$('[data-color]').forEach(function (b) {
      b.addEventListener('click', function () {
        state.color = b.getAttribute('data-color');
        $$('[data-color]').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        var nameEl = $('[data-color-name]');
        if (nameEl && data.colors[state.color]) nameEl.textContent = data.colors[state.color].name;
        if (data.colors[state.color]) setGallery(data.colors[state.color].photos);
        refreshSizes();
        refreshPrice();
        refreshStock();
      });
    });
    $$('[data-size]').forEach(function (b) {
      b.addEventListener('click', function () {
        state.size = b.getAttribute('data-size');
        if (sizeError) sizeError.hidden = true;
        refreshSizes();
        refreshPrice();
        refreshStock();
      });
    });

    /* Bottom bar on phones: price and buy button while the main button is off screen */
    var bar = $('[data-buybar]');
    var barBtn = $('[data-buybar-btn]');
    var mainBtn = $('[data-add-to-cart]');
    if (bar && barBtn && mainBtn && 'IntersectionObserver' in window) {
      barBtn.addEventListener('click', function () { mainBtn.click(); });
      new IntersectionObserver(function (entries) {
        var e = entries[0];
        // Shown only while the main button is below the screen (not after the user scrolled past it).
        bar.hidden = e.isIntersecting || e.boundingClientRect.top < 0;
      }).observe(mainBtn);
    }
    function syncBar() {
      var barPrice = $('[data-buybar-price]');
      if (barPrice && priceEl) barPrice.textContent = priceEl.textContent;
    }

    /* Add to cart */
    var addBtn = $('[data-add-to-cart]');
    var added = $('[data-added]');
    var addError = $('[data-add-error]');
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        var id = data.productId;
        if (offers.length) {
          var offer = hasSizes ? offerFor(state.color, state.size) : (offers.filter(function (o) { return o.color === state.color && o.canBuy; })[0] || null);
          if (!offer) {
            if (sizeError) { sizeError.hidden = false; }
            var firstSize = $('[data-size]:not(:disabled)');
            if (firstSize) firstSize.focus();
            return;
          }
          id = offer.id;
        }
        addBtn.disabled = true;
        addError.hidden = true;
        var url = data.actionUrl + (data.actionUrl.indexOf('?') < 0 ? '?' : '&') + 'action=ADD2BASKET&ajax_basket=Y&id=' + encodeURIComponent(id);
        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (!res || res.STATUS !== 'OK') throw new Error('add failed');
            var sizeLabel = $('[data-added-size]');
            if (sizeLabel) sizeLabel.textContent = state.size ? ': размер ' + state.size : '';
            added.hidden = false;
            if (window.BX && BX.onCustomEvent) BX.onCustomEvent('OnBasketChange');
          })
          .catch(function () { addError.hidden = false; })
          .then(function () { addBtn.disabled = false; });
      });
    }

    /* Tabs (WAI-ARIA tabs pattern) */
    var tabs = $$('.tabs__tab', document);
    function selectTab(tab) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.setAttribute('tabindex', on ? '0' : '-1');
        var panel = document.getElementById(t.getAttribute('aria-controls'));
        if (panel) panel.hidden = !on;
      });
    }
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { selectTab(tab); });
      tab.addEventListener('keydown', function (e) {
        var n = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : null;
        if (n === null) return;
        e.preventDefault();
        var t = tabs[(n + tabs.length) % tabs.length];
        t.focus();
        selectTab(t);
      });
    });

    refreshSizes();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
