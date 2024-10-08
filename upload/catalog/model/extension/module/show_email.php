<?php
class ModelExtensionModuleShowEmail extends Model {
    
    // Método para obter o campo personalizado do cliente
    public function getCustomerCustomField() {
        if ($this->customer->isLogged()) {
            $customer_id = $this->customer->getId();
            $this->load->model('account/customer');
            $customer_info = $this->model_account_customer->getCustomer($customer_id);
            
            if (isset($customer_info['custom_field'])) {
                $custom_field = json_decode($customer_info['custom_field'], true);
                if (is_array($custom_field) && !empty($custom_field)) {
                    return reset($custom_field);
                }
            }
        }
        return ''; 
    }

    // Sua função setCustomerCustomField
    public function setCustomerCustomField($value = "string test") {
        if ($this->customer->isLogged()) {
            $customer_id = $this->customer->getId();
            $this->load->model('account/customer');
            
            // Obter as informações atuais do cliente
            $customer_info = $this->model_account_customer->getCustomer($customer_id);

            // Obter o campo personalizado existente (se houver)
            $custom_field = isset($customer_info[0]) ? json_decode($customer_info['custom_field'], true) : array();
            
            // Define o novo valor para o campo personalizado
            $custom_field['custom_field_key'] = $value; // Substitua 'custom_field_key' pela chave correta do campo personalizado

            // Atualizar o campo personalizado no banco de dados
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET custom_field = '" . $this->db->escape(json_encode($custom_field)) . "' WHERE customer_id = '" . (int)$customer_id . "'");
            
            return true;
        }
        return false; // Retorna falso se o usuário não estiver logado
    }
    // Método para obter credenciais usando as informações do assinante
    public function getCredentials($accNumber, $lastName) {
        $baseURL = $this->config->get("module_show_email_base_url");
        $token = $this->config->get("module_show_email_token");
        $login = $this->config->get("module_show_email_login");

        // Monta a URL da API
        $url = "https://$baseURL.norago.tv/apex/v2/subscribers/get";
        
        // Prepara o payload com os dados de autenticação e da conta do cliente
        $payload = json_encode([
            "auth" => [
                "token" => $token,
                "login" => $login,
                "accountNumber" => $accNumber,
                "lastName" => $lastName,
            ],
        ]);

        // Inicializa o cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        // Executa a requisição e captura a resposta
        $response = curl_exec($ch);

        // Verifica se houve erro na requisição cURL
        if (curl_errno($ch)) {
            $error_msg = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            error_log($error_msg); // Log para depuração
            return ["", "", ""]; // Retorna valores vazios em caso de erro
        }

        // Fecha o cURL
        curl_close($ch);

        // Decodifica a resposta JSON
        $data = json_decode($response, true);

        // Verifica se a resposta contém os dados esperados
        $username = isset($data["result"]["userName"]) ? $data["result"]["userName"] : "";
        $password = isset($data["result"]["password"]) ? $data["result"]["password"] : "";
        $expirationTime = isset($data["result"]["expirationTime"]) ? $data["result"]["expirationTime"] : "";

        // Formata a data de expiração, se disponível
        if ($expirationTime) {
            $dateTime = new DateTime($expirationTime);
            $expirationTime = $dateTime->format("Y-m-d");
        }

        // Retorna os dados (username, password, expirationTime)
        return [$username, $password, $expirationTime];
    }

    public function create_user($subdomain, $token, $login, $first_name, $last_name, $phone, $email) {
        // Monta a URL da API
        $url = 'https://' . $subdomain . '.norago.tv/apex/v2/subscribers/create';
    
        // Gerar números aleatórios de 9 dígitos para username e password
        $username = strval(rand(100000000, 999999999));
        $password = strval(rand(100000000, 999999999));
    
        // Dados a serem enviados
        $data = array(
            "auth" => array(
                "token" => $token,
                "login" => $login
            ),
            "userName" => $username,
            "password" => $password,
            "pinCode" => "1234",
            "firstName" => $first_name,
            "lastName" => $last_name,
            "email" => $email,
            "phone" => $phone,
            "zipCode" => "1840000",
            "address" => "victoria 381",
            "city" => "ovalle",
            "country" => "CL",
            "state" => "",
            "timeZone" => "America/Santiago",
            "language" => "English",
            "dateOfBirth" => ""
        );
    
        // Inicializa o cURL
        $curl = curl_init($url);
    
        // Configura as opções do cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); // Converte o array para JSON
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
    
        // Executa a requisição e captura a resposta
        $response = curl_exec($curl);
    
        // Verifica se ocorreu algum erro
        if (curl_errno($curl)) {
            error_log('Erro no cURL: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }
    
        // Fecha a conexão cURL
        curl_close($curl);
    
        // Decodifica a resposta JSON
        $response_data = json_decode($response, true);
    
        // Verifica se a criação foi bem-sucedida e retorna os dados apropriados
        if (isset($response_data['result']['accountNumber'])) {
            return $response_data['result']['accountNumber']; // Retorna o número da conta
        } else {
            error_log('Erro na criação do usuário: resposta inesperada');
            return false; // Retorna falso em caso de erro
        }
    }

    public function make_payment($subdomain, $token, $login, $account_number, $last_name, $subscription_id) {
        // Monta a URL da API
        $url = 'https://' . $subdomain . '.norago.tv/apex/v2/payments/do';
    
        // Dados a serem enviados
        $data = array(
            "auth" => array(
                "token" => $token,
                "login" => $login,
                "accountNumber" => $account_number,
                "lastName" => $last_name
            ),
            "deviceCount" => 4,
            "subscriptionId" => $subscription_id,
            "approvalRequired" => false,
            "paymentSystemType" => "CASH"
        );
    
        // Inicializa o cURL
        $curl = curl_init($url);
    
        // Configura as opções do cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); // Converte o array para JSON
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
    
        // Executa a requisição e captura a resposta
        $response = curl_exec($curl);
    
        // Verifica se ocorreu algum erro
        if (curl_errno($curl)) {
            error_log('Erro no cURL: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }
    
        // Fecha a conexão cURL
        curl_close($curl);
    
        // Decodifica a resposta JSON
        $response_data = json_decode($response, true);
    
        // Verifica se o pagamento foi bem-sucedido e retorna a resposta apropriada
        if (isset($response_data['status']['code']) && $response_data['status']['code'] == '0') {
            return $response_data; // Retorna a resposta do pagamento
        } else {
            error_log('Erro no pagamento: resposta inesperada');
            return false;
        }
    }

    public function checkout($order_id) {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
    
        if (!$order_info) {
            error_log('Erro: Pedido não encontrado.');
            return false;
        }
    
        $accNumber = $this->getCustomerCustomField();
        $lastName = $order_info['payment_lastname'];
    
        $baseURL = $this->config->get("module_show_email_base_url");
        $token = $this->config->get("module_show_email_token");
        $login = $this->config->get("module_show_email_login");
    
        list($username, $password, $expirationTime) = $this->getCredentials($accNumber, $lastName);
        error_log('Username: ' . $username);
        if ($username && $password) {
            // Se o usuário já existe, faz o pagamento
            $request = $this->make_payment($baseURL, $token, $login, $accNumber, $lastName, 'subscription_id');
        } else {
            // Caso contrário, cria o usuário
            $request = $this->create_user($baseURL, $token, $login, $order_info['payment_firstname'], $order_info['payment_lastname'], $order_info['telephone'], $order_info['email']);
            
            if ($request) {
                // Fazer pagamento após criar o usuário
                $this->setCustomerCustomField($request);
                $request = $this->make_payment($baseURL, $token, $login, $request, $lastName, 'subscription_id');
            } else {
                error_log('Erro ao criar o usuário.');
            }
        }
    
        return $request;
    }
    
}
