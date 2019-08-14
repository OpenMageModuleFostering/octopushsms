<?php

class Octopush_OctopushSms_Block_Campaign_Edit_Form extends
Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        //get the campaign
        $campaignModel = Mage::registry('campaign_data');
        $form = new Varien_Data_Form(
                array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))
            ),
            'method' => 'post',
                )
        );

        $form->setUseContainer(true);
        //$this->setForm($form);
        $this->setForm($form);

        //set legend of this block
        $legend = __('SMS Details');
        if ($campaignModel->getStatus() == 0) {
            $legend = __('SMS Settings');
        }


        $fieldset = $form->addFieldset('campaign_form', array('legend' => $legend));

        $setting = Mage::getModel('octopushsms/setting')->load('1');
        $b_auth = $setting->getData('sendsms_mail') ? true : false;

        if (!$b_auth) {
            $fieldset->addField('note', 'note', array(
                'text' => '<span class="failed> ' . __('Before sending a message, you have to enter your account information in the Settings Tab.') . '</span><br/><br/>',
            ));
        }

        //TODO remove $notice=$this->getLayout()->createBlock('octopushsms/campaign_edit_info')->setTemplate('octopushsms/notice.phtml')->toHtml();


        $fieldset->addField('id_sendsms_campaign', 'hidden', array(
            'name' => 'id_sendsms_campaign',
        ));

        $fieldset->addField('note2', 'note', array(
            'text' => "model:" . $campaignModel->getStatus() . "|" . ($campaignModel->getStatus() < 2) . "|" . empty($campaignModel->getStatus()),
        ));


        $fieldset->addField('title', 'text', array(
            'label' => __('Title of the campaign'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'title',
            'maxlength' => "255",
        ));
        $fieldset->addField('message', 'textarea', array(
            'label' => __('Message'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'message',
            'onclick' => "",
            'onchange' => "",
            'value' => '',
            'disabled' => ($campaignModel->getStatus() < 2) == 1 ? false : true,
            //'readonly' => ($campaignModel->getStatus() < 2 )==1 ? "false" : true,
            'after_element_html' => '<br/><small>' . __('Variables you can use : {firstname}, {lastname}') . '</small>',
            'tabindex' => 1
        ));
        /* $fieldset->addField('telephone', 'text', array(
          'label' => 'telephone',
          'class' => 'required-entry',
          'required' => true,
          'name' => 'telephone',
          )); */
        $fieldset->addField('date_send', 'date', array(
            'label' => __('Send date'),
            'name' => 'date_send', // should match with your table column name where the data should be inserted 
            'time' => true,
            //'class' => 'required-entry',
            //'required' => true,
            'format' => $this->escDates(),
            'image' => $this->getSkinUrl('images/grid-cal.gif')
        ));
        if (Mage::registry('campaign_data')) {
            $form->setValues(Mage::registry('campaign_data')->getData());
        }
        return parent::_prepareForm();
    }

    private function escDates() {
        return 'yyyy-MM-dd HH:mm:ss';
        //Mage_Core_Model_Locale::F
    }

}
