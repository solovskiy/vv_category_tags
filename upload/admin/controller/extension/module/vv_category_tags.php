<?php
class ControllerExtensionModuleVvCategoryTags extends Controller {

    public function index() {
        $this->load->language('extension/module/vv_category_tags');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/vv_category_tags', 'user_token=' . $this->session->data['user_token'], true),
            ],
        ];

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/vv_category_tags', $data));
    }

    /**
     * Вызывается при установке модуля — регистрирует событие.
     */
    public function install() {
        $this->load->model('setting/event');

        // Удаляем старую запись на случай переустановки
        $this->model_setting_event->deleteEventByCode('vv_category_tags');

        // trigger хранится с префиксом 'catalog/' — startup/event.php его стрипает
        $this->model_setting_event->addEvent(
            'vv_category_tags',
            'catalog/view/product/category/before',
            'extension/module/vv_category_tags/onCategoryView',
            1,
            0
        );
    }

    /**
     * Вызывается при удалении модуля — снимает событие.
     */
    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('vv_category_tags');
    }
}
