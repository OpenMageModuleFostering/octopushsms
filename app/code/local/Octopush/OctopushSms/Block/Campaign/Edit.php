<?php

class Octopush_OctopushSms_Block_Campaign_Edit extends Mage_Core_Block_Template {

    public $setting;
    public $b_auth;
    public $campaign;
    protected $Helper_api;
    protected $Helper_campaign;
    protected $balance;
    public $back_url;

    public function __construct() {
        $setting = Mage::getModel('octopushsms/setting')->load('1');
        $this->b_auth = $setting->getData('octopush_sms_email') ? true : false;
        $this->campaign = Mage::registry('campaign_data');
        $this->Helper_api = Mage::helper('octopushsms/API');
        $this->Helper_campaign = Mage::helper('octopushsms/Campaign');
        $api_balance = $this->Helper_api->get_balance();
        $this->balance = $api_balance !== '001' ? $api_balance : 0;

        if ($this->getRequest()->getActionName() != 'history') {
            $this->back_url = Mage::helper("adminhtml")->getUrl('adminoctopushsms/adminhtml_index/campaign/');
        } else {
            $this->back_url = Mage::helper("adminhtml")->getUrl('adminoctopushsms/adminhtml_index/history/');
        }
        parent::_construct();
    }

    public function output_button() {
//setLocation(\'' . $this->getUrl('*/*/campaign') . '\')
        ?>

        <div class="content-header">
            <h3 class="icon-head head-campaign"><?php echo __('Edit campaign') ?></h3>
            <p class="form-buttons" style="display: <?php (isset($_REQUEST['sendsms_transmit']) && $this->campaign == 1 ? 'none' : 'block') ?>">
                <button id="id_back" title="Back" type="button" class="scalable back" onclick="setLocation('<?php echo $this->back_url; ?>')" style="">
                    <span><span><span>Back</span></span></span>
                </button>
                <!--TODO remove <button id="id_5e4de885b14e6e9025bfb2c135241f29" title="delete campaign" type="button" class="scalable delete" onclick="deleteConfirm('Are you sure you want to do this?', 'http://localhost/mag/index.php/adminoctopushsms/adminhtml_index/delete/id/11/key/03f696c687944087e06e2591b97a905d/')" style="">
                    <span><span><span>delete campaign</span></span></span>
                </button>-->
                <?php if ($this->getRequest()->getActionName() == 'edit') { ?>
                    <button id="sendsms_save" name="sendsms_save" title="<?php echo __('Save this campaign') ?>" type="button" class="scalable save"  style="" onclick="submitForm(this);">
                        <span><span><span><?php echo __('Save this campaign') ?></span></span></span>
                    </button>
                    <button <?php (!$this->b_auth ? 'disabled="disabled"' : '') ?> id="sendsms_transmit" name="sendsms_transmit" title="<?php echo __('Transmit to Octopush') ?>" type="button" class="scalable save" onclick="submitForm(this);" style="">
                        <span><span><span><?php echo __('Transmit to Octopush') ?></span></span></span>
                    </button>
                    <?php if ($this->campaign->getData('status') < 3) { ?>
                        <button <?php (!$this->b_auth ? 'disabled="disabled"' : '') ?> id="sendsms_validate" name="sendsms_validate" title="<?php echo __('Accept & Send') ?>" type="button" class="scalable save" onclick="submitForm(this);" style="">
                            <span><span><span><?php echo __('Accept & Send') ?></span></span></span>
                        </button>
                        <?php
                    }
                }
                if ($this->campaign->getData('status') >= 1 || $this->campaign->getData('status') < 3 || ($this->campaign->getData('status') == 3 && Mage::getModel('core/date')->date('Y-m-d H:m:s') < $this->campaign->getData('date_send'))) {
                    ?>
                    <button <?php (!$this->b_auth ? 'disabled="disabled"' : '') ?> id="sendsms_cancel" name="sendsms_cancel" title="<?php echo __('Cancel this campaign') ?>" type="button" class="scalable save" onclick="submitForm(this);" style="">
                        <span><span><span><?php echo __('Cancel this campaign') ?></span></span></span>
                    </button>
                <?php } ?>
                <button <?php (!$this->b_auth ? 'disabled="disabled"' : '') ?> id="sendsms_delete" name="sendsms_delete" title="<?php echo __('Delete this campaign') ?>" type="button" class="scalable delete" onclick="submitForm(this);" style="">
                    <span><span><span><?php echo __('Delete this campaign') ?></span></span></span>
                </button>
                <?php if ($this->campaign->getData('event') == 'sendsmsFree') { ?>
                    <button <?php (!$this->b_auth ? 'disabled="disabled"' : '') ?> id="sendsms_duplicate" name="sendsms_duplicate" title="<?php echo __('Duplicate this campaign') ?>" type="button" class="scalable save" onclick="submitForm(this);" style="">
                        <span><span><span><?php echo __('Duplicate this campaign') ?></span></span></span>
                    </button>
                <?php } ?>
            </p>
        </div>        
        <?php
    }

    public function getHeaderText() {
        if (Mage::registry('campaign_data') && Mage::registry('campaign_data')->getId()) {
            return __('Edit campaign ') . $this->htmlEscape(
                            Mage::registry('campaign_data')->getTitle()) . '<br />';
        } else {
            return __('Add campaign');
        }
    }

    public function output_campaign_details() {
        echo '
<fieldset class="box" id="block_infos">

    <h2><span class="dashicons dashicons-info vmiddle"></span>' . __('Information') . '</h2>
    <div class="toastgrid">
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Amount in your account') . '</div>
        <div id="balance" class="toastgrid__col toastgrid__col--2-of-5">' . $this->balance . ' &euro;</div>
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Campaign ID') . '</div>
        <div id="id_campaign" class="toastgrid__col toastgrid__col--2-of-5">' . $this->campaign->getId() . '</div>
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Ticket') . '</div>
        <div id="ticket" class="toastgrid__col toastgrid__col--2-of-5">' . $this->campaign->ticket . '</div>
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Status') . '</div>
        <div id="status" class="toastgrid__col toastgrid__col--2-of-5">' . $this->Helper_campaign->get_status($this->campaign->getStatus()) . '</div>
'.
        ($this->campaign->status == 5 ? '
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Error') . '</div>
        <div id="error_code" class="toastgrid__col toastgrid__col--2-of-5">' . $this->Helper_api->get_error_SMS($this->campaign->getErrorCode()) . '</div>
        ' : '') .'
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Recipients') . '</div>
        <div id="nb_recipients" class="toastgrid__col toastgrid__col--2-of-5">' . $this->campaign->getNbRecipients() . '</div> 
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Price') . '</div>
        <div id="price" class="toastgrid__col toastgrid__col--2-of-5">' . number_format($this->campaign->getPrice(), 3, '.', '') . ' €</div>
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Send date') . '</div>
        <div class="toastgrid__col toastgrid__col--2-of-5">' . ($this->campaign->getDateSend() != "0000-00-00 00:00:00" ? Mage::helper('core')->formatDate($this->campaign->getDateSend(), 'medium', true) : '') . '</div>
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Transmition date') . '</div>
        <div class="toastgrid__col toastgrid__col--2-of-5">' . ($this->campaign->getDateTransmitted() != "" ? Mage::helper('core')->formatDate($this->campaign->getDateTransmitted(), 'medium', true) : '') . '</div>
        <div class="toastgrid__col toastgrid__col--3-of-5">' . __('Validation date') . '</div>
        <div class="toastgrid__col toastgrid__col--2-of-5">' . ($this->campaign->getDateValidation() != "" ? Mage::helper('core')->formatDate($this->campaign->getDateValidation(), 'medium', true) : '') . '</div>
    </div>
</fieldset>';
    }

}
