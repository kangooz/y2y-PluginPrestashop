<?php

if (!class_exists('Y2Y_API')) {

    class Y2Y_API {

        protected $host = 'localhost:9000/api';
        protected $curl;
        protected $url = '';
        protected $method = '';
        protected $params;
        private $api_key;
        private $api_secret;
        protected $protocol = 'http';

        public function __construct($api_key, $api_secret, $options = array()) {
            $this->api_key = $api_key;
            $this->api_secret = $api_secret;

            $this->curl = curl_init();

            $defaults = array(
                'protocol' => 'http',
                'host' => $this->host,
            );

            $options = array_merge($defaults, $options);

            $this->protocol = $options['protocol'];
            $this->host = $options['host'];

            // Switch cURL to not worry about SSL certificate checking.
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        
        public function close(){
            curl_close($this->curl);
            $this->curl = null;
            
            return true;
        }

        public function get($url, $params = array()) {
            $this->method = 'GET';

            $this->url = $url;
            
            $this->params = $params;

            return $this->exec();
        }
        
        public function post($url, $params = array()){
            $this->method = 'POST';
            
            $this->url = $url;
            
            $this->params = $params;
            
            return $this->exec();
        }
        
        public function put($url, $params = array()){
            $this->method = 'PUT';
            
            $this->url = $url;
            
            $this->params = $params;
            
            return $this->exec();
        }
        
        public function patch($url, $params = array()){
            $this->method = 'PATCH';
            
            $this->url = $url;
            
            $this->params = $params;
            
            return $this->exec();
        }
        
        public function delete($url, $params = array()){
            $this->method = 'DELETE';
            
            $this->url = $url;
            
            $this->params = $params;
            
            return $this->exec();
        }

        private function exec() {

            if (empty($this->method) || empty($this->url)) {
                return array(
                    
                );
            }

            switch ($this->method) {
                case 'GET':
                case 'get':
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
                    $getParams = null;
                    if (!empty($this->params)) {
                        foreach ($this->params as $field_name => $field_value) {
                            $getParams .= $field_name . '=' . urlencode($field_value) . '&';
                        }

                        curl_setopt($this->curl, CURLOPT_URL, $this->buildUrl() . '&' . $getParams);
                    } else {
                        curl_setopt($this->curl, CURLOPT_URL, $this->buildUrl());
                    }
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
                    break;

                case 'POST':
                case 'post':
                    $params = json_encode($this->params);

                    #$string = $params['params'];
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: POST'));
                    curl_setopt($this->curl, CURLOPT_URL, $this->buildUrl());
                    curl_setopt($this->curl, CURLOPT_POST, true);
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params))
                    );
                    break;

                case 'PUT':
                case 'put':
                    $params = json_encode($this->params);
                    
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
                    curl_setopt($this->curl, CURLOPT_URL, $this->buildUrl());
                    curl_setopt($this->curl, CURLOPT_POST, true);
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params))
                    );
                    break;
                
                case 'PATCH':
                case 'patch':
                    $params = json_encode($this->params);
                    
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PATCH'));
                    curl_setopt($this->curl, CURLOPT_URL, $this->buildUrl());
                    curl_setopt($this->curl, CURLOPT_POST, true);
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params))
                    );
                    break;
                
                case 'DELETE':
                case 'delete':
                    $params = json_encode($this->params);
                    
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: DELETE'));
                    curl_setopt($this->curl, CURLOPT_URL, $this->buildUrl());
                    curl_setopt($this->curl, CURLOPT_POST, true);
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params))
                    );
                    break;
            }
            
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

            return array(
                'result' => json_decode(curl_exec($this->curl)),
                'reponse_code' => curl_getinfo($this->curl, CURLINFO_HTTP_CODE),
                'errno' => curl_errno($this->curl),
                'error' => curl_error($this->curl)
            );
        }

        private function buildUrl() {
            $url = $this->protocol . '://' . $this->host . '/' . $this->url . '?api_secret=' . $this->api_secret . '&api_key=' . $this->api_key;
            //$url = $this->protocol . '://' . $this->host . '/' . $this->url . '?access_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwicm9sZSI6Ikluc2NyaXQiLCJpYXQiOjE0NTQ0MzIxNTAsImV4cCI6MTQ1NDQzNTc1MH0.fBGNYzaNzqsd_e78DcXDHnzT8FFIFLFrMm6ATVl_eKI';
            return $url;
        }
        
        public function test_connection(){
            return true;
            if(empty($this->api_secret) || empty($this->api_key)){
                return false;
            }
            
            $result = $this->get('/deliveries');
            
            if(in_array($result['response_code'], array(401, 403))){
                return false;
            }
            
            if($result['errno'] != 0){
                return false;
            }
            
            return true;
            
        }

    }

}


