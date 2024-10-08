<?php
class ControllerExtensionModuleShowEmail extends Controller {
    private $error = array();

    // Adicionar o método de instalação
    public function install() {
        // Registrar o XML como uma modificação OCMOD
        $this->load->model('setting/modification');
        
        $xml_file = DIR_APPLICATION . 'controller/extension/module/installCode.xml';
        
        if (file_exists($xml_file)) {
            $xml = file_get_contents($xml_file);
            
            $modification_data = array(
                'name'        => 'NGO-Cart',
                'code'        => 'show_email_mod',  // Identificador único
                'author'      => 'theHatb0y',
                'version'     => '1.0',
                'link'        => 'https://yourwebsite.com',
                'status'      => 1,  // Ativado
                'xml'         => $xml,
                'date_added'  => date('Y-m-d H:i:s')
            );

            // Adicionar a modificação
            $this->model_setting_modification->addModification($modification_data);

            // Atualizar modificações para aplicar as mudanças
            $this->load->controller('extension/modification/refresh');
        }
    }

    // Adicionar o método de desinstalação
    public function uninstall() {
        // Remover a modificação quando o módulo for desinstalado
        $this->load->model('setting/modification');
        $this->model_setting_modification->deleteModification('show_email_mod');
        
        // Atualizar as modificações para remover as mudanças
        $this->load->controller('extension/modification/refresh');
    }

    public function index() {
        $this->load->language('extension/module/show_email');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_show_email', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_base_url'] = $this->language->get('entry_base_url');
        $data['entry_token'] = $this->language->get('entry_token');
        $data['entry_login'] = $this->language->get('entry_login');
        $data['entry_provider_id'] = $this->language->get('entry_provider_id');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ),
            array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/show_email', 'user_token=' . $this->session->data['user_token'], true)
            )
        );

        $data['action'] = $this->url->link('extension/module/show_email', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_show_email_status'])) {
            $data['module_show_email_status'] = $this->request->post['module_show_email_status'];
        } else {
            $data['module_show_email_status'] = $this->config->get('module_show_email_status');
        }

        if (isset($this->request->post['module_show_email_base_url'])) {
            $data['module_show_email_base_url'] = $this->request->post['module_show_email_base_url'];
        } else {
            $data['module_show_email_base_url'] = $this->config->get('module_show_email_base_url');
        }

        if (isset($this->request->post['module_show_email_token'])) {
            $data['module_show_email_token'] = $this->request->post['module_show_email_token'];
        } else {
            $data['module_show_email_token'] = $this->config->get('module_show_email_token');
        }

        if (isset($this->request->post['module_show_email_login'])) {
            $data['module_show_email_login'] = $this->request->post['module_show_email_login'];
        } else {
            $data['module_show_email_login'] = $this->config->get('module_show_email_login');
        }

        if (isset($this->request->post['module_show_email_provider_id'])) {
            $data['module_show_email_provider_id'] = $this->request->post['module_show_email_provider_id'];
        } else {
            $data['module_show_email_provider_id'] = $this->config->get('module_show_email_provider_id');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/show_email', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/show_email')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

}
