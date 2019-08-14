<?php

class Octopush_OctopushSms_Block_Campaign_Edit_Info extends Mage_Core_Block_Template {

    protected $Helper_api;
    protected $Helper_campaign;
    protected $balance;
    protected $campaignModel;

    public function __construct() {
        $this->Helper_api = Mage::helper('octopushsms/API');
        $this->Helper_campaign = Mage::helper('octopushsms/Campaign');
        $api_balance = $this->Helper_api->get_balance();
        $this->balance = $api_balance !== '001' ? $api_balance : 0;
//get the campaign
        $this->campaignModel = Mage::registry('campaign_data');
        parent::__construct();
    }

    /**
     * 
     * @return string
     */
    public function getHeadInformation() {
        $notice_text = '<div class="box">';
        $notice_text .= "<p>";
        $notice_text.=__('This module allows you to send SMS to the admin, or to customers on different events.');
        $notice_text.=__('It also allows to send Bulk SMS for marketing campaign.');
        $notice_text.="</p>";
        $notice_text.=__('First, you have to create an account on ');
        $notice_text.='<b><a href="http://www.octopush.com/inscription" target="_blank">www.octopush.com</a></b>';
        $notice_text.=__(' and credit this account to be able to send SMS.');
        $notice_text.="</p><p>";
        $notice_text.=__('Then, please fill your identification settings, and set your options.');
        $notice_text.="</p><p>";
        $notice_text.=__('If you want that the customers pay for the notification service, first create a product representing the SMS service, then fill the product ID field.');
        $notice_text.=__('Finally, you have to activate/desactivate events you want on the "Messages" tab, and if needed, customize the text that will be sent for each event.');
        $notice_text.="</p><p>";
        $notice_text.=__('Enjoy !');
        $notice_text.="</p></div>";

        return $notice_text;
    }

    
}
    