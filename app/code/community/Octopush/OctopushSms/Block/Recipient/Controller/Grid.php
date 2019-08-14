<?php

class Octopush_OctopushSms_Block_Recipient_Controller_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('recipientGrid');
        $this->setDefaultSort('id_sendsms_recipient');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/gridRecipient', array('_current' => true,'id'=>Mage::registry('campaign_data')->getId()));
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('octopushsms/recipient')->getCollection();
        if (Mage::registry('campaign_data') && Mage::registry('campaign_data')->getId()>0) {
            $collection->addFieldToFilter('id_sendsms_campaign', Mage::registry('campaign_data')->getId());
        } else {
            $collection->addFieldToFilter('id_sendsms_campaign', -1);
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('id_sendsms_recipient', array(
            'header' => __('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'id_sendsms_recipient',
        ));
        $this->addColumn('firstname', array(
            'header' => __('Firstname'),
            'align' => 'left',
            'index' => 'firstname',
        ));
        $this->addColumn('lastname', array(
            'header' => __('Lastname'),
            'align' => 'left',
            'index' => 'lastname',
        ));
        $this->addColumn('phone', array(
            'header' => __('Phone'),
            'align' => 'left',
            'index' => 'phone',
        ));
        $this->addColumn('iso_country', array(
            'header' => __('Country'),
            'align' => 'left',
            'index' => 'iso_country',
        ));
        if (Mage::registry('campaign_data')) {
            $_campaign = Mage::registry('campaign_data');
            if (intval($_campaign->getData('status')) == 0) {
                /* $this->addColumn('iso_country', array(
                  'header' => __('Country'),
                  'align' => 'left',
                  'index' => 'iso_country',
                  ));
                  $column_to_add = array('user_actions' => __('Actions', 'octopush-sms'));
                  $columns = array_merge($columns, $column_to_add); */
            } else if (intval($_campaign->getData('status')) >= 1) {
                $this->addColumn('price', array(
                    'header' => __('Price'),
                    'align' => 'left',
                    'index' => 'price',
                ));
                $this->addColumn('transmitted', array(
                    'header' => __('Transmited to Octopush'),
                    'align' => 'left',
                    'index' => 'transmitted',
                ));
                $values = Mage::helper('octopushsms/Campaign')->get_status_array();
                $this->addColumn('status', array(
                    'header' => __('Status / Error'),
                    'align' => 'left',
                    'index' => 'status',
                    //'type' => 'options',
                    //'options' => $values,
                    'renderer' => 'Octopush_OctopushSms_Block_Recipient_Renderer_Status',
                ));
            }
        }
        $this->addColumn('action', array(
            'header' => Mage::helper('octopushsms')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('octopushsms')->__('Delete'),
                    'url' => array('base' => '*/*/deleteRecipient'),//'$entity_id'
                    'field' => 'id',
                )),
            'filter' => false,
            'sortable' => false,
            //'index' => 'stores',
            'is_system' => true,
        ));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        //no edit possible
        //return $this->getUrl('*/*/editRecipient', array('id' => $row->getId()));
    }

}
