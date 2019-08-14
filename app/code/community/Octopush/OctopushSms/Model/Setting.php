<?php

/**
 * Setting of octopushsms module
 */
class Octopush_OctopushSms_Model_Setting extends Mage_Core_Model_Abstract {

    var $balance = 0;

    public function _construct() {
        parent::_construct();
        $this->_init('octopushsms/setting');
    }

    public function validate() {
        $errors = array();
        $helper = Mage::helper('octopushsms');

        if (!Zend_Validate::is($this->getEmail(), 'NotEmpty') ||
                !Zend_Validate::is($this->getKey(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter your account information to login to www.octopush.com');
        } else if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $errors[] = $helper->__('The email you entered is not a valid email.');
        } else {
            $api = Mage::helper('octopushsms/API');
            $api->user_login = $this->getData('email');
            $api->api_key = $this->getData('key');
            $api->sms_sender = $this->getData('sender');
            $this->balance = $api->get_balance();
            if ($this->balance === false) {
                $errors[] = $helper->__('This account is not a valid account on www.octopush.com');
            } else if ($this->balance === '001') {
                $errors[] = $helper->__('This account is not a valid account on www.octopush.com');
                $error = $api->get_error_SMS('001');
                $errors[] = $helper->$error;
            }
        }

//sender
        if (!preg_match('/^[[:digit:]]{1,16}$/', $this->getSender()) && !preg_match('/^[[:alnum:]]{1,11}$/', $this->getSender())) {
            $errors[] = $helper->__('Please enter a valid sender name : 11 chars max (letters + digits)');
        }


        //Admin phone
        if (!Zend_Validate::is($this->getAdminPhone(), 'NotEmpty') || !preg_match('/^\+[0-9]{6,16}$/', $this->getAdminPhone())) {
            $errors[] = $helper->__('Please enter a valid admin mobile number');
        }

        //Admin alert
        if (!Zend_Validate::is($this->getAdminAlert(), 'Int')) {
            $errors[] = $helper->__('Please enter a valid integer value for alert');
        }

        //id_product
        //check only is not free_option
        if ($this->getFreeOption()==0) {
            $products=Mage::getResourceModel('catalog/product_collection')->addAttributeToFilter('entity_id',$this->getIdProduct());
            if ($products->count()==0) {
                $errors[] = $helper->__('The product with entity_id %s does not exist',$this->getIdProduct());
            }
        }
        /*
        //free option
        if (isset($freeoption)) {
            if (!is_numeric($product_id)) {
                WC_Admin_Settings::add_error(__('Please enter a valid integer value for product_id', 'octopush-sms'));
            } else {
                //TODO verify product exist
                update_option('octopush_sms_option_id_product', $product_id);
            }
            update_option('octopush_sms_freeoption', $freeoption);
        }
        }
        $this->balance = Octopush_Sms_API::get_instance()->get_balance();
        $this->bAuth = $this->balance === false || $this->balance === '001' ? false : true;
        } */

        if (empty($errors) || $this->getShouldIgnoreValidation()) {
            return true;
        }
        return $errors;
    }

}
