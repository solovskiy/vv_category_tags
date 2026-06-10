# vv_category_tags

Подстановка плейсхолдеров в описание категории OpenCart 3.0.2 (ocStore).

## Плейсхолдеры

| Токен | Что подставляет |
|---|---|
| `{vv_min_price}` | Минимальная цена товаров, отформатированная в текущей валюте |
| `{vv_max_price}` | Максимальная цена товаров |
| `{vv_count}` | Кол-во товаров |
| `{vv_count_instock}` | Кол-во товаров в наличии (`quantity > 0`) |

## Где работают токены

- **Страницы категорий** — в `Description` категории. Статистика по всей категории.
- **Страницы производителей** — в описании/мета производителя
  (`oc_manufacturer_description`). Статистика по товарам производителя
  (`product.manufacturer_id`).
- **SEO-страницы OCFilter** — в `description_top` / `description_bottom` / мета-тегах
  SEO-страницы фильтра. Статистика считается **по отфильтрованной выборке**
  (товары категории страницы ∩ значения фильтра OCFilter).

Замена выполняется через событие `catalog/view/*/before`: обработчик ловит вью
категории, производителя и модуля OCFilter и заменяет токены во всех строковых
полях `$data` плюс в `meta_title` / `meta_description` документа.

## Установка

1. Упаковать `upload/` в `.ocmod.zip`
2. Admin → Extensions → Installer → загрузить архив
3. Admin → Extensions → Modules → VV Category Tags → Install

`install.xml` не нужен — расширение не правит core-файлы.

## Использование

В Admin → Catalog → Categories → Description (вкладка нужного языка):

```
Смесители от {vv_min_price} — широкий выбор в нашем каталоге.
```

## Логика расчёта

- `MIN/MAX(product.price)`, `COUNT(DISTINCT product)` по товарам категории (прямые, без подкатегорий)
- Фильтры: `status=1`, `price>0`, `date_available <= NOW()`, привязка к текущему store
- На SEO-странице OCFilter добавляется пересечение с `oc_ocfilter_filter_value_to_product`
  по параметрам страницы (`oc_ocfilter_page.params`)
- Спеццены (`oc_product_special`) **не учитываются**
- Налог **не применяется** (голый `product.price` → `currency->format()`)
- `quantity` не фильтруется (кроме `{vv_count_instock}`)

### Ограничение OCFilter
Учитываются только дискретные значения фильтра (атрибуты/опции).
Диапазонные фильтры (slider цены) в выборку **не вносят ограничение** —
такая группа параметров пропускается.

## Кэш

TTL = 3600 сек (1 час). Ключи:
- категория: `vv_category_tags.stats.{category_id}.{currency}.{store_id}`
- производитель: `vv_category_tags.stats.m{manufacturer_id}.{currency}.{store_id}`
- SEO-страница OCFilter: `vv_category_tags.stats.f{page_id}.{currency}.{store_id}`

## Добавить новый плейсхолдер

В `catalog/controller/extension/module/vv_category_tags.php`, метод `getTokenHandlers()`:

```php
'vv_count' => function() {
    $s = $this->getStats();
    return $s ? (string)$s['count_all'] : '';
},
```
