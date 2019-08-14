<?php

/**
 * Campaign of octopushsms module
 */
class Octopush_OctopushSms_Model_Campaign extends Mage_Core_Model_Abstract {

    var $balance = 0;

    public function _construct() {
        parent::_construct();
        $this->_init('octopushsms/campaign');
    }

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave() {
        if (empty($this->getData('date_add'))) {
            $this->setDateAdd(
                    Mage::getSingleton('core/date')
                            ->gmtDate()
            );
        }
        if (empty($this->getData('date_send'))) {
            //if the send date is not set, we set the send date to now
            $this->setData('date_send', Mage::getModel('core/date')->date('Y-m-d H:m:s'));
        }
        if (empty($this->getData('ticket'))) {
            //if the send date is not set, we set the send date to now
            $this->setData('ticket', (string) time());
        }
        //$this->setData('date_add',Mage::getModel('core/date')->date('Y-m-d HH:mm:ss'));            
        $this->setDateUpd(Mage::getSingleton('core/date')->gmtDate());
        return parent::_beforeSave();
    }

    public function validate() {
        $errors = array();
        $helper = Mage::helper('octopushsms');
        if (empty($this->getData('title'))) {
            $errors[] = $helper->__('Please enter a title');
        }
        if (empty($this->getData('message'))) {
            $errors[] = $helper->__('Please enter a message');
        }
        if (empty($this->getData('date_send'))) {
            $errors[] = $helper->__('Please enter a valid send date');
        }
        if (empty($errors) || $this->getShouldIgnoreValidation()) {
            return true;
        }
        return $errors;
    }

}
