<?php


/**
 * Setting of octopushsms module
 */
class Octopush_OctopushSms_Model_Mysql4_Campaign extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('octopushsms/campaign', 'id_sendsms_campaign');
    }


}