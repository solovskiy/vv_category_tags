<?php
class ModelExtensionModuleVvCategoryTags extends Model {

    /**
     * Возвращает агрегаты по товарам категории за один запрос:
     * min_price, max_price, count_all, count_instock.
     * Без налога, без спеццен. quantity не фильтруется (кроме count_instock).
     * Возвращает массив или null если подходящих товаров нет.
     */
    public function getCategoryStats($category_id, $store_id) {
        return $this->aggregate(array('category' => (int)$category_id), $store_id, array());
    }

    /**
     * То же, что getCategoryStats, но с пересечением по фильтру OCFilter.
     * $groups — массив групп фильтра, каждая:
     *   ['filter_id' => int, 'source' => int, 'values' => [строки-цифры value_id]]
     * Внутри группы значения объединяются по OR, группы — по AND.
     */
    public function getFilteredStats($category_id, $store_id, array $groups) {
        return $this->aggregate(array('category' => (int)$category_id), $store_id, $groups);
    }

    /**
     * Статистика по товарам производителя (product.manufacturer_id).
     */
    public function getManufacturerStats($manufacturer_id, $store_id) {
        return $this->aggregate(array('manufacturer' => (int)$manufacturer_id), $store_id, array());
    }

    /**
     * $scope — ['category' => id] или ['manufacturer' => id].
     */
    private function aggregate(array $scope, $store_id, array $groups) {
        $sql = "
            SELECT
                MIN(p.price)                  AS min_price,
                MAX(p.price)                  AS max_price,
                COUNT(DISTINCT p.product_id)  AS count_all,
                SUM(p.quantity > 0)           AS count_instock
            FROM " . DB_PREFIX . "product p
            WHERE p.status = 1
              AND p.price > 0
              AND p.date_available <= NOW()
              AND EXISTS (
                  SELECT 1 FROM " . DB_PREFIX . "product_to_store pts
                  WHERE pts.product_id = p.product_id
                    AND pts.store_id = '" . (int)$store_id . "'
              )";

        if (isset($scope['category'])) {
            $sql .= "
              AND EXISTS (
                  SELECT 1 FROM " . DB_PREFIX . "product_to_category ptc
                  WHERE ptc.product_id = p.product_id
                    AND ptc.category_id = '" . (int)$scope['category'] . "'
              )";
        } elseif (isset($scope['manufacturer'])) {
            $sql .= "
              AND p.manufacturer_id = '" . (int)$scope['manufacturer'] . "'";
        }

        foreach ($groups as $g) {
            $values = array();
            foreach ($g['values'] as $v) {
                $v = preg_replace('/\D/', '', (string)$v);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
            if (!$values) {
                continue;
            }
            $sql .= "
              AND EXISTS (
                  SELECT 1 FROM " . DB_PREFIX . "ocfilter_filter_value_to_product v
                  WHERE v.product_id = p.product_id
                    AND v.filter_id = '" . (int)$g['filter_id'] . "'
                    AND v.source = '" . (int)$g['source'] . "'
                    AND v.value_id IN (" . implode(',', $values) . ")
              )";
        }

        $row = $this->db->query($sql)->row;

        if (empty($row) || $row['min_price'] === null) {
            return null;
        }

        return array(
            'min_price'     => (float)$row['min_price'],
            'max_price'     => (float)$row['max_price'],
            'count_all'     => (int)$row['count_all'],
            'count_instock' => (int)$row['count_instock'],
        );
    }

    /**
     * Возвращает category_id и params (JSON) SEO-страницы OCFilter.
     * null — страницы нет или она выключена.
     */
    public function getOcfilterPage($page_id) {
        $query = $this->db->query("
            SELECT category_id, params
            FROM " . DB_PREFIX . "ocfilter_page
            WHERE page_id = '" . (int)$page_id . "'
              AND status = 1
        ");

        return $query->num_rows ? $query->row : null;
    }
}
