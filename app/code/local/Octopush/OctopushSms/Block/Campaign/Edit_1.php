<?php

class Octopush_OctopushSms_Block_Campaign_Edit extends
Mage_Adminhtml_Block_Widget_Form_Container {

    public function output_button() {
            $b_auth = get_option('octopush_sms_email') ? true : false;
            ?>
            <div id="sendsms_buttons">
                <div id="buttons" class="clear center" style="display: <?php (isset($_REQUEST['sendsms_transmit']) && $this->_campaign->status == 1 && !sizeof(self::$errors) ? 'none' : 'block') ?>">
                    <?php if (get_class($this) == 'Octopush_Sms_Send_Tab') { ?>
                        <input type="submit" id="sendsms_save" name="sendsms_save" value="<?php _e('Save this campaign', 'octopush-sms') ?>" class="button button-primary" />
                        <input <?php (!$b_auth ? 'disabled="disabled"' : '') ?> type="submit" id="sendsms_transmit" name="sendsms_transmit" value="<?php _e('Transmit to Octopush', 'octopush-sms') ?>" class="button button-primary" />
                        <?php if ($this->_campaign->status < 3) { ?>
                            <input <?php (!$b_auth ? 'disabled="disabled"' : '') ?> type="submit" id="sendsms_validate" name="sendsms_validate" value="<?php _e('Accept & Send', 'octopush-sms') ?>" class="button button-primary" /> 
                        <?php } ?>
                        <?php
                    }
                    if ($this->_campaign->status >= 1 || $this->_campaign->status < 3 || ($this->_campaign->status == 3 && the_date('Y-m-d H:i:s') < $this->_campaign->date_send)) {
                        ?>
                        <input <?php (!$b_auth ? 'disabled' : '') ?> type="submit" id="sendsms_cancel" name="sendsms_cancel" value="<?php _e('Cancel this campaign', 'octopush-sms') ?>" class="button button-primary" />
                    <?php } ?>
                    <input type="submit" id="sendsms_delete" name="sendsms_delete" value="<?php _e('Delete this campaign', 'octopush-sms') ?>" class="button button-primary" /> 
                    <?php if ($this->_campaign->event == 'sendsmsFree') { ?>
                        <input type="submit" id="sendsms_duplicate" name="sendsms_duplicate" value="<?php _e('Duplicate this campaign', 'octopush-sms') ?>" class="button button-primary" />
                    <?php } ?>
                </div>
			</div>
            <?php
            if (get_class($this) == 'Octopush_Sms_Send_Tab' && isset($_POST['sendsms_transmit']) && $this->_campaign->status == 1 && !sizeof(self::$errors)) {
                echo '<div id="progress_bar" class="error fade">' . __('Transfer in progress :', 'octopush-sms') . ' <span id="waiting_transfert">' . $this->_campaign->nb_recipients . '</span> ' . __('recipients left', 'octopush-sms') . '</div>';
            }
        }
        
    public function __construct() {
        $data = array(
            'label' => __('Back'),
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/campaign') . '\')',
            'class' => 'back'
        ); 
        //'Transmit to Octopush'
        //'Accept & Send'
        //'Cancel this campaign'
        //'Delete this campaign'
        //'Duplicate this campaign'
        
        //TODO barre d'avancement
        $transmit = array(
            'label' => __('Transmit to Octopush'),
            'onclick' => 'transmitToOWS();',
            'class' => 'transmit'
        );
        $accept = array(
            'label' => __('Accept & Send'),
            'onclick' => 'transmitToOWS();',
            'class' => 'accept'
        );
        $cancel= array(
            'label' => __('Cancel this campaign'),
            //'onclick' => 'transmitToOWS();',
            'class' => 'cancel'
        );
        $duplicate= array(
            'label' => __('Duplicate this campaign'),
            //'onclick' => 'transmitToOWS();',
            'class' => 'cancel'
        );
        
        parent::__construct();
        $this->_removeButton('back');
        $this->addButton('my_back', $data, 0, -200, 'header');
        $this->_removeButton('reset');
        
        $this->_objectId = 'id';
        //vous remarquerez qu’on lui assigne le même blockGroup que le Grid Container
        $this->_blockGroup = 'octopushsms';
        //et le meme controlleur
        $this->_controller = 'campaign';
        //on definit les labels pour les boutons save et les boutons delete
        $this->_updateButton('save', 'label', __('Save this campaign'));
        $this->_updateButton('delete', 'label', __('delete campaign'));
    }

    public function getHeaderText() {
        if (Mage::registry('campaign_data') && Mage::registry('campaign_data')->getId()) {
            return __('Edit campaign ') . $this->htmlEscape(
                            Mage::registry('campaign_data')->getTitle()) . '<br />';
        } else {
            return __('Add campaign');
        }
    }

}
