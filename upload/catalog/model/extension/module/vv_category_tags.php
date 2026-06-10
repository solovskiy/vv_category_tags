<?php
class ModelExtensionModuleVvCategoryTags extends Model {

    /**
     * Возвращает агрегаты по товарам категории за один запрос:
     * min_price, max_price, count_all, count_instock.
     * Без налога, без спеццен. quantity не фильтруется (кроме count_instock).
     * Возвращает массив или null если подходящих товаров нет.
     */
    public function getCategoryStats($category_id, $store_id) {
        $query = $this->db->query("
            SELECT
                MIN(p.price)                          AS min_price,
                MAX(p.price)                          AS max_price,
                COUNT(*)                              AS count_all,
                SUM(p.quantity > 0)                   AS count_instock
            FROM " . DB_PREFIX . "product_to_category ptc
            JOIN " . DB_PREFIX . "product p
                ON p.product_id = ptc.product_id
               AND p.status = 1
               AND p.price > 0
               AND p.date_available <= NOW()
            WHERE ptc.category_id = '" . (int)$category_id . "'
              AND EXISTS (
                  SELECT 1 FROM " . DB_PREFIX . "product_to_store pts
                  WHERE pts.product_id = p.product_id
                    AND pts.store_id = '" . (int)$store_id . "'
              )
        ");

        $row = $query->row;

        if (empty($row) || $row['min_price'] === null) {
            return null;
        }

        return [
            'min_price'     => (float)$row['min_price'],
            'max_price'     => (float)$row['max_price'],
            'count_all'     => (int)$row['count_all'],
            'count_instock' => (int)$row['count_instock'],
        ];
    }
}
