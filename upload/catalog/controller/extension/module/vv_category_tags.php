<?php
class ControllerExtensionModuleVvCategoryTags extends Controller {

    const CACHE_TTL = 3600;

    private $stats_loaded = false;
    private $stats_value  = false;

    /**
     * Обработчик события catalog/view/*\/before.
     * Заменяет плейсхолдеры {vv_*} в описании категории, описании SEO-страницы
     * OCFilter и мета-тегах. Срабатывает на страницах категорий и фильтра.
     */
    public function onCategoryView(&$route, &$data, &$template) {
        // Только вью категории и модуля OCFilter — там лежат описания с токенами
        if (strpos($route, 'product/category') === false
            && strpos($route, 'ocfilter') === false) {
            return;
        }

        $handlers = null; // ленивая инициализация — только если найдём токен

        // Мета-теги документа (заголовок/описание)
        $meta_title = $this->document->getTitle();
        if (strpos($meta_title, '{vv_') !== false) {
            $handlers = $handlers ?: $this->getTokenHandlers();
            $this->document->setTitle($this->replaceTokens($meta_title, $handlers));
        }

        $meta_desc = $this->document->getDescription();
        if (strpos($meta_desc, '{vv_') !== false) {
            $handlers = $handlers ?: $this->getTokenHandlers();
            $this->document->setDescription($this->replaceTokens($meta_desc, $handlers));
        }

        // Строковые значения $data (description категории, description_top/bottom OCFilter)
        $this->walkReplace($data, $handlers);
    }

    /**
     * Рекурсивно проходит по $data и заменяет токены в строках.
     * $handlers инициализируется лениво при первом найденном токене.
     */
    private function walkReplace(&$value, &$handlers) {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $this->walkReplace($item, $handlers);
            }
            unset($item);
            return;
        }

        if (is_string($value) && strpos($value, '{vv_') !== false) {
            if ($handlers === null) {
                $handlers = $this->getTokenHandlers();
            }
            $value = $this->replaceTokens($value, $handlers);
        }
    }

    private function replaceTokens($text, $handlers) {
        return preg_replace_callback(
            '/\{(vv_[a-z0-9_]+)\}/',
            function($m) use ($handlers) {
                $token = $m[1];
                return isset($handlers[$token]) ? call_user_func($handlers[$token]) : $m[0];
            },
            $text
        );
    }

    /**
     * Карта токенов. Добавить новый — одна запись.
     * Все токены используют общую кешированную статистику текущей страницы.
     */
    private function getTokenHandlers() {
        return array(
            'vv_min_price'     => function() {
                $s = $this->getStats();
                return $s ? $this->currency->format($s['min_price'], $this->getCurrency()) : '';
            },
            'vv_max_price'     => function() {
                $s = $this->getStats();
                return $s ? $this->currency->format($s['max_price'], $this->getCurrency()) : '';
            },
            'vv_count'         => function() {
                $s = $this->getStats();
                return $s ? (string)$s['count_all'] : '';
            },
            'vv_count_instock' => function() {
                $s = $this->getStats();
                return $s ? (string)$s['count_instock'] : '';
            },
        );
    }

    /**
     * Статистика текущей страницы (с учётом фильтра OCFilter, если он активен).
     * Контекст определяется один раз за запрос и мемоизируется.
     */
    private function getStats() {
        if ($this->stats_loaded) {
            return $this->stats_value;
        }
        $this->stats_loaded = true;

        $store_id = (int)$this->config->get('config_store_id');
        $currency = $this->getCurrency();

        $this->load->model('extension/module/vv_category_tags');
        $model = $this->model_extension_module_vv_category_tags;

        // SEO-страница OCFilter → статистика по отфильтрованной выборке
        if (isset($this->request->get['ocfilter_page_id'])) {
            $page_id = (int)$this->request->get['ocfilter_page_id'];
            $cache_key = 'vv_category_tags.stats.f' . $page_id . '.' . $currency . '.' . $store_id;

            $cached = $this->cache->get($cache_key);
            if ($cached !== false) {
                return $this->stats_value = $cached;
            }

            $page = $model->getOcfilterPage($page_id);
            if ($page) {
                $groups = $this->parseFilterParams($page['params']);
                $stats = $model->getFilteredStats((int)$page['category_id'], $store_id, $groups);
            } else {
                $stats = null;
            }

            $this->cache->set($cache_key, $stats, self::CACHE_TTL);
            return $this->stats_value = $stats;
        }

        // Обычная категория → статистика по всей категории
        $path = isset($this->request->get['path']) ? (string)$this->request->get['path'] : '';
        $parts = explode('_', $path);
        $category_id = (int)end($parts);

        if (!$category_id) {
            return $this->stats_value = null;
        }

        $cache_key = 'vv_category_tags.stats.' . $category_id . '.' . $currency . '.' . $store_id;
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $this->stats_value = $cached;
        }

        $stats = $model->getCategoryStats($category_id, $store_id);
        $this->cache->set($cache_key, $stats, self::CACHE_TTL);
        return $this->stats_value = $stats;
    }

    /**
     * Разбирает params SEO-страницы OCFilter ({"73.2":["valueId",...]})
     * в группы для getFilteredStats. Ключ — "filter_id.source".
     * Диапазонные значения (slider) пропускаются.
     */
    private function parseFilterParams($params_json) {
        $params = json_decode((string)$params_json, true);
        if (!is_array($params)) {
            return array();
        }

        $groups = array();
        foreach ($params as $key => $values) {
            $kp = explode('.', $key);
            if (!ctype_digit((string)$kp[0])) {
                continue; // не атрибутный фильтр (например price-диапазон)
            }
            $filter_id = (int)$kp[0];
            $source    = isset($kp[1]) ? (int)$kp[1] : 0;

            $clean = array();
            foreach ((array)$values as $v) {
                if (ctype_digit((string)$v)) {
                    $clean[] = (string)$v;
                }
            }
            if ($clean) {
                $groups[] = array(
                    'filter_id' => $filter_id,
                    'source'    => $source,
                    'values'    => $clean,
                );
            }
        }

        return $groups;
    }

    private function getCurrency() {
        return isset($this->session->data['currency'])
            ? $this->session->data['currency']
            : $this->config->get('config_currency');
    }
}
