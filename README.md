# vv_category_tags

Подстановка плейсхолдеров в описание категории OpenCart 3.0.2 (ocStore).

## Плейсхолдеры

| Токен | Что подставляет |
|---|---|
| `{vv_min_price}` | Минимальная цена товаров категории, отформатированная в текущей валюте |

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

## Логика расчёта цены

- `MIN(product.price)` по товарам категории (только прямые, без подкатегорий)
- Фильтры: `status=1`, `price>0`, `date_available <= NOW()`, привязка к текущему store
- Спеццены (`oc_product_special`) **не учитываются**
- Налог **не применяется** (голый `product.price` → `currency->format()`)
- `quantity` не фильтруется (товары без остатка учитываются)

## Кэш

TTL = 3600 сек (1 час). Ключ: `vv_category_tags.min_price.{category_id}.{currency}.{store_id}`.

## Добавить новый плейсхолдер

В `catalog/controller/extension/module/vv_category_tags.php`, метод `getTokenHandlers()`:

```php
'vv_count' => function() use ($category_id) {
    // своя логика
    return $this->getProductCountFormatted($category_id);
},
```
