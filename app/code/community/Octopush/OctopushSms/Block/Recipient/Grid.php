<?php

class Octopush_OctopushSms_Block_Recipient_Grid extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'recipient_controller';
        $this->_blockGroup = 'octopushsms';
        $this->_headerText = __('Recipients');
        parent::__construct();
        $this->_removeButton('add');
        $this->setUseAjax(true);
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/gridRecipient', array('_current' => true));
    }

}
