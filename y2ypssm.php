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
 *  @author    Partner IT Group <support@partner-it-group.com>
 *  @copyright 2016 Partner IT Group
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}


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

    public static $validPostCodes = array(
        '75', '92', '93', '94'
    );
    
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
        
        if (self::isInstalled($this->name)) {
            if(!extension_loaded("curl")){
                $this->uninstall();
                return;
            }
            
            require_once(_PS_MODULE_DIR_ . "/y2ypssm/classes/Y2Y_API.php");
            $this->validApi = $this->_testApi();
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
            'Y2YPSSM_INLINE_CALENDAR' => array('type' => 'checkbox', 'desc' => $this->l('Inline Calendar')),
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
        
        require_once(dirname(__FILE__) . '/sql/install.php');
      
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
        $lunchBegin = Tools::getValue('Y2YPSSM_LUNCH_TIME_BEGIN');
        $lunchEnd = Tools::getValue('Y2YPSSM_LUNCH_TIME_END');
        $closedDay = Tools::getValue('Y2YPSSM_CLOSED_DAY');
        $invalidDays = array();
        $daysOfWeek = $this->getDaysOfWeek();
        
        foreach($daysOfWeek as $i => $day) {
            /*if(!empty($openingHours[$i]) && !empty($closingHours[$i])){
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
            }*/
            if(!empty($closedDay[$i])){
                $openingHours[$i] = '';
                $closingHours[$i] = '';
                $lunchBegin[$i] = '';
                $lunchEnd[$i] = '';
            }else if(!empty($openingHours[$i]) && !empty($closingHours[$i])){
                $closedDay[$i] = '';
            }else if(!empty($openingHours) || !empty($closingHours[$i])){
                $openingHours[$i] = '';
                $closingHours[$i] = '';
                $lunchBegin[$i] = '';
                $lunchEnd[$i] = '';
                $closedDay[$i] = 'yes';
            }
            if(empty($closedDay[$i]) && strtotime($openingHours[$i]) >= strtotime($closingHours[$i])){
                $invalidDays[] = $day;
            }
            
            
        }
        $_POST['Y2YPSSM_OPENING_HOURS'] = $openingHours;
        $_POST['Y2YPSSM_CLOSING_HOURS'] = $closingHours;
        $_POST['Y2YPSSM_LUNCH_TIME_BEGIN'] = $lunchBegin;
        $_POST['Y2YPSSM_LUNCH_TIME_END'] = $lunchEnd;
        $_POST['Y2YPSSM_CLOSED_DAY'] = $closedDay;
        
        if(count($invalidDays) > 0){
            $this->_addErrorMessage(sprintf($this->l('Invalid hours in the following day(s): %s'),implode(', ', $invalidDays)));
            
        }
        
        return (count($this->_notices['error']) == 0);
    }
    
    protected function saveAdminFields() {
        
        $i='';
        foreach ($this->_fieldsList as $key => $type) {
            $value = Tools::getValue($key);
            $i[]=$value;
            if (is_array($value)) {
                $value = serialize($value);
                Configuration::updateValue($key, $value);
            }else{
                Configuration::updateValue($key, $value);
            }
        }
        //die(var_dump($i));
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
        
        $timeout = ($timeout <= 0) ? 0 : $timeout;
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
        $address = new Address($params->id_address_delivery);
        $country = new Country($address->id_country);
        
        if($country->iso_code == 'FR'){
            if(!self::isValidPostCode($address->postcode)){
                return false;
            }
            return (float)5;
        }
        return false;
        
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
                <div id="y2y_module">
                    <input type="hidden" class="form-control" id="y2y_hidden_date" name="y2y_hidden_date">
                    <input type="hidden" class="form-control" id="y2y_delivery_date" name="y2y_delivery_date">
                    <input type="hidden" class="form-control" id="y2y_hidden_time" name="y2y_hidden_time">
                    <div class="y2ypssm-datepicker-holder"></div>
                    <?php
                    $result = $this->getConfigValues();
                    $inline_cal=$result['Y2YPSSM_INLINE_CALENDAR'];
                    if($inline_cal==='yes' || !empty($inline_cal))
                    {
                    ?>
                        <div id="calendar" class="inline-calendar" style="float:left;"></div>
                        <div class="y2y_time" style="max-height:100px; min-height:200px">
                            <div class="radio-buttons"></div>
                        </div>
                    <?php
                    }else{
                    ?>
                        <div id="modal-y2y" class="col-1" style="display:none">
                            <div id="calendar" class="col-1" style="float: left;"></div>
                                <div class="y2y_time" style=" float: right;">
                                    <div class="radio-buttons"></div>
                                </div>
                                <div style="width:100%;display:table; padding:4px;">
                                <button class="button btn btn-default button-small" onclick="select_time()">
                                    <span>Choisir</span>
                                </button>
                            </div>
                        </div>
                        <button class="call-modal button btn btn-default button-small"><span>Choose date</span></button>
                        <?php
                    }
                    $closed_days = '';

                    if(!empty($result['Y2YPSSM_CLOSED_DAY']))
                    {
                        
                        for($i=0;$i<sizeof($result['Y2YPSSM_CLOSED_DAY']);$i++){
                            if($result['Y2YPSSM_CLOSED_DAY'][$i]=='yes')
                            {
                                $closed_days[] = $i;
                            }
                        }
                    }

                    $today = getdate();
                    $today = $today['year'].'-'.$today['mon'].'-'.$today['mday'].' '.$today['hours'].':'.$today['minutes'].':'.$today['seconds'];
                    $timeout = $result['Y2YPSSM_TIMEOUT'];
                    ?>
                    <div id="y2y-sentence"></div>
                </div>
            </div>
            <script type="text/javascript">
                var today = '<?php echo $today; ?>';
                var timeout = <?php echo $timeout; ?>;
                var openning_hours = [<?php echo '"'.implode('","', $result['Y2YPSSM_OPENING_HOURS']).'"' ?>];
                var closing_hours = [<?php echo '"'.implode('","', $result['Y2YPSSM_CLOSING_HOURS']).'"' ?>];
                var today_week = moment(today).format('e');
                var now = moment(today);
                var nowtimeout = now.add(timeout+1,'hour');
                if(moment(closing_hours[today_week],'HH[h]mm')>moment(nowtimeout,'HH[h]mm')){
                    minDate = 0;
                }else{
                    minDate=1;
                    week = <?php echo json_encode($result['Y2YPSSM_CLOSED_DAY']) ?>;
                    var week = $.map(week, function(el) { return el });
                    tommorrow = moment(today).add(1,'day').format('e');
                    nch = $.merge(week.slice(tommorrow), week.slice(0,tommorrow));
                    $.each(nch , function(i, val) {
                        if(val==='yes'){
                            minDate = i;
                            return false;
                        }
                    });
                }
                
                
                cd = [<?php echo implode(',', $closed_days) ?>];
                var cal = $('#y2y_module #calendar').datepicker({
                    minDate: minDate,
                    altField: "#y2y_module #y2y_hidden_date",
                    altFormat: "yy-mm-dd",
                    setDate: minDate,
                    gotoCurrent: true,
                    buttonText: "Select date",
                    beforeShowDay: function(date) {
                        var day = date.getDay();
                        if ($.inArray(day, cd) === -1) {
                          return [true, "","Available"];
                        } else {
                          return [false,"","unAvailable"];
                        }
                    },
                    onSelect: function(date) {
                        $("#y2y_module #y2y_hidden_date").trigger("change");
                    }
                });
                $('#y2y_module  .call-modal').on('click', function(event) {
                    event.preventDefault();
                    $("#y2y_module  #y2y_hidden_date").trigger("change");
                    $('#modal-y2y').dialog({
                        width: '45%',
                        close: function(event, ui){
                            select_time();
                        }
                    });
                });
                
                
                $("#y2y_module #y2y_hidden_date").change(function() {
                    val = $("#y2y_module #y2y_hidden_date").val();
                    choosen_day = moment(val).day();
                    var times = [];
                    lunch_beg = [<?php echo '"'.implode('","', $result['Y2YPSSM_LUNCH_TIME_BEGIN']).'"' ?>];
                    lunch_end = [<?php echo '"'.implode('","', $result['Y2YPSSM_LUNCH_TIME_END']).'"' ?>];
                    choosen_day = moment(val).day();
                    beg_hour = openning_hours[choosen_day];
                    end_hour = closing_hours[choosen_day];
                    lunch_beg = lunch_beg[choosen_day];
                    lunch_end = lunch_end[choosen_day];
                    var now = moment(today).format('HH[h]mm');
                    now_m = moment(now,'HH[h]mm').format('mm');
                    while(now_m!=='00' && now_m!=='30'){
                        now = moment(now,'HH[h]mm').add(1,'minute');
                        now_m = moment(now,'HH[h]mm').format('mm');
                    }
                    if(moment(today).format('YYYY-MM-DD') === val){
                        //today
                        if(lunch_beg!=='' || lunch_end!=='')
                        {
                            //morning
                            add = timeout+1;
                            if(moment(now,'HH[h]mm') < moment(beg_hour,'HH[h]mm')){
                                now = beg_hour;
                            }
                            while(moment(now,'HH[h]mm').add(timeout+1,'hour') <= moment(lunch_beg,'HH[h]mm').add(add,'hour')){
                                now = moment(now,'HH[h]mm').add(add,'hour');
                                times.push(moment(now,'HH[h]mm').format('HH[h]mm')+" - "+moment(now,'HH[h]mm').add(1,'hour').format('HH[h]mm'));
                                add = 1;
                            }

                            //afternnoon
                            add = timeout+1;
                            if(moment(now,'HH[h]mm') < moment(lunch_end,'HH[h]mm')){
                                now = lunch_end;
                            }
                            while(moment(now,'HH[h]mm').add(timeout+1,'hour') <= moment(end_hour,'HH[h]mm').add(add,'hour')){
                                now = moment(now,'HH[h]mm').add(add,'hour');
                                times.push(moment(now,'HH[h]mm').format('HH[h]mm')+" - "+moment(now,'HH[h]mm').add(1,'hour').format('HH[h]mm'));
                                add = 1;
                            }
                        }
                        else
                        {
                            add = timeout+1;
                            if(moment(now,'HH[h]mm') < moment(beg_hour,'HH[h]mm')){
                                now = beg_hour;
                            }
                            while(moment(now,'HH[h]mm').add(timeout+1,'hour') <= moment(end_hour,'HH[h]mm').add(add,'hour')){
                                now = moment(now,'HH[h]mm').add(add,'hour');
                                times.push(moment(now,'HH[h]mm').format('HH[h]mm')+" - "+moment(now,'HH[h]mm').add(1,'hour').format('HH[h]mm'));
                                add = 1;
                            }
                        }
                    }
                    else
                    {
                        //not today
                        if(lunch_beg!=='' || lunch_end!=='')
                        {
                            //morning
                            while(moment(beg_hour,'HH[h]mm').add(1,'hour') < moment(lunch_beg,'HH[h]mm').add(1,'hour')){
                                beg_hour = moment(beg_hour,'HH[h]mm').add(1,'hour');
                                times.push(moment(beg_hour,'HH[h]mm').format('HH[h]mm')+" - "+moment(beg_hour,'HH[h]mm').add(1,'hour').format('HH[h]mm'));
                            }

                            //afeternoon
                            while(moment(lunch_end,'HH[h]mm').add(1,'hour') < moment(end_hour,'HH[h]mm').add(1,'hour')){
                                lunch_end = moment(lunch_end,'HH[h]mm').add(1,'hour');
                                times.push(moment(lunch_end,'HH[h]mm').format('HH[h]mm')+" - "+moment(lunch_end,'HH[h]mm').add(1,'hour').format('HH[h]mm'));
                            }
                        }
                        else
                        {
                            //morning
                            while(moment(beg_hour,'HH[h]mm').add(1,'hour') < moment(end_hour,'HH[h]mm').add(1,'hour')){
                                beg_hour = moment(beg_hour,'HH[h]mm').add(1,'hour');
                                times.push(moment(beg_hour,'HH[h]mm').format('HH[h]mm')+" - "+moment(beg_hour,'HH[h]mm').add(1,'hour').format('HH[h]mm'));
                            }
                        }
                    }

                    rawtime = $("#y2y_module input[id=y2y_hidden_time]").val();
                    var radiobtns = '';
                    for (i = 0; i < times.length; i++){
                        span = times[i].split(' - ');
                        if(rawtime === (span[0]+'-'+span[1]) || i===0){
                            checked='checked="checked"';
                        }else{
                            checked='';
                        }
                        
                        
                        if(i === 0)
                        {
                            radiobtns = '<div style="float:left;margin-left:10px;">'+radiobtns;
                        }
                        if(i % 5 === 0 && i!==0)
                        {
                            radiobtns = radiobtns+'<div style="float:left;">';
                        }
                        radiobtns += '<div class="buttonsetv" onchange="javascript:generate_sentence();" name="radio-group-'+i+'">'
                                            +'<input type="radio" id="time'+i+'" name="time" '+checked+' value="'+span[0]+'-'+span[1]+'">'+'\
                                            <label for="time'+i+'">'+times[i]+'</label>'
                                    +'</div>';
                        if(i!==0)
                        {
                            if( (i === times.length-1))
                            {
                                radiobtns+='</div>';
                            }
                            console.debug(i+1%5);
                            if((i+1)%5===0)
                            {
                                radiobtns+='</div>';
                            }
                        }
                    }
                    if(radiobtns===''){
                        radiobtns = '<p style="algin-text:center">Il n\'y a pas de livraisons ce jour-là</p>';
                    }
                    
                    $("#y2y_module .radio-buttons").html(radiobtns);
                    $('#y2y_module .buttonsetv').buttonsetv();
                    generate_sentence();
                });
                
                
                function select_time()
                {
                    generate_sentence();
                    $("#modal-y2y").dialog('close');
                }


                function generate_sentence()
                {
                    time_sel = $('#y2y_module .y2y_time .radio-buttons input[name=time]:checked').val();
                    $('#y2y_module #y2y_hidden_time').val(time_sel);
                    val = $("#y2y_hidden_date").val();
                    choosen_date = val.split('-');
                    monthpos = choosen_date[1].replace(/^0+/, '');
                    choosen_day = choosen_date[2].replace(/^0+/, '');
                    time_sent = '';
                    rawtime = $("#y2y_module #y2y_hidden_time").val();
                    time = rawtime;
                    if(time!==''){
                        time = time.split('-');
                        time_sent = time[0].toString().replace('h',':')+":00";
                        time = "Veuillez vous rendre disponible de "+time[0]+" à "+time[1];
                    }
                    $("#y2y_module #y2y_delivery_date").val(val+" "+time_sent);
                    var months = [
                        "janvier",
                        "février",
                        "mars",
                        "avril",
                        "mai",
                        "juin",
                        "juillet",
                        "août",
                        "septembre",
                        "octobre",
                        "novembre",
                        "décembre",
                    ];
                    var week = [
                        "dimanche",
                        "lundi",
                        "mardi",
                        "mercredi",
                        "jeudi",
                        "vendredi",
                        "samedi",
                    ];

                    var year = choosen_date[0];
                    var dayofthemonth = choosen_day;
                    choosen_day = moment(val).day();
                    var dayoftheweek = week[choosen_day];
                    var month = months[monthpos-1];
                    $("#y2y_module #y2y-sentence").html("Vous avez choisi le "+dayoftheweek+" "+dayofthemonth+" "+month+" "+year+". "+time+" pour réceptionner votre colis auprès du livreur. Il vous demandera un code que vous allez recevoir par SMS dans quelques minutes.");
                }
                $("#y2y_module #y2y_hidden_date").trigger("change");
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
            $delivery_date = Tools::getValue('y2y_delivery_date','');
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
    
    public static function isValidPostCode($postcode){
        foreach (self::$validPostCodes as $frpostcode) {
            if (substr($postcode, 0, 2) == $frpostcode) {
                return true;
            }
        }
        
        return false;
    }
    
    /*****************************************/
    /********* BACK AND FRONT OFFICE *********/
    /*****************************************/
    private function _loadCss() {
        $this->context->controller->addCSS($this->_path . 'assets/css/DateTimePicker.css');
        $this->context->controller->addCSS($this->_path . 'assets/css/y2ypssm.css');
        $this->context->controller->addCSS($this->_path . 'assets/js/jquery-calendar/jquery-ui.min.css');
    }

    private function _loadJs() {
        $this->context->controller->addJS($this->_path . 'assets/js/y2ypssm.js');
        $this->context->controller->addJS($this->_path . 'assets/js/DateTimePicker/DateTimePicker.js');
        $this->context->controller->addJS($this->_path . 'assets/js/DateTimePicker/i18n/DateTimePicker-i18n-fr.js');
        $this->context->controller->addJS($this->_path . 'assets/js/moment-with-locales/moment.js');
        $this->context->controller->addJS($this->_path . 'assets/js/jquery-calendar/jquery-ui.js');
        $this->context->controller->addJS($this->_path . 'assets/js/jquery.verticalradio/jquery.verticalradio.js');
        
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
        return $values;
    }

}
