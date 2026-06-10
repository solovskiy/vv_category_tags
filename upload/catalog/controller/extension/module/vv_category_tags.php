<?php
class ControllerExtensionModuleVvCategoryTags extends Controller {

    const CACHE_TTL = 3600;

    /**
     * Обработчик события view/product/category/before.
     * Заменяет плейсхолдеры {vv_*} в описании и мета-тегах категории.
     */
    public function onCategoryView(&$route, &$data, &$template) {
        $desc        = isset($data['description']) ? (string)$data['description'] : '';
        $meta_title  = $this->document->getTitle();
        $meta_desc   = $this->document->getDescription();

        // Быстрая проверка: есть ли токены хоть в одном поле
        $has_token = strpos($desc, '{vv_') !== false
            || strpos($meta_title, '{vv_') !== false
            || strpos($meta_desc, '{vv_') !== false;

        if (!$has_token) {
            return;
        }

        $path = isset($this->request->get['path']) ? (string)$this->request->get['path'] : '';
        $parts = explode('_', $path);
        $category_id = (int)end($parts);

        if (!$category_id) {
            return;
        }

        $handlers = $this->getTokenHandlers($category_id);

        // Описание категории
        if (strpos($desc, '{vv_') !== false) {
            $data['description'] = $this->replaceTokens($desc, $handlers);
        }

        // meta_title (document)
        if (strpos($meta_title, '{vv_') !== false) {
            $this->document->setTitle($this->replaceTokens($meta_title, $handlers));
        }

        // meta_description (document)
        if (strpos($meta_desc, '{vv_') !== false) {
            $this->document->setDescription($this->replaceTokens($meta_desc, $handlers));
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
     * Все токены используют общий кешированный getCategoryStats().
     */
    private function getTokenHandlers($category_id) {
        return [
            'vv_min_price'     => function() use ($category_id) {
                $s = $this->getStats($category_id);
                return $s ? $this->currency->format($s['min_price'], $this->getCurrency()) : '';
            },
            'vv_max_price'     => function() use ($category_id) {
                $s = $this->getStats($category_id);
                return $s ? $this->currency->format($s['max_price'], $this->getCurrency()) : '';
            },
            'vv_count'         => function() use ($category_id) {
                $s = $this->getStats($category_id);
                return $s ? (string)$s['count_all'] : '';
            },
            'vv_count_instock' => function() use ($category_id) {
                $s = $this->getStats($category_id);
                return $s ? (string)$s['count_instock'] : '';
            },
        ];
    }

    /**
     * Возвращает статистику категории из кеша или БД.
     * Все четыре токена делят один кеш-ключ — один SQL на страницу.
     */
    private function getStats($category_id) {
        $store_id = (int)$this->config->get('config_store_id');
        $currency = $this->getCurrency();

        $cache_key = 'vv_category_tags.stats.' . $category_id . '.' . $currency . '.' . $store_id;

        $cached = $this->cache->get($cache_key);

        // cache->get() возвращает false при промахе
        if ($cached !== false) {
            return $cached; // null (нет товаров) или массив со статистикой
        }

        $this->load->model('extension/module/vv_category_tags');
        $stats = $this->model_extension_module_vv_category_tags->getCategoryStats($category_id, $store_id);

        // кешируем и null (нет товаров) — чтобы не долбить БД повторно
        $this->cache->set($cache_key, $stats, self::CACHE_TTL);

        return $stats;
    }

    private function getCurrency() {
        return isset($this->session->data['currency'])
            ? $this->session->data['currency']
            : $this->config->get('config_currency');
    }
}
