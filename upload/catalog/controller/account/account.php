<?php
class ControllerAccountAccount extends Controller {
    
    public function index() {
        if (!$this->customer->isLogged()) {
            $this->session->data["redirect"] = $this->url->link("account/account", "", true);
            $this->response->redirect($this->url->link("account/login", "", true));
        }
        
        $this->load->language("account/account");
        $this->document->setTitle($this->language->get("heading_title"));
        
        $data["breadcrumbs"] = [];
        $data["breadcrumbs"][] = [
            "text" => $this->language->get("text_home"),
            "href" => $this->url->link("common/home"),
        ];
        $data["breadcrumbs"][] = [
            "text" => $this->language->get("text_account"),
            "href" => $this->url->link("account/account", "", true),
        ];
        
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        $data["edit"] = $this->url->link("account/edit", "", true);
        $data["password"] = $this->url->link("account/password", "", true);
        $data["address"] = $this->url->link("account/address", "", true);
        
        $data["credit_cards"] = [];
        $files = glob(DIR_APPLICATION . "controller/extension/credit_card/*.php");
        
        foreach ($files as $file) {
            $code = basename($file, ".php");
            
            if ($this->config->get("payment_" . $code . "_status") && $this->config->get("payment_" . $code . "_card")) {
                $this->load->language("extension/credit_card/" . $code, "extension");
                
                $data["credit_cards"][] = [
                    "name" => $this->language->get("extension")->get("heading_title"),
                    "href" => $this->url->link("extension/credit_card/" . $code, "", true),
                ];
            }
        }
        
        $data["wishlist"] = $this->url->link("account/wishlist");
        $data["order"] = $this->url->link("account/order", "", true);
        $data["download"] = $this->url->link("account/download", "", true);
        
        if ($this->config->get("total_reward_status")) {
            $data["reward"] = $this->url->link("account/reward", "", true);
        } else {
            $data["reward"] = "";
        }
        
        $data["return"] = $this->url->link("account/return", "", true);
        $data["transaction"] = $this->url->link("account/transaction", "", true);
        $data["newsletter"] = $this->url->link("account/newsletter", "", true);
        $data["recurring"] = $this->url->link("account/recurring", "", true);
        
        $this->load->model("account/customer");
        $affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());
        
        if (!$affiliate_info) {
            $data["affiliate"] = $this->url->link("account/affiliate/add", "", true);
        } else {
            $data["affiliate"] = $this->url->link("account/affiliate/edit", "", true);
        }
        
        $data["tracking"] = ($affiliate_info) ? $this->url->link("account/tracking", "", true) : "";
        
        // Loads the default content blocks
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["column_right"] = $this->load->controller("common/column_right");
        $data["content_top"] = $this->load->controller("common/content_top");
        $data["content_bottom"] = $this->load->controller("common/content_bottom");
        $data["footer"] = $this->load->controller("common/footer");
        $data["header"] = $this->load->controller("common/header");
        
        // Retrieves the customer's lastname, Needed for NoraGO API Search
        // The last name on OpenCart should match the last name on NoraGO
        $data["lastname"] = $this->customer->getLastName();
        
        //  Retrieves the customer's custom account number, needed for NoraGO API Search
        //  You need to previously have the account number stored in the customer's custom field
        $accNumber = $this->getCustomerCustomField();
        $data["accountNumber"] = $accNumber;
        
        // Função do módulo de email para obter credenciais
        list($username, $password, $expirationTime) = $this->getCredentials($accNumber, $data["lastname"]);
        
        $data["username"] = $username;
        $data["password"] = $password;
        $data["expirationTime"] = $expirationTime;
        
        // Define a saída do conteúdo
        $this->response->setOutput($this->load->view("account/account", $data));
    }
    
    // Método para obter o campo personalizado do cliente
    private function getCustomerCustomField() {
        if ($this->customer->isLogged()) {
            $customer_id = $this->customer->getId();
            $this->load->model('account/customer');
            $customer_info = $this->model_account_customer->getCustomer($customer_id);
            
            if (isset($customer_info['custom_field'])) {
                $custom_field = json_decode($customer_info['custom_field'], true);
                if (!empty($custom_field)) {
                    return reset($custom_field); // Retorna o primeiro valor do array de campos personalizados
                }
            }
        }
        
        return ''; // Retorna uma string vazia se não houver campo personalizado ou o usuário não estiver logado
    }
    
    // Método para obter informações do assinante usando cURL
    private function getSubscriberInfo($baseURL, $token, $login, $accNumber, $lastName) {
        $url = "https://$baseURL.norago.tv/apex/v2/subscribers/get";
        $payload = json_encode([
            "auth" => [
                "token" => $token,
                "login" => $login,
                "accountNumber" => $accNumber,
                "lastName" => $lastName,
            ],
        ]);
        
        $headers = ["Content-Type: application/json"];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    // Método para obter credenciais usando as informações do assinante
    private function getCredentials($accNumber, $lastName) {
        $baseURL = $this->config->get("module_show_email_base_url");
        $token = $this->config->get("module_show_email_token");
        $login = $this->config->get("module_show_email_login");
        
        $response = $this->getSubscriberInfo($baseURL, $token, $login, $accNumber, $lastName);
        $data = json_decode($response, true);
        
        $username = isset($data["result"]["userName"]) ? $data["result"]["userName"] : "";
        $password = isset($data["result"]["password"]) ? $data["result"]["password"] : "";
        $expirationTime = isset($data["result"]["expirationTime"]) ? $data["result"]["expirationTime"] : "";
        
        // Filter and format expirationTime to "YYYY-MM-DD"
        if ($expirationTime) {
            $dateTime = new DateTime($expirationTime);
            $expirationTime = $dateTime->format("Y-m-d");
        }
        
        return [$username, $password, $expirationTime];
    }
    
    public function country() {
        $json = [];
        $this->load->model("localisation/country");
        
        if (isset($this->request->get["country_id"])) {
            $country_info = $this->model_localisation_country->getCountry($this->request->get["country_id"]);
            
            if ($country_info) {
                $this->load->model("localisation/zone");
                $json = [
                    "country_id" => $country_info["country_id"],
                    "name" => $country_info["name"],
                    "iso_code_2" => $country_info["iso_code_2"],
                    "iso_code_3" => $country_info["iso_code_3"],
                    "address_format" => $country_info["address_format"],
                    "postcode_required" => $country_info["postcode_required"],
                    "zone" => $this->model_localisation_zone->getZonesByCountryId($this->request->get["country_id"]),
                    "status" => $country_info["status"],
                ];
            }
        }
        
        $this->response->addHeader("Content-Type: application/json");
        $this->response->setOutput(json_encode($json));
    }
}
