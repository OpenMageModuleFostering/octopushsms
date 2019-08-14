<?php

/**
 * Setting of octopushsms module
 */
class Octopush_OctopushSms_Model_Mysql4_Phoneprefix extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('octopushsms/phoneprefix', 'iso_code');
    }


}
