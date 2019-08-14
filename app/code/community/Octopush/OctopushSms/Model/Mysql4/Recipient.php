<?php


/**
 * Setting of octopushsms module
 */
class Octopush_OctopushSms_Model_Mysql4_Recipient extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('octopushsms/recipient', 'id_sendsms_recipient');
    }


}