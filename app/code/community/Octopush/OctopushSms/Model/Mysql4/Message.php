<?php

/**
 * Setting of octopushsms module
 */
class Octopush_OctopushSms_Model_Mysql4_Message extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('octopushsms/message', 'id_message');
    }

}

