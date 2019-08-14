<?php
class Octopush_OctopushSms_Model_Mysql4_Test extends Mage_Core_Model_Mysql4_Abstract
{
     public function _construct()
     {
         $this->_init('octopushsms/test', 'id_octopush_test');
     }
}