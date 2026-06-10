<?php
$_['heading_title']  = 'VV Category Tags';
$_['text_extension'] = 'Расширения';
$_['text_home']      = 'Главная';
$_['text_edit']      = 'Настройки модуля';

$_['text_info'] = '
<p>Модуль заменяет плейсхолдеры в описании категории (Admin → Каталог → Категории → Описание).</p>
<table class="table table-bordered table-striped">
    <thead><tr><th>Плейсхолдер</th><th>Что подставляет</th></tr></thead>
    <tbody>
        <tr><td><code>{vv_min_price}</code></td><td>Минимальная цена товаров категории</td></tr>
        <tr><td><code>{vv_max_price}</code></td><td>Максимальная цена товаров категории</td></tr>
        <tr><td><code>{vv_count}</code></td><td>Количество товаров (включая нет в наличии)</td></tr>
        <tr><td><code>{vv_count_instock}</code></td><td>Количество товаров в наличии (quantity &gt; 0)</td></tr>
    </tbody>
</table>
<p class="text-muted">Без учёта спеццен и налога. Кэш: 1 час. Один SQL-запрос на все токены страницы.</p>
';
