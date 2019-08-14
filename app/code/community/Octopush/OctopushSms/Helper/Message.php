<?php

class Octopush_OctopushSms_Helper_Message extends Mage_Core_Helper_Abstract {

    /**
     * Admin messages with their title
     */
    public $admin_config;
    public $customer_config;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @var      string    $octopush_sms       The name of this plugin.
     * @var      string    $version    The version of this plugin.
     */
    public function __construct() {
//initialisation of possible admin message
        $this->admin_config = array(
            'action_create_account' => __('Create an account'), //don't suppress                
            'action_send_message' => __('Send Message'),
            'action_validate_order' => __('Validate Order'),
            //'action_order_return' => __('Order return'),
            'action_update_quantity' => __('Stock notification'),
            'action_admin_alert' => __('Admin alert'),
            'action_daily_report' => __('Daily report', 'octopush-sms')
        );

        $this->customer_config = array(
            'action_create_account' => __('Create an account'),
            'action_password_renew' => __('Send SMS when customer has lost his password'),
            'action_customer_alert' => __('Send SMS when product is available'),
            'action_send_message' => __('Send Message'),
            'action_validate_order' => __('Validate Order'),
            'action_admin_orders_tracking_number_update' => __('Update tracking number order'),
            'action_order_status_update' => __('Status update', 'octopush-sms'));
    }

    /**
     * Get the key of a "hook".
     * 
     * @param type $hookId
     * @param type $b_admin
     * @param type $params
     * @return type
     */
    public function _get_hook_key($message) {
        return $message->getData('id_message') . '[message]';
    }

    /**
     * Return the key of isvalid for this "hook".
     * 
     * @param type $hookId
     * @param type $b_admin
     * @param type $params
     * @return type
     */
    public function _get_isactive_hook_key($message) {
        return $message->getData('id_message') . '[isactive]';
    }

    /**
     * Get values for specific hook: action_creat_account
     * This function returns the values to be replace in the sms send for this hook and the recipients list.
     * @param type $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
     * @param type $b_admin il this is for admin or for customer
     * @return array values to be replaced in the sms send.
     */
    private function _get_action_create_account_values($bSimu = false, $b_admin = false, $params = null) {
        if ($bSimu) {
            $test_values = array(
                '{firstname}' => 'John',
                '{lastname}' => 'Doe'
            );
            $values = array_merge($test_values, self::_getBaseValues());
        } else {
            $api = Mage::helper('octopushsms/API');
//get customer id
            $customer_id = $params['customer_id'];
            $helperCustomer = Mage::Helper('octopushsms/customer');
            $customer = $helperCustomer->find_customer_id($customer_id)->getFirstItem();
//set sender phone and recipient
            $api->_set_phone($customer->getData('billing_telephone'), $customer->getData('billing_country_id'), $b_admin);

            $store_id = $customer->getStoreID();
            $store = Mage::getModel('core/store')->load($store_id);
            $values = array(
                '{firstname}' => $customer->getData('firstname'),
                '{lastname}' => $customer->getData('lastname'),
                '{shopname}' => $store->getName(),
                '{shopurl}' => $store->getUrl(),
            );
        }
        Mage::log('action_create_account - _get_action_create_account_values ' . print_r($values, true), Zend_Log::DEBUG);
        return array_merge($values, self::_getBaseValues());
    }

    public function _get_action_admin_orders_tracking_number_update_values($bSimu = false, $b_admin = false, $params = null) {
        if ($bSimu) {
            $currency = Mage::app()->getDefaultStoreView()->getDefaultCurrencyCode();
            $values = array(
                '{firstname}' => 'John',
                '{lastname}' => 'Doe',
                '{order_id}' => '000001',
                '{shipping_number}' => 'ABC001',
                '{payment}' => 'Paypal',
                '{total_paid}' => '100',
                '{currency}' => $currency
            );
        } else {
            $track = $params['track'];
            if ($track) {
                //load order
                $order_id = $track->getData('order_id');
                $order = Mage::getModel('sales/order')->load($order_id);

                $billing = $order->getBillingAddress();
                $this->_recipients = null;
                $this->_phone = null;
                //the send of this sms is optionnal. Verify that you can send it.
                if (!$this->can_send_optional_sms($order_id, $b_admin)) {
                    return null;
                }
                $api = Mage::helper('octopushsms/API');
                //set sender phone and recipient
                $api->_set_phone($billing->getData('telephone'), $billing->getData('country_id'), $b_admin);
                //get payment method
                $payment_method = $order->getPayment()->getMethodInstance()->getTitle();
                $values = array(
                    '{firstname}' => $billing->getData('firstname'),
                    '{lastname}' => $billing->getData('lastname'),
                    '{order_id}' => $order_id,
                    '{order_state}' => $order->getData('status'),
                    '{payment}' => $payment_method,
                    '{total_paid}' => $order->getGrandTotal(),
                    '{currency}' => $order->getBaseCurrencyCode(),
                    '{shipping_number}' => $track->getData('track_number'),
                    '{shipping_title}' => $track->getData('track_title'),
                    '{shipping_carrier_code}' => $track->getData('carrier_code'),
                );
            }
        }
        return array_merge($values, self::_getBaseValues());
    }

    /**
     * Get values for specific hook: action_admin_alert
     * This function returns the values to be replace in the sms send for this hook and the recipients list.
     * @param boolean $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
     * @param boolean $b_admin
     * @return array if not null, return values to be replace in the sms text otherwise if null no value is set and no message have to be send
     */
    public function _get_action_admin_alert_values($bSimu = false, $b_admin = false, $params = null) {
        Mage::log('octopush-sms - _get_action_admin_alert_values - BEGIN', Zend_Log::DEBUG);
        if ($bSimu) {
            $values = array(
                '{balance}' => number_format('10', 3, ',', ' '),
            );
        } else {
            $api = Mage::helper('octopushsms/API');
            $this->_phone = null;
//only for admin sms
            if (!$b_admin) {
                return null;
            }
            $api->_set_phone(null, null, true);
            $values = array(
                '{balance}' => number_format($params['balance'], 3, ',', ' '),
            );
        }
        return array_merge($values, self::_getBaseValues());
    }

    public function _get_action_customer_alert_values($bSimu = false, $b_admin = false, $params = array()) {
        if ($bSimu) {
            $valuesPart = array(
                '{firstname}' => 'John',
                '{lastname}' => 'Doe',
                '{product}' => 'Ipod Nano',
            );
            $values = array_merge($valuesPart, self::_getBaseValues());
        } else {
            if ($b_admin) {
                $api->_set_phone(null, null, false);
                return null;
            }
            $customer = $params['customer'];
            $product = $params['product'];
            $address=Mage::getModel('customer/address')->load($customer->getDefaultBilling());
            $api = Mage::helper('octopushsms/API');
            //set sender phone and recipient
            Mage::log("la01", Zend_Log::DEBUG, "octopushsms.log");
            $api->_set_phone($address->getTelephone(), $address->getCountryId(), false);
            
Mage::log("la02", Zend_Log::DEBUG, "octopushsms.log");
            
            $store_id = $customer->getStoreID();
            $store = Mage::getModel('core/store')->load($store_id);
            Mage::log("la03", Zend_Log::DEBUG, "octopushsms.log");
            
            $values = array(
                '{firstname}' => $address->getData('firstname'),
                '{lastname}' => $address->getData('lastname'),
                '{shopname}' => $store->getName(),
                '{shopurl}' => $store->getUrl(),
                '{product}' => $product->getName(),
            );
            Mage::log("la04", Zend_Log::DEBUG, "octopushsms.log");            
        }
        Mage::log("values:".print_r($values,true), Zend_Log::DEBUG, "octopushsms.log");

        return $values;
    }

    function _get_action_order_status_update_values($bSimu = false, $b_admin = false, $params = array()) {
        $currency = Mage::app()->getDefaultStoreView()->getDefaultCurrencyCode();
        if ($bSimu) {
            $values = array(
                '{firstname}' => 'John',
                '{lastname}' => 'Doe',
                '{order_id}' => '000001',
                '{order_state}' => 'xxx',
                '{total_paid}' => '100',
                '{currency}' => $currency
            );
        } else {
            if ($b_admin) {
//not for admin
                return null;
            }
            $order = $params['order'];
            if ($order) {
                $billing = $order->getBillingAddress();
                $this->_recipients = null;
                $this->_phone = null;
//the send of this sms is optionnal. Verify that you can send it.
                if (!$this->can_send_optional_sms($order->getId(), $b_admin)) {
                    return null;
                }
                $api = Mage::helper('octopushsms/API');

//set sender phone and recipient
                $api->_set_phone($billing->getData('telephone'), $billing->getData('country_id'), $b_admin);
//get payment method
                $payment_method = $order->getPayment()->getMethodInstance()->getTitle();
                $values = array(
                    '{firstname}' => $billing->getData('firstname'),
                    '{lastname}' => $billing->getData('lastname'),
                    '{order_id}' => $order->getId(),
                    '{order_state}' => $order->getData('status'),
                    '{payment}' => $payment_method,
                    '{total_paid}' => $order->getGrandTotal(),
                    '{currency}' => $order->getBaseCurrencyCode(),
                );
            } else {
                return null;
            }
        }
        return array_merge($values, self::_getBaseValues());
    }

    /**
     * Get values for specific hook: action_validate_order
     * This function returns the values to be replace in the sms send for this hook and the recipients list.
     * @param type $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
     * @param type $b_admin
     * @param type $params
     * @return type
     */
    public function _get_action_validate_order_values($bSimu = false, $b_admin = false, $params = null) {
        $currency = Mage::app()->getDefaultStoreView()->getDefaultCurrencyCode();
        if ($bSimu) {
            $values = array(
                '{firstname}' => 'John',
                '{lastname}' => 'Doe',
                '{order_id}' => '000001',
                '{payment}' => 'Paypal',
                '{total_paid}' => '100',
                '{currency}' => $currency,
            );
        } else {
//init values
            $order_id = $params['order_id'];
            $this->_recipients = null;
            $this->_phone = null;
//the send of this sms is optionnal. Verify that you can send it.
            if (!$this->can_send_optional_sms($params['order_id'], $b_admin)) {
                return null;
            }
            $api = Mage::helper('octopushsms/API');

            $order = Mage::getModel('sales/order')->load($order_id);
            $address = $order->getBillingAddress();
            $custName = $address->getFirstname();
            //set sender phone and recipient
            $api->_set_phone($address->getTelephone(), $address->getCountryId(), $b_admin);
            //get payment method
            $payment_method = $order->getPayment()->getMethodInstance()->getTitle();
            $values = array(
                '{firstname}' => $custName,
                '{lastname}' => $order->getData('customer_lastname'),
                '{order_id}' => $params['order_id'],
                '{payment}' => $payment_method,
                '{total_paid}' => $order->getGrandTotal(),
                '{currency}' => $order->getBaseCurrencyCode(),
            );
        }
        return array_merge($values, self::_getBaseValues());
    }

    /**
     * Get values for specific hook: action_password_renew
     * This function returns the values to be replace in the sms send for this hook and the recipients list.
     * @param type $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
     * @param type $b_admin
     * @param type $params
     * @return array values to replace in sms text
     */
    public function _get_action_password_renew_values($bSimu = false, $b_admin = false, $params = array()) {
        if ($bSimu) {
            $valuesSimu = array(
                '{firstname}' => 'John',
                '{lastname}' => 'Doe',
                    //'{password}' => 'YourNewPass',
            );
            $values = array_merge($valuesSimu, self::_getBaseValues());
        } else {
            try {
                $customer_id = $params['customer_id'];
                $api = Mage::helper('octopushsms/API');
                $helperCustomer = Mage::Helper('octopushsms/customer');
                $customer = $helperCustomer->find_customer_id($customer_id);
                //set sender phone and recipient
                $api->_set_phone($customer->getData('billing_telephone'), $customer->getData('billing_country_id'), $b_admin);

                $store_id = $customer->getStoreID();
                $store = Mage::getModel('core/store')->load($store_id);
                $values = array(
                    '{firstname}' => $customer->getData('firstname'),
                    '{lastname}' => $customer->getData('lastname'),
                    '{shopname}' => $store->getName(),
                    '{shopurl}' => $store->getUrl(),
                );
            } catch (Exception $e) {
                Mage::log("Exception : " . $e->getMessage(), Zend_Log::ERR);
                return null;
            }
        }
        return $values;
    }

//action_update_quantity
    public function _get_action_update_quantity_values($bSimu = false, $b_admin = false, $params = array()) {
        if ($bSimu) {
            $values = array(
                '{product_id}' => '000001', //id
                '{product_ref}' => 'REF-001', //sku
                '{product_name}' => 'Ipod Nano', //name
                '{quantity}' => '2'
            );
        } else {
            $api = Mage::helper('octopushsms/API');
            $product = $params['product'];

            $api->_set_phone(null, null, $b_admin);

            $values = array(
                '{product_id}' => $product->getId(),
                '{product_ref}' => $product->getSku(),
                '{product_name}' => $product->getName(),
                '{quantity}' => intval(Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getProductId())->getQty()),
            );
        }
        return array_merge($values, self::_getBaseValues());
    }

    private function _get_action_send_message_values($bSimu = false, $b_admin = false, $params = array()) {
        if ($bSimu) {
            $values = array(
                '{contact_name}' => 'webmaster',
                '{contact_mail}' => 'webmaster@woocommerce.com',
                '{from}' => 'johndoe@gmail.com',
                '{message}' => 'This is a message'
            );
        } else {
            $api = Mage::helper('octopushsms/API');
            $comment = $params['comment'];
            if ($b_admin) {
                $api->_set_phone(null, null, $b_admin);
            } else {
                //otherwise send to customer
                if (isset($comment['telephone'])) {
                    $api->_set_phone($comment['telephone'], null, false);
                }
            }

            $from_email = Mage::getStoreConfig('trans_email/ident_general/email'); //fetch sender email Admin
            $from_name = Mage::getStoreConfig('trans_email/ident_general/name'); //fetch sender name Admin

            $values = array(
                '{contact_name}' => $from_name,
                '{contact_mail}' => $from_email,
                '{from}' => $comment['name'],
                '{message}' => $comment['comment'],
            );
        }
        return array_merge($values, self::_getBaseValues());
    }

    /**
     * Get values for specific hook: action_daily_report
     * This function returns the values to be replace in the sms send for this hook and the recipients list.
     * @param boolean $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
     * @param boolean $b_admin
     * @return array if not null, return values to be replace in the sms text otherwise if null no value is set and no message have to be send
     */
    function _get_action_daily_report_values($bSimu = false, $b_admin = false, $params = array()) {
        $currency = Mage::app()->getDefaultStoreView()->getDefaultCurrencyCode();
        if ($bSimu) {
            $values = array(
                '{date}' => date('Y-m-d'),
                '{subs}' => '5', //subscription
                '{visitors}' => '42', //visitor
//'{visits}' => '70',
                '{orders}' => '8', //order of day
                '{day_sales}' => "50 $currency", //sales of day
                '{month_sales}' => "1000 $currency", //sales of month
            );
        } else {
            $api = Mage::helper('octopushsms/API');
            $statisticHelper = Mage::helper('octopushsms/statistic');
            $values = array_merge(array('{date}' => date('Y-m-d')), $statisticHelper->getSales(), $statisticHelper->getVisits(), $statisticHelper->getSubscriptions());
            $api->_set_phone(null, null, $b_admin);
        }
        return array_merge($values, self::_getBaseValues());
    }

    /**
     * Get dummy value to generate message for test.
     * The method to call is generated from the hook (not good)
     * @param type $hook
     * @return type
     */
    public function get_sms_values_for_test($hook) {
        $values = array();
        $method = '_get_' . $hook . '_values';
        if (strstr($hook, 'action_order_status_update')) {
            $method = '_get_action_order_status_update_values';
        }
        if (method_exists(__CLASS__, $method)) {
            $values = self::$method(true);
        }
        return $values;
    }

    /**
     * Update option corresponding to the hook
     * @param type $hook
     * @param type $b_admin
     */
    public function update_message_option($hook, $b_admin = false) {
//if is active
        $hook_is_active = Octopush_Sms_Admin::get_instance()->_get_isactive_hook_key($hook, $b_admin);
        if (array_key_exists($hook_is_active, $_POST)) {
            $value = wc_clean($_POST[$hook_is_active]);
//save the option
            update_option($hook_is_active, (int) $value);
        } else {
            update_option($hook_is_active, 0);
        }
//message text
        $hook_key = Octopush_Sms_Admin::get_instance()->_get_hook_key($hook, $b_admin);
        if (array_key_exists($hook_key, $_POST)) {
            $value = stripslashes($_POST[$hook_key]);
//save the option
            update_option($hook_key, Octopush_Sms_API::get_instance()->replace_for_GSM7(trim($value)));
        }
//specific case of 'action_order_status_update'
        if ($hook == 'action_order_status_update') {
            global $wp_post_statuses;
            foreach ($wp_post_statuses as $key => $value) {
                if (strstr($key, 'wc-')) {
                    $this->update_message_option($hook . "_$key", $b_admin);
                }
            }
        }
    }

    public function is_active($message) {
//load message elementfrom database
        /* TODO remove$message = Mage::getResourceModel('octpushsms/message_collection')
          ->addFieldToFilter('hook_id', $bAdmin)
          ->addFieldToFilter('bAdmin', $bAdmin)
          ->getFirstItem(); */
        return ($message && $message->getData('is_active'));
    }

    public function get_message($hook_id, $bAdmin) {
//load message elementfrom database
        $collection = Mage::getResourceModel('octopushsms/message_collection')
                ->addFieldToFilter('hook_id', $hook_id)
                ->addFieldToFilter('bAdmin', $bAdmin);
        if ($collection->getSize() > 0) {
            return $collection->getFirstItem();
        }
//create in database
        $message = Mage::getModel('octopushsms/message');
        $message->setData('hook_id', $hook_id);
        $message->setData('bAdmin', $bAdmin);
        $message->setData('message', $this->get_sms_default_text($hook_id, $bAdmin));
        $message->save();

        return $message;
    }

    private function _getBaseValues() {
        $values = array(
            '{shopname}' => Mage::app()->getStore()->getName(),
            '{shopurl}' => Mage::app()->getStore()->getUrl(),
        );
        return $values;
    }

    public function get_sms_default_text_from_message($message) {
        return $this->get_sms_default_text($message->getData('hook_id'), $message->getData('bAdmin'));
    }

    /**
     * Return the default text
     * @param type $hookId
     */
    public function get_sms_default_text($hook_key, $bAdmin) {
//$hook_key = $message->getData('hook_key');
//$bAdmin = $message->getData('bAdmin');
        $defaultMessage = "";
        $hookText = $hook_key;
        if ($bAdmin) {
            $hookText.="_admin";
        }
        switch ($hookText) {
            case 'action_create_account_admin':
                $defaultMessage = __("{firstname} {lastname} has just registered on {shopname}", 'octopush-sms');
                break;
            case 'action_send_message_admin' :
                $defaultMessage = __("{from} has sent a message to{contact_name} ({contact_mail}) : {message}", 'octopush-sms');
                break;
            case 'action_validate_order_admin' :
                $defaultMessage = __("New order from {firstname} {lastname}, id: {order_id}, paiement: {payment}, total: {total_paid} {currency}.", 'octopush-sms');
                break;
            case 'action_order_return_admin' :
                $defaultMessage = __("Back order ({return_id}) done by the client {customer_id} about the order {order_id}. Reason : {message}", 'octopush-sms');
                break;
            case 'action_update_quantity_admin' :
                $defaultMessage = __("This item is almost out of order, id: {product_id}, réf: {product_ref}, name: {product_name}, quantity: {quantity}", 'octopush-sms');
                break;
            case 'action_admin_alert_admin' :
                $defaultMessage = __("Your SMS credit is almost empty. Your remaining balance is {balance} SMS available.", 'octopush-sms');
                break;
            case 'action_daily_report_admin' :
                $defaultMessage = __("date: {date}, inscriptions: {subs}, orders: {orders}, sales: {day_sales}, for the month of: {month_sales}", 'octopush-sms');
                break;
            case 'action_create_account' :
                $defaultMessage = __("{firstname} {lastname}, Welcome to {shopname} !", 'octopush-sms');
                break;
            case 'action_password_renew' :
                $defaultMessage = __("{firstname} {lastname}, you reset your password to access to {shopname} , {shopurl}", 'octopush-sms');
                break;
            case 'action_customer_alert' :
                $defaultMessage = __("{firstname} {lastname}, the item {product} is now available on {shopname} ({shopurl})", 'octopush-sms');
                break;
            case 'action_send_message' :
                $defaultMessage = __("Thank you for your message. We will answer it very shortly. {shopname}", 'octopush-sms');
                break;
            case 'action_validate_order' :
                $defaultMessage = __("{firstname} {lastname}, we do confirm your order {order_id}, of {total_paid} {currency}. Thank you. {shopname}", 'octopush-sms');
                break;
            case 'action_admin_orders_tracking_number_update' :
                $defaultMessage = __("{firstname} {lastname}, your order {order_id} was delivered. Your shipping number is {shipping_number}. {shopname}", 'octopush-sms');
                break;
            default:
//specific case for action_order_status_update where the hook key is action_order_status_update_[order_state] where [order_state] can take differents values
                if (strstr($hook_key, 'action_order_status_update')) {
                    $defaultMessage = __('{firstname} {lastname}, your order {order_id} on {shopname} has a new status : {order_state}', 'octopush-sms');
                } else {
                    $defaultMessage = __('Not defined', 'octopush-sms');
                    $defaultMessage .= $hookText;
                }
                break;
        }
        return $defaultMessage;
    }

    /**
     * Construct the sms values before send it.
     * The SMS values depend of the hook 
     * @param type $b_admin
     */
    public function _prepare_sms($hook_id, $params, $b_admin = false) {
        Mage::log("==========================|>  _prepare_sms hook_id:$hook_id , admin:$b_admin==================================================", Zend_Log::DEBUG, "octopushsms.log");

//defined the method name to call to set the recipient phone and the value to replave in the sms text
        $method = '_get_' . $hook_id . '_values';
        //Mage::log('_prepare_sms ' . $method . ' params: ' . print_r($params, true), Zend_Log::DEBUG, "octopushsms.log");

        $api = Mage::helper('octopushsms/API');

//if method to get the values corresponding to this hook exist we continue
//specific case of update status
        $hook_id_adapted = null;
        if ($hook_id == 'action_order_status_update') {
            $hook_id_adapted = $hook_id . "_" . $params['order']->getStatus();
        } else {
            $hook_id_adapted = $hook_id;
        }

        $message = $this->get_message($hook_id_adapted, $b_admin);
        if ($message->getId() <= 0 || !method_exists(__CLASS__, $method)) {
            Mage::log($hook_id . ' not exit or no method ' . $method . ' exists : no sms send', Zend_Log::INFO, "octopushsms.log");
            return false;
        }
        $api->_recipients = null;
        $api->_phone = null;
//this internal hook is active?
        $is_active = $message->getData('is_active');
        $text = $message->getData('message');
//TODO remove ? $locale = get_locale();
//if active and a text exists
        if ($is_active && $text) {
            Mage::log("=========la", Zend_Log::DEBUG, "octopushsms.log");

//get values to replace in sms text
            $values = $this->$method(false, $b_admin, $params);
//check if everything is valid for sending the sms (if $this->_phone is not set, nothing is send)
            Mage::log("=========lae", Zend_Log::DEBUG, "octopushsms.log");

            if (!$api->_is_everything_valid_for_sending()) {
                return false;
            }
            Mage::log("=========laee", Zend_Log::DEBUG, "octopushsms.log");

//if we can send the sms, we send it
            if (is_array($values)) {
                $text_to_send = str_replace(array_keys($values), array_values($values), $text);
                $api->_send_sms($text_to_send);
            }
        }
    }

    public function send($hook, $params) {
//send sms for the client (check if sending the sms is needed, create the mesage...)
        self::_prepare_sms($hook, $params);
//send sms for admin (check if sending the sms is needed, create the mesage...)
        self::_prepare_sms($hook, $params, true);
        if ($hook != 'action_admin_alert') {
            $this->ckeck_balance_for_admin_alert();
        }
    }

    public function can_send_optional_sms($order_id, $b_admin) {
        if ($b_admin) {
            return true;
        }
//test setting value
        $setting = Mage::getModel('octopushsms/setting')->load(1);
        if ($setting->getData('free_option')) {
            return true;
        }

//option is not free test if the product is in the order
        $product_id = $this->setting->getData('id_product');
        Mage::getModel('sales/order');
        $order = Mage::getModel('sales/order')->load($order_id);
        $orderItems = $order->getItemsCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('product_id', array('eq' => $product_id))
                ->load();
        return ($orderItems->getSize() > 0);
    }

    public function ckeck_balance_for_admin_alert() {
        $api = Mage::helper('octopushsms/API');
        $balance = $api->get_balance();
        $setting = Mage::getModel("octopushsms/setting")->load('1');
        $alert_level = $setting->getAdminAlert();
        if ($alert_level > 0 && (float) $balance < $alert_level && $setting->getAdminAlertSent() == 0) {
            $messageHelper = Mage::helper('octopushsms/message');
            $params = array('balance' => $balance);
            $messageHelper->send('action_admin_alert', $params);
            $setting->setAdminAlertSent(1);
            $setting->save();
        }
    }

}
