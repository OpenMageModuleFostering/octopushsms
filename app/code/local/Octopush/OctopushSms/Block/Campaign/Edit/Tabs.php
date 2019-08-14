<?php

  class Octopush_OctopushSms_Block_Campaign_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
  {
     public function __construct()
     {
          parent::__construct();
          $this->setId('test_tabs');
          $this->setDestElementId('edit_form');
          $this->setTitle('Information sur le contact');
      }
      protected function _beforeToHtml()
      {
          $this->addTab('form_section', array(
                   'label' => 'Contact Information',
                   'title' => 'Contact Information',
                   'content' => $this->getLayout()
     ->createBlock('octopushsms/campaign_edit_tab_form')
     ->toHtml()
         ));
         return parent::_beforeToHtml();
    }
}
