<?php

class Octopush_OctopushSms_Block_Campaign_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        return Mage::helper('octopushsms/Campaign')->get_status($row->getData($this->getColumn()->getIndex()));
    }

}
