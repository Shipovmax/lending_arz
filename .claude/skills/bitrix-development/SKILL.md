---
name: bitrix-development
description: Rules for developing on 1C-Bitrix (1С-Битрикс) without touching the core. Use whenever the task involves Bitrix, /local/, a site template (шаблон сайта), header.php/footer.php, copying or customizing component templates (шаблоны компонентов), bitrix:catalog.* components, product/SKU (торговые предложения, размер, цвет), or porting an HTML/CSS prototype into Bitrix, even if the user does not say "Bitrix" explicitly.
---

# Bitrix development rules

Facts below were checked against the official docs (dev.1c-bitrix.ru). Items marked **UNVERIFIED** were not confirmed: check the docs or the code of the installed version before relying on them. Do not fill gaps from memory.

## 1. Never edit the core
- Do not edit anything under `/bitrix/`. System component templates there are restored on update ([Шаблоны компонента](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2829)).
- All project code lives in `/local/`. `/local/` has priority over `/bitrix/`: a site template with the same name in both is taken from `/local/` ([Папка /local](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2705)).
- Do not duplicate same-named entities in both folders; move them.
- In templates and components use `/local/...` paths, not direct `/bitrix/templates/` or `/bitrix/components/` links.

## 2. `/local/` layout
Subfolders named in the docs: `components`, `templates`, `modules`, `php_interface`, `activities`, `gadgets`, `blocks`, `routes`, `js`. From main-module 24.100.0 also `.settings.php`, `.settings_extra.php`.

- `templates/` - site templates, component templates, page templates.
- `components/<namespace>/<name>/` - own components. Use your own namespace, not `bitrix`. A component has `component.php` (logic) and optional `templates/`.
- `php_interface/` - per docs holds `init.php` and `dbconn.php` (since 24.100.0) and `user_lang`. Access rights must match `/bitrix/php_interface/`.
- **Conflicting docs on `init.php`:** one lesson recommends putting `init.php` in `/local` (since 14.0.1); the migration article says not to create it in `/local/php_interface/` and to include `constants.php`, `events.php`, an autoloader from `/bitrix/php_interface/init.php`. Check which applies to the installed core version, and ask the user if unclear.
- Secrets (payment/delivery keys, DB passwords) never go into the repo.

## 3. Site template
- Location: `/local/templates/<template_id>/` (templates in `/local/templates/` supported since 14.0.0).
- `header.php` = everything before page content, `footer.php` = everything after; the work area is marked `#WORK_AREA#` when cutting up markup ([Шаблон сайта](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=12766)).
- In `<head>`: `$APPLICATION->ShowHead()` (meta, CSS, scripts registered via the API) and `ShowTitle()`. Right after `<body>`: `$APPLICATION->ShowPanel()`, otherwise the admin panel does not appear.
- `template_styles.css` is the template's main CSS file. Paths via `SITE_TEMPLATE_PATH`.
- Connect CSS/JS through the API (`\Bitrix\Main\Page\Asset::getInstance()->addCss()/addJs()`) so merging, minification and caching work; raw `<link>` only as a last resort. If `addCss` does nothing, the forum fix was calling `ShowHead()` first and passing a root-relative path. For debugging markup, disable CSS/JS merge/compression in main-module settings.
- Which template applies to which site/section is set in site settings > Templates (conditions supported).
- Subfolders a template can have: `components/`, `lang/`, `images/`, `page_templates/`.
- **UNVERIFIED:** `description.php`, `.parameters.php`, include-area conventions. Look at the installed core before using.

## 4. Customizing a component template (copy, don't edit)
Copy the whole template (a template is indivisible) to:

`/local/templates/<site_template>/components/<namespace>/<component>/<template_name>/`

Ways: admin UI, Edit mode > "Копировать шаблон компонента"; or manually copy `/bitrix/components/bitrix/<component>/templates/<name>/`. Then assign the copy to the component call and edit only the copy.

Lookup order (site template in `/local`): `/local/templates/<current>/components/`, then `/local/templates/.default/components/`, then `/bitrix/templates/.default/components/`, then the component's own folder. If no template name is set, `.default` is used. `.default` is both a template name and a fallback folder; do not confuse them.

Put project-specific templates in the project site template folder; use `.default` only for templates shared by all site templates.

## 5. Clothing catalog: product + SKU (торговые предложения)
Verified from the docs ([Товары и торговые предложения](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&LESSON_ID=3455), [Свойства товаров и ТП](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&LESSON_ID=12623)):
- Products live in an infoblock used as a trade catalog. Offers (SKU) live in a **separate** offers infoblock, linked to the products infoblock in the Trade Catalog module settings (tab "Каталоги").
- The link creates property "Элемент каталога" (`CML2_LINK`), type "Привязка к товарам". Imports must fill it.
- Size and color are properties of the **offers** infoblock. For offer generation only properties of type "Список" or "Справочник" are available; offer filtering works only on non-multiple "Список", "Справочник", "Привязка к элементам".
- "Показывать в умном фильтре" on a property lets the smart filter work with offers.
- Offers can be generated in admin: pick sizes and colors on the product and the system creates all combinations.
- Display of offer properties on the site is set explicitly in the catalog component settings ("Свойства предложений").
- Stock and quantity accounting can be set per offer. Forum guidance: pass the **parent product ID** as `ELEMENT_ID` to `bitrix:catalog.element`; on offer switch the template fires JS event `onCatalogStoreProductChange`; `bitrix:catalog.store.amount` takes `OFFER_ID` for per-offer warehouse stock. (Community answers, not core docs; verify on the installed version.)
- Module option "Показывать вкладку Торговый каталог для товаров, имеющих торговые предложения": docs recommend unchecking it, otherwise new offers become unavailable.
- Per project rules, stock is kept at SKU level; do not invent workarounds without asking the user.
- **UNVERIFIED:** exact parameter names of `catalog.element` / `catalog.section` for offer properties (e.g. `OFFERS_PROPERTY_CODE`), D7 class names for offers. Read the installed component's `.parameters.php` and docs first.

## 6. Workflow for porting a prototype
1. Do nothing until `/design/` contains files the user called approved.
2. Cut the prototype into `header.php` / `footer.php` / `template_styles.css` in the site template; move tokens to CSS variables.
3. For each component on the page: copy its template per section 4, restyle the copy, keep the component's output logic and `$arResult` usage.
4. Ask before migrations, data deletion, or any irreversible action.
5. Verify by running the site at 375px and 1280px and walking catalog > filter > product > cart > order; state what was not verified.
