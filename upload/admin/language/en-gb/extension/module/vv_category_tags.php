<?php
$_['heading_title']  = 'VV Category Tags';
$_['text_extension'] = 'Extensions';
$_['text_home']      = 'Home';
$_['text_edit']      = 'Module Settings';

$_['text_info'] = '
<p>Replaces placeholders in category description (Admin → Catalog → Categories → Description).</p>
<table class="table table-bordered table-striped">
    <thead><tr><th>Placeholder</th><th>Replaced with</th></tr></thead>
    <tbody>
        <tr><td><code>{vv_min_price}</code></td><td>Minimum product price in category</td></tr>
        <tr><td><code>{vv_max_price}</code></td><td>Maximum product price in category</td></tr>
        <tr><td><code>{vv_count}</code></td><td>Total product count (including out of stock)</td></tr>
        <tr><td><code>{vv_count_instock}</code></td><td>In-stock product count (quantity &gt; 0)</td></tr>
    </tbody>
</table>
<p class="text-muted">Base price only, no tax, no special prices. Cache TTL: 1 hour. Single SQL per page for all tokens.</p>
';
