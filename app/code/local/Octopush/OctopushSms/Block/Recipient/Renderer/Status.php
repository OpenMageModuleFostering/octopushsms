<?php

class Octopush_OctopushSms_Block_Recipient_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        return Mage::helper('octopushsms/API')->get_error_SMS($value);        
    }

}
