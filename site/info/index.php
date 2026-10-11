<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Покупателям');
$APPLICATION->SetPageProperty('title', 'Покупателям — доставка, оплата, возврат — Мир спорта');
$APPLICATION->SetPageProperty('description', 'Как оформить заказ, доставка СДЭК и самовывоз в Арзамасе, оплата, возврат и обмен, таблица размеров, контакты и реквизиты.');
// The page prints its own h1 (from the prototype).
$APPLICATION->SetPageProperty('hide_h1', 'Y');
require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/contacts.php';
$contacts = \MirSport\contacts();
?>
<div class="container page page--tight">
  <nav aria-label="Хлебные крошки">
    <ol class="breadcrumbs"><li><a class="link-u" href="/">Главная</a></li><li aria-current="page">Покупателям</li></ol>
  </nav>

  <h1 class="visually-hidden">Покупателям</h1>
  <div class="doc-layout" data-info>
    <nav class="side-nav" aria-label="Разделы для покупателей">
      <a href="#order" data-info-link>Как оформить заказ <span aria-hidden="true">→</span></a>
      <a href="#delivery" data-info-link>Доставка <span aria-hidden="true">→</span></a>
      <a href="#payment" data-info-link>Оплата <span aria-hidden="true">→</span></a>
      <a href="#return" data-info-link>Возврат и обмен <span aria-hidden="true">→</span></a>
      <a href="#sizes" data-info-link>Таблица размеров <span aria-hidden="true">→</span></a>
      <a href="#contacts" data-info-link>Контакты <span aria-hidden="true">→</span></a>
      <?php if ($contacts['legal'] !== ''): ?><a href="#requisites" data-info-link>Реквизиты <span aria-hidden="true">→</span></a><?php endif ?>
    </nav>

    <article class="doc">
      <section id="order" data-info-section>
        <h2 class="page-title">Как оформить заказ</h2>
        <ol class="steps">
          <li><b>01</b><span><strong>Выберите товар.</strong> Найдите его в каталоге или через поиск, выберите цвет и размер, нажмите «Добавить в корзину».</span></li>
          <li><b>02</b><span><strong>Перейдите в корзину.</strong> Проверьте товары и количество. Регистрация не нужна.</span></li>
          <li><b>03</b><span><strong>Укажите контакты и доставку.</strong> Имя, телефон и способ получения: самовывоз в Арзамасе или СДЭК.</span></li>
          <li><b>04</b><span><strong>Оплатите.</strong> Онлайн на сайте или при получении. Мы позвоним, чтобы подтвердить заказ.</span></li>
        </ol>
      </section>

      <section id="delivery" data-info-section>
        <h2 class="page-title">Доставка</h2>
        <dl class="def-list">
          <div><dt><strong style="color: var(--c-ink)">Самовывоз из магазина</strong><br>Арзамас<?= $contacts['address'] !== '' ? ', ' . htmlspecialcharsbx($contacts['address']) : '' ?>. Если товар в наличии, заказ можно забрать в день оформления.</dt><dd>Бесплатно</dd></div>
          <div><dt><strong style="color: var(--c-ink)">Пункт выдачи СДЭК</strong><br>В любой город России. Стоимость и срок рассчитываются автоматически при оформлении.</dt><dd>По тарифу СДЭК</dd></div>
          <div><dt><strong style="color: var(--c-ink)">Курьер СДЭК</strong><br>Доставка до двери. Курьер позвонит заранее и согласует время.</dt><dd>По тарифу СДЭК</dd></div>
        </dl>
        <p class="muted small">После отправки пришлём трек-номер СДЭК в SMS или на почту.</p>
      </section>

      <section id="payment" data-info-section>
        <h2 class="page-title">Оплата</h2>
        <h3>Онлайн на сайте</h3>
        <p>Банковской картой МИР, Visa, Mastercard или через СБП. Оплата проходит на защищённой странице платёжного сервиса.</p>
        <h3>При получении в СДЭК</h3>
        <p>Картой или наличными в пункте выдачи или курьеру. СДЭК может взять комиссию за наложенный платёж.</p>
        <h3>В магазине</h3>
        <p>При самовывозе — картой, через СБП или наличными.</p>
        <p class="muted small">Кассовый чек отправим на телефон или электронную почту, указанные при заказе.</p>
      </section>

      <section id="return" data-info-section>
        <h2 class="page-title">Возврат и обмен</h2>
        <h3>Если товар не подошёл</h3>
        <p>Заказ, оформленный на сайте, можно вернуть в течение 7 дней после получения, если сохранены товарный вид, ярлыки и документ об оплате. Деньги вернём в течение 10 дней после получения возврата, за вычетом стоимости обратной доставки.</p>
        <h3>Обмен в магазине</h3>
        <p>Покупку в магазине можно обменять на другой размер или цвет в течение 14 дней, если товар не был в употреблении.</p>
        <h3>Если нашли брак</h3>
        <p>Напишите или позвоните нам с номером заказа и фото. Заменим товар или вернём деньги по закону о защите прав потребителей.</p>
        <h3>Как оформить возврат</h3>
        <p>Заполните заявление в магазине<?= $contacts['email_href'] !== '' ? ' или пришлите его на <a class="link-line" href="' . htmlspecialcharsbx($contacts['email_href']) . '">' . htmlspecialcharsbx($contacts['email']) . '</a>' : '' ?>. Отправить товар можно через СДЭК, адрес для отправки подскажем.</p>
      </section>

      <section id="sizes" data-info-section>
        <h2 class="page-title">Таблица размеров</h2>
        <div class="toolbar__group" role="tablist" aria-label="Таблицы размеров">
          <button class="pill" type="button" role="tab" aria-pressed="true" aria-selected="true" aria-controls="size-men" data-size-tab>Мужская одежда</button>
          <button class="pill" type="button" role="tab" aria-pressed="false" aria-selected="false" aria-controls="size-women" data-size-tab>Женская одежда</button>
          <button class="pill" type="button" role="tab" aria-pressed="false" aria-selected="false" aria-controls="size-shoes" data-size-tab>Обувь</button>
        </div>
        <div class="table-wrap" id="size-men" role="tabpanel"><table class="table">
          <thead><tr><th scope="col">Размер RU</th><th scope="col">Международный</th><th scope="col">Обхват груди, см</th><th scope="col">Обхват талии, см</th></tr></thead>
          <tbody><tr><td>44</td><td>XS</td><td>86–89</td><td>74–77</td></tr><tr><td>46</td><td>S</td><td>90–93</td><td>78–81</td></tr><tr><td>48</td><td>M</td><td>94–97</td><td>82–85</td></tr><tr><td>50</td><td>L</td><td>98–101</td><td>86–89</td></tr><tr><td>52</td><td>XL</td><td>102–105</td><td>90–93</td></tr><tr><td>54</td><td>XXL</td><td>106–109</td><td>94–97</td></tr></tbody>
        </table></div>
        <div class="table-wrap" id="size-women" role="tabpanel" hidden><table class="table">
          <thead><tr><th scope="col">Размер RU</th><th scope="col">Международный</th><th scope="col">Обхват груди, см</th><th scope="col">Обхват бёдер, см</th></tr></thead>
          <tbody><tr><td>40</td><td>XXS</td><td>76–79</td><td>84–87</td></tr><tr><td>42</td><td>XS</td><td>80–83</td><td>88–91</td></tr><tr><td>44</td><td>S</td><td>84–87</td><td>92–95</td></tr><tr><td>46</td><td>M</td><td>88–91</td><td>96–99</td></tr><tr><td>48</td><td>L</td><td>92–95</td><td>100–103</td></tr><tr><td>50</td><td>XL</td><td>96–99</td><td>104–107</td></tr></tbody>
        </table></div>
        <div class="table-wrap" id="size-shoes" role="tabpanel" hidden><table class="table">
          <thead><tr><th scope="col">Размер RU</th><th scope="col">EU</th><th scope="col">Длина стопы, см</th></tr></thead>
          <tbody><tr><td>39</td><td>40</td><td>25,0</td></tr><tr><td>40</td><td>41</td><td>25,5–26,0</td></tr><tr><td>41</td><td>42</td><td>26,5</td></tr><tr><td>42</td><td>43</td><td>27,0–27,5</td></tr><tr><td>43</td><td>44</td><td>28,0</td></tr><tr><td>44</td><td>45</td><td>28,5–29,0</td></tr></tbody>
        </table></div>
        <p class="muted small">Таблица ориентировочная: у разных брендов размеры могут отличаться. Если сомневаетесь, позвоните — подскажем.</p>
      </section>

      <section id="contacts" data-info-section>
        <h2 class="page-title">Контакты</h2>
        <dl class="def-list">
          <div><dt>Адрес магазина</dt><dd>Арзамас<?= $contacts['address'] !== '' ? ', ' . htmlspecialcharsbx($contacts['address']) : '' ?></dd></div>
          <?php if ($contacts['hours'] !== ''): ?><div><dt>Часы работы</dt><dd><?= htmlspecialcharsbx($contacts['hours']) ?></dd></div><?php endif ?>
          <?php if ($contacts['phone'] !== ''): ?><div><dt>Телефон</dt><dd><?= \MirSport\link_or_text($contacts['phone'], $contacts['phone_href'], '') ?></dd></div><?php endif ?>
          <?php if ($contacts['email'] !== ''): ?><div><dt>Электронная почта</dt><dd><?= \MirSport\link_or_text($contacts['email'], $contacts['email_href'], '') ?></dd></div><?php endif ?>
        </dl>
        <a class="btn" href="https://yandex.ru/maps/?text=<?= rawurlencode('Арзамас, ' . ($contacts['address'] !== '' ? $contacts['address'] : 'Мир спорта')) ?>" target="_blank" rel="noopener">Открыть на карте</a>
      </section>

      <?php if ($contacts['legal'] !== ''): ?>
      <section id="requisites" data-info-section>
        <h2 class="page-title">Реквизиты</h2>
        <p><?= nl2br(htmlspecialcharsbx($contacts['legal'])) ?></p>
      </section>
      <?php endif ?>

      <div class="help">
        <span class="help__text">Остались вопросы? Ответим по телефону, почте или в магазине.</span>
        <?php if ($contacts['phone_href'] !== ''): ?><a class="btn btn--primary" href="<?= htmlspecialcharsbx($contacts['phone_href']) ?>">Позвонить</a><?php endif ?>
        <?php if ($contacts['email_href'] !== ''): ?><a class="btn" href="<?= htmlspecialcharsbx($contacts['email_href']) ?>">Написать</a><?php endif ?>
      </div>
    </article>
  </div>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
