<?php

/**
 * Recipient of octopushsms module
 */
class Octopush_OctopushSms_Model_Recipient extends Mage_Core_Model_Abstract {

    var $balance = 0;

    public function _construct() {
        parent::_construct();
        $this->_init('octopushsms/recipient');
    }

    public function validate() {
        $errors = array();
        //$helper = Mage::helper('octopushsms');
        if (!Zend_Validate::is($this->getData('phone'), 'NotEmpty') || !preg_match('/^\+[0-9]{6,16}$/', $this->getData('phone'))) {
            $errors[] = __('The phone number is wrong');
        }
        
        if (empty($errors) || $this->getShouldIgnoreValidation()) {
            return true;
        }
        return $errors;
    }

}
