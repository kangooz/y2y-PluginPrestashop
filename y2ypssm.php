<?php

/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . "/y2ypssm/classes/Y2Y_API.php");

class Y2YPSSM extends CarrierModule {

    protected $config_form = false;
    private $_fieldsList = array();
    private $_hooks = array(
        'updateCarrier',
        'backOfficeHeader', //Load css and js
        'header', //Load css and js
        'displayCarrierList', //Show the delivery_date field in the front end
        'actionCarrierProcess', //Validate the delivery_date field in the front end
        'actionValidateOrder', //Register a temporary y2y delivery
        'actionOrderStatusUpdate', //Register the delivery in y2y
    );
    
    private $_notices = array();
    private $_api;
    protected $validApi = false;

    public function __construct() {
        $this->name = 'y2ypssm';
        $this->tab = 'shipping_logistics';
        $this->version = '0.0.1';
        $this->author = 'Partner IT Group';
        $this->limited_countries = array('fr');
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('You2You');
        $this->description = $this->l('You2You, collaborative delivery. Boost your sales by offering a delivery service in 3 hours or by appointment.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');

        $this->_loadFields();
        $this->validApi = $this->_testApi();
        
        if (self::isInstalled($this->name)) {
            if (!$this->validApi) {
                $this->warning .= $this->l('Your api key and/or secret are not valid. The You2You shipping method will not be available until you correct those fields.') . ' ';
                $this->_addWarningMessage($this->l('Your api key and/or secret are not valid. The You2You shipping method will not be available until you correct those fields.'));
            }
            $this->_registerHooks();
        }
    }
    
    private function _loadFields() {
        // Loading Fields List
        $this->_fieldsList = array(
            'Y2YPSSM_API_KEY' => array('type' => 'text', 'desc' => $this->l('Api Key')),
            'Y2YPSSM_API_SECRET' => array('type' => 'text', 'desc' => $this->l('Api Secret')),
            'Y2YPSSM_TIMEOUT' => array('type' => 'number', 'desc' => $this->l('Timeout')),
            'Y2YPSSM_STORE_COUNTRY' => array('type' => 'text', 'desc' => $this->l('Store Country')),
            'Y2YPSSM_STORE_CITY' => array('type' => 'text', 'desc' => $this->l('Store City')),
            'Y2YPSSM_STORE_ADDRESS' => array('type' => 'text', 'desc' => $this->l('Store Address')),
            'Y2YPSSM_STORE_POSTALCODE' => array('type' => 'text', 'desc' => $this->l('Store Postalcode')),
            'Y2YPSSM_STORE_INFORMATION' => array('type' => 'textarea', 'desc' => $this->l('Store Information')),
            'Y2YPSSM_OPENING_HOURS' => array('type' => 'time', 'desc' => $this->l('Opening hours')),
            'Y2YPSSM_CLOSING_HOURS' => array('type' => 'time', 'desc' => $this->l('Opening hours')),
            'Y2YPSSM_LUNCH_TIME_BEGIN' => array('type' => 'time', 'desc' => $this->l('Lunch time')),
            'Y2YPSSM_LUNCH_TIME_END' => array('type' => 'time', 'desc' => $this->l('Lunch time')),
            'Y2YPSSM_CLOSED_DAY' => array('type' => 'checkbox', 'desc' => $this->l('Closed day')),
        );
    }
    
    private function _testApi() {
        $api_key = Configuration::get('Y2YPSSM_API_KEY');
        $api_secret = Configuration::get('Y2YPSSM_API_SECRET');
        $this->_api = new Y2Y_API($api_key, $api_secret);

        if (!$this->_api->test_connection()) {
            return false;
        } else {
            return true;
        }
    }
    
    /*******************************/
    /********* BACK OFFICE ********/
    /*******************************/
    private function _registerHooks(){
        foreach ($this->_hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }
        return true;
    }
    
    private function _unregisterHooks(){
        foreach ($this->_hooks as $hook) {
            $this->unregisterHook($hook);
        }
        return true;
    }
    
    private function _formatNotices() {
        $html = '';
        if (count($this->_notices) == 0) {
            return $html;
        }

        foreach ($this->_notices as $type => $messages) {
            foreach ($messages as $message) {
                $html .= '<div class="alert alert-' . $type . '">' . $message . '</div>';
            }
        }

        return $html;
    }

    private function _addSuccessMessage($message) {
        $this->_notices['success'][] = $message;
    }

    private function _addErrorMessage($message) {
        $this->_notices['danger'][] = $message;
    }

    private function _addWarningMessage($message) {
        $this->_notices['warning'][] = $message;
    }
    
    private function _addDefaultValues($values){
        $y2y_values = $this->getConfigValues();
        foreach($values as $y2y_key => $ps_key){
            
            if(empty($y2y_values[$y2y_key])){
                Configuration::updateValue($y2y_key, Configuration::get($ps_key, ''));
            }
            
        }
    }
    public function install() {
        if (!extension_loaded('curl')) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        if (!parent::install()) {
            return false;
        }

        $carrier = $this->addCarrier();
        if($carrier){
            Configuration::updateValue('Y2YPSSM_CARRIER_ID', (int)$carrier->id);
        }else{
            return false;
        }
        
        include(dirname(__FILE__) . '/sql/install.php');

        if(!$this->_registerHooks()){
            return false;
        }
        
        //Add default values
        $this->_addDefaultValues(array(
            'Y2YPSSM_STORE_ADDRESS' => 'PS_SHOP_ADDR1',
            'Y2YPSSM_STORE_CITY' => 'PS_SHOP_CITY',
            'Y2YPSSM_STORE_COUNTRY' => 'PS_SHOP_COUNTRY',
            'Y2YPSSM_STORE_POSTALCODE' => 'PS_SHOP_CODE',
            'Y2YPSSM_STORE_INFORMATION' => 'PS_SHOP_DETAILS'
        ));
        return true;
    }

    public function uninstall() {
        if (!parent::uninstall()) {
            return false;
        }

        //Delete the keys
        foreach ($this->_fieldsList as $key => $type) {
            Configuration::deleteByName($key);
        }

        //Unregister the hooks
        $this->_unregisterHooks();

        //Delete the carrier
        $carrier_id = Configuration::get('Y2YPSSM_CARRIER_ID');
        $carrier = new Carrier($carrier_id);
        $carrier->delete();

        Configuration::deleteByName('Y2YPSSM_CARRIER_ID');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return true;
    }
    
    public function getContent() {
        $html = '';
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submit' . $this->name)) == true && $this->validateAdminFields()) {
            $this->saveAdminFields();
        }

        //Check if there are any messages to write before the form
        $html .= $this->_formatNotices();

        $this->context->smarty->assign('moduleDir', $this->_path);
        $this->context->smarty->assign('moduleName', $this->name);
        $this->context->smarty->assign('formFields', $this->_fieldsList);
        $this->context->smarty->assign('daysOfWeek', $this->getDaysOfWeek());
        $this->context->smarty->assign('configValues', $this->getConfigValues());

        $submitUrl = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules');
        
        /*array(
            'tab_module' => htmlentities($this->tab),
            'configure' => htmlentities($this->name),
            'token' => htmlentities(Tools::getAdminTokenLite('AdminModules')),
            'module_name' => htmlentities($this->name)
        );*/

        $this->context->smarty->assign('submitUrl', $submitUrl);
        $html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $html;
    }
    
    protected function validateAdminFields() {
        //validate hours of days
        $openingHours = Tools::getValue('Y2YPSSM_OPENING_HOURS');
        $closingHours = Tools::getValue('Y2YPSSM_CLOSING_HOURS');
        $closedDay = Tools::getValue('Y2YPSSM_CLOSED_DAY');
        $invalidDays = array();
        $daysOfWeek = $this->getDaysOfWeek();
        
        foreach($daysOfWeek as $i => $day) {
            if(!empty($openingHours[$i]) && !empty($closingHours[$i])){
                $closedDay[$i] = '';
            }else if(!empty($closedDay[$i])){
                $openingHours[$i] = '';
                $closingHours[$i] = '';
            }else if(!empty($openingHours) || !empty($closingHours[$i])){
                $openingHours[$i] = '';
                $closingHours[$i] = '';
                $closedDay[$i] = 'yes';
            }
            
            if(empty($closedDay[$i]) && strtotime($openingHours[$i]) >= strtotime($closingHours[$i])){
                $invalidDays[] = $day;
            }
            
        }
        $_POST['Y2YPSSM_OPENING_HOURS'] = $openingHours;
        $_POST['Y2YPSSM_CLOSING_HOURS'] = $closingHours;
        $_POST['Y2YPSSM_CLOSED_DAY'] = $closedDay;
        
        if(count($invalidDays) > 0){
            $this->_addErrorMessage(sprintf($this->l('Invalid hours in the following day(s): %s'),implode(', ', $invalidDays)));
            
        }
        
        return (count($this->_notices['error']) == 0);
    }
    
    protected function saveAdminFields() {
        
        foreach ($this->_fieldsList as $key => $type) {
            $value = Tools::getValue($key);
            if (is_array($value)) {
                $value = serialize($value);
            }else{
                Configuration::updateValue($key, $value);
            }
            
        }

        $this->_addSuccessMessage($this->l('Information saved'));
    }
    
    protected function addCarrier() {
        $carrier = new Carrier();

        $carrier->name = $this->l('You2You Delivery Service');
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->deleted = 0;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = sprintf($this->l('%d hours'), 2);
        }

        if ($carrier->add()) {
            copy(dirname(__FILE__) . '/assets/img/y2y.png', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg');
            Configuration::updateValue('Y2YPSSM_CARRIER_ID', (int) $carrier->id);
            $this->addZones($carrier);
            $this->addGroups($carrier);
            $this->addRanges($carrier);
            return $carrier;
        }


        return false;
    }

    protected function addGroups($carrier) {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group)
            $groups_ids[] = $group['id_group'];

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier) {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier) {
        $carrier->addZone('1'); //Europe
    }
    
    public function hookBackOfficeHeader() {
        if (Tools::getValue('module_name') == $this->name) {
            $this->_loadCss();
            $this->_loadJs();
        }
    }
    
    public function hookUpdateCarrier($params) {
        /**
         * Not needed since 1.5
         * You can identify the carrier by the id_reference
         */
    }
    
    public function hookActionOrderStatusUpdate($params){
        $status = $params['newOrderStatus'];
        if(!in_array($status->id, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_WS_PAYMENT'), Configuration::get('PS_OS_PREPARATION')))){
            return;
        }
        
        $order_id = $params['id_order'];
        $db_row = DB::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'y2ypssm_deliveries WHERE status = 1 AND ps_order_id = '.$order_id, false);
        if($db_row){
            /** Send request through api **/
            $order = new Order($order_id);

            $order_details = '';
            $products = $order->getProducts();

            foreach($products as $product){
                $order_details .= '\t'.sprintf($this->l('Name: %s'),$product['product_name']).'\n';
                $order_details .= '\t'.sprintf($this->l('Quantity: %d'),$product['product_quantity']).'\n';
                $order_details .= '\t'.sprintf($this->l('Width: %.2f'),$product['width']).'\n';
                $order_details .= '\t'.sprintf($this->l('Depth: %.2f'),$product['depth']).'\n';
                $order_details.= '\t'.sprintf($this->l('Height: %.2f'),$product['height']).'\n';
                $order_details.= '\t'.sprintf($this->l('Weight: %.2f'),$product['weight']).'\n\n';
            }
            $order_details_html = str_replace('\n',"<br>",$order_details);
            $order_details_html = str_replace('\t',"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$order_details_html);
            
            $delivery_address = new Address($order->id_address_delivery);
            $customer = $order->getCustomer();
            $configValues = $this->getConfigValues();
            
            /*$this->api->post('deliveries', array(
                'street' => $delivery_address->address1,
                'city' => $delivery_address->city,
                'country' => $delivery_address->country,
                'postalcode' => $delivery_address->postcode,
                'information' => $delivery_address->other,
                'company' => $delivery_address->company,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'shipstart' => date('d/m/Y H:i:s',strtotime($db_row['delivery_date'])),
                'shipend' => date('d/m/Y H:i:s', strtotime('+2 hours', strtotime($db_row['delivery_date']))),
                'compensation' => 8


            ));*/
            
            
            $templateVars = array(
                '{store_address}' => $configValues['Y2YPSSM_STORE_ADDRESS']
                                        .', '.$configValues['Y2YPSSM_STORE_POSTALCODE']
                                        .' '.$configValues['Y2YPSSM_STORE_CITY']
                                        .' '.$configValues['Y2YPSSM_STORE_COUNTRY'],
                '{store_information}' => $configValues['Y2YPSSM_STORE_INFORMATION'],
                '{order_details}' => $order_details,
                '{order_details_html}' => $order_details_html,
                '{destination_address}' => $delivery_address->address1
                                                .', '.$delivery_address->postcode
                                                .' '.$delivery_address->city
                                                .' '.$delivery_address->country,
                '{destination_address_2}' => $delivery_address->address2,
                '{destination_information}' => $delivery_address->other,
                '{company}' => $delivery_address->company,
                '{customer_name}' => $customer->firstname.' '.$customer->lastname,
                '{shipstart}' => date('d/m/Y H:i:s',strtotime($db_row['delivery_date'])),
                '{shipend}' => date('d/m/Y H:i:s', strtotime('+2 hours', strtotime($db_row['delivery_date']))),
                '{compensation}' => 8,
                '{reference}' => $order->reference
                
            );
            $sent = Mail::Send(
                    $db_row['ps_lang_id'], //Language
                    'delivery', //Template name
                    Mail::l('You2You Order', $db_row['ps_lang_id']), //Subject
                    $templateVars, //Template vars
                    'contact@you2you.fr', //to
                    'You2You', //To Name
                    null, //From
                    null, //From name
                    null, //File attachment
                    null, //Mode smtp
                    dirname(__FILE__).'/mails/', //Template path
                    false, //die
                    null, //id_shop
                    'support@partner-it-group.com' //bcc
            );
            
            if($sent){
                DB::getInstance()->update('y2ypssm_deliveries',
                        array('status' => 2),
                        'ps_order_id = '.(int)$order_id
                );
            }
        }
    }
    
    /*******************************/
    /********* FRONT OFFICE ********/
    /*******************************/
    private function _validateDeliveryDate($delivery_date){
        if(empty($delivery_date)){
            return $this->l('We need to know the date for the delivery');
        }

        $delivery_date = date('Y-m-d H:i:s', strtotime($delivery_date));
        $today = date('Y-m-d H:i:s');

        if($today > $delivery_date){
            return $this->l('The delivery date should be after today\'s date');
        }
        
        $configValues = $this->getConfigValues();

        $timestamp = strtotime($delivery_date);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);
        $hour = date('H', $timestamp);
        $minute = date('i', $timestamp);
        $dayofweek = date('w', $timestamp);

        $timeout = (float)str_replace(',','.',$configValues['Y2YPSSM_TIMEOUT']);
        
        $timeout = ($timeout > 2) ? $timeout*60*60 : 2*60*60;
        $order_hour = date('H:i',$timestamp);
        $delivery_hour = strtotime(date('H:i',$timestamp+$timeout));
        $delivery_day_hour = strtotime(date('Y/m/d H:i',$timestamp+$timeout));

        
        //Closed day
        if($configValues['Y2YPSSM_CLOSED_DAY'][$dayofweek] == 'yes'){
            return $this->l('The shop is closed in this day');
        }

        //Before opening hours
        if(strtotime($order_hour) < strtotime($configValues['Y2YPSSM_OPENING_HOURS'][$dayofweek])){
            return $this->l('The shop is closed at that hour in the morning');
        }

        //After opening hours
        if( $delivery_hour > strtotime($configValues['Y2YPSSM_CLOSING_HOURS'][$dayofweek])){
            return $this->l('The shop is closed at that hour in the afternoon');
        }
        
        return true;
    }
    
    public function getOrderShippingCost($params, $shipping_cost) {
        if (!$this->active) {
            return false;
        }

        if (!$this->validApi) {
            return false;
        }
        /*$id_address_delivery = Context::getContext()->cart->id_address_delivery;
        $address = new Address($id_address_delivery);*/
        /**
         * Send the details through the API
         * Return the price sent by the API
         */
        return (float)8;
        
    }

    public function getOrderShippingCostExternal($params) {
        return $this->getOrderShippingCost($params, null);
    }
    
    public function hookHeader() {
        $this->_loadCss();
        $this->_loadJs();
    }
    
    public function hookDisplayCarrierList($params) {
        $minDate = $this->getMinDate();
        if(Tools::getValue('id_delivery_option') == Configuration::get('Y2YPSSM_CARRIER_ID').','){
            ob_start();?>
            <div class="form-group form-group-sm">
                <label for="y2ypssm_delivery_date"><?php echo $this->l('Delivery Date');?></label>
                <input type="text" class="form-control" style="width:40%" id="y2ypssm_delivery_date" name="y2ypssm_delivery_date" required="" data-field="datetime">
                <div class="y2ypssm-datepicker-holder"></div>
            </div>
            <script type="text/javascript">
                $(".y2ypssm-datepicker-holder").DateTimePicker({
                    mode: "datetime",
                    dateTimeFormat: 'dd-MM-yyyy HH:mm',
                    minDateTime: '<?php echo $minDate; ?>',
                    language: 'fr',
                    minuteInterval: 15,
                    roundOffMinutes: true,
                    defaultDate: '<?php $this->getMinDateForJS($minDate); ?>'
                });
            </script>
            <?php
            return ob_get_clean();
        }
        return "";
    }
    
    protected function getMinDateForJS($date = ''){
        if($date == ''){
            return date('D M d Y H:i:s O', strtotime($this->getMinDate()));
        }else{
            return date('D M d Y H:i:s O', strtotime($date));
        }
    }
    
    protected function getMinDate(){
        $todayWithTimeout = date('d-m-Y H:i', strtotime('+'.(int)Configuration::get('Y2YPSSM_TIMEOUT',2).' hours'));
        return $todayWithTimeout;
    }
    
    public function hookActionCarrierProcess($params){
        $order_carrier_id = $params['cart']->id_carrier;
        
        if($order_carrier_id == Configuration::get('Y2YPSSM_CARRIER_ID')){
            $delivery_date = Tools::getValue('y2ypssm_delivery_date','');
            if( ($error_message = $this->_validateDeliveryDate($delivery_date)) !== true){
                $this->context->controller->errors[] = $error_message;
                return false;
            }else{
                DB::getInstance()->insert('y2ypssm_deliveries', array(
                    'ps_cart_id' => (int)$params['cart']->id,
                    'delivery_date' => date('Y-m-d H:i:s',strtotime($delivery_date)),
                    'status' => 1,
                    'ps_lang_id' => $params['cart']->id_lang
                ));
            }
            
        }
        return true;
    }
    
    public function hookActionValidateOrder($params){
        /*array(
            'cart' => $this->context->cart,
            'order' => $order,
            'customer' => $this->context->customer,
            'currency' => $this->context->currency,
            'orderStatus' => $order_status
        */
        DB::getInstance()->update('y2ypssm_deliveries', 
                array('ps_order_id' => (int)$params['order']->id), 
                'ps_cart_id = '.(int)$params['cart']->id
        );
    }
    
    /*****************************************/
    /********* BACK AND FRONT OFFICE *********/
    /*****************************************/
    private function _loadCss() {
        $this->context->controller->addCSS($this->_path . 'assets/css/DateTimePicker.css');
        $this->context->controller->addCSS($this->_path . 'assets/css/y2ypssm.css');
    }

    private function _loadJs() {
        $this->context->controller->addJS($this->_path . 'assets/js/DateTimePicker/DateTimePicker.js');
        $this->context->controller->addJS($this->_path . 'assets/js/DateTimePicker/i18n/DateTimePicker-i18n-fr.js');
        $this->context->controller->addJS($this->_path . 'assets/js/y2ypssm.js');
    }
    
    protected function getDaysOfWeek() {
        return array(
            0 => $this->l('Sunday'),
            1 => $this->l('Monday'),
            2 => $this->l('Tuesday'),
            3 => $this->l('Wednesday'),
            4 => $this->l('Thursday'),
            5 => $this->l('Friday'),
            6 => $this->l('Saturday')
        );
    }
    
    protected function getConfigValues() {
        $values = array();
        $i = count($this->_fieldsList);
        foreach ($this->_fieldsList as $key => $type) {
            //handle serialized data
            if ($i <= 5) {
                $values[$key] = unserialize(Configuration::get($key, ''));
            } else {
                $values[$key] = Configuration::get($key, '');
            }

            $i--;
        }
        //die(var_dump($values));
        return $values;
    }

}
