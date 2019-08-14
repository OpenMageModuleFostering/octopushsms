<?php

class Octopush_OctopushSms_Block_Campaign_Controller_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('campaignGrid');
        $this->setDefaultSort('id_sendsms_campaign');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('octopushsms/campaign')->getCollection();
        if ($this->getRequest()->getActionName() == 'history') {
            $collection->addFieldToFilter('status', array('in' => array(3, 4, 5)));
        } else {
            $collection->addFieldToFilter('status', array('nin' => array(3, 4, 5)));
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('id_sendsms_campaign', array(
            'header' => 'ID',
            'align' => 'right',
            'width' => '50px',
            'index' => 'id_sendsms_campaign',
        ));
        $this->addColumn('ticket', array(
            'header' => __('Ticket'),
            'align' => 'left',
            'index' => 'ticket',
        ));
        $this->addColumn('title', array(
            'header' => __('Title'),
            'align' => 'left',
            'index' => 'title',
        ));
        $values = Mage::helper('octopushsms/Campaign')->get_status_array();
        $this->addColumn('status', array(
            'header' => __('Status'),
            'align' => 'left',
            'index' => 'status',
            'type' => 'options',
            'options' => $values,
            'renderer' => 'Octopush_OctopushSms_Block_Campaign_Renderer_Status',
        ));
        $this->addColumn('nb_recipients', array(
            'header' => __('Nb recipients'),
            'align' => 'left',
            'index' => 'nb_recipients',
        ));
        $this->addColumn('price', array(
            'header' => __('Price'),
            'align' => 'left',
            'index' => 'price',
            'renderer' => 'Octopush_OctopushSms_Block_Campaign_Renderer_Price',
        ));
        $this->addColumn('date_send', array(
            'header' => __('Sending date'),
            'align' => 'left',
            'index' => 'date_send',
        ));
        //TODO user action
        $link = Mage::getUrl('*/*/edit', array('id' => '$entity_id'));
        $this->addColumn('action_edit', array(
            'header' => $this->helper('octopushsms')->__('Action'),
            'width' => 15,
            'getter' => 'getId',
            'sortable' => false,
            'filter' => false,
            'type' => 'action',
            'actions' => array(
                array(
                    'url' => array('base' => '*/*/edit'),
                    'caption' => $this->helper('octopushsms')->__('Edit'),
                    'field' => 'id'
                ),
            )
        ));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
