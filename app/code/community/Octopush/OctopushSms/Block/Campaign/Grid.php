<?php

class Octopush_OctopushSms_Block_Campaign_Grid extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'campaign_controller';
        $this->_blockGroup = 'octopushsms';
        //name of the button to add a campaign
        if ($this->getRequest()->getActionName() == 'history') {
            $this->_headerText = __('Campaigns history');            
        } else {
            $this->_headerText = __('Campaigns that have not yet been sent');            
        }
        $this->_addButtonLabel = __('Create a new campaign / Send a new SMS');
        parent::__construct();
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

}
