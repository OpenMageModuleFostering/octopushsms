<?php

/**
 * Block to dislay messages from octopush
 */
class Octopush_OctopushSms_Block_Messages extends Mage_Core_Block_Template {

    private $api;
    private $message;

    public function __construct(array $args = array()) {
        parent::__construct($args);
        $this->api = Mage::helper('octopushsms/API');
        $this->message = Mage::helper('octopushsms/Message');
    }

    public function getBody() {
        //save is date is send
        //$this->_post_process();
        //WC_Admin_Settings::show_messages();
        //$defaultLanguage = (int) $this->context->language->id;

        $admin_html = '';
        //display messages for each possible admin hook
        foreach ($this->message->admin_config as $hookId => $hookName) {
            $admin_html .= $this->_get_code($hookId, $hookName, true);
        }

        $customer_html = '';
        foreach ($this->message->customer_config as $hookId => $hookName) {
            if ($hookId != 'action_order_status_update') {
                /* TODO to validate if ($hookId == 'action_validate_order' || $hookId == 'action_admin_orders_tracking_number_update') {
                  $customer_html .= $this->_get_code($hookId, $hookName, false, null, true);
                  } else { */
                $customer_html .= $this->_get_code($hookId, $hookName, false);
                //}
            } else {
                //specific hook when status of a command change
                $statusCollection = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
                foreach ($statusCollection as $status) {
                    $customer_html .= $this->_get_code($hookId . "_".$status['status'], $hookName . " (".$status['label'].")", false, null, true);
                }
            }
        }

        $html = '
			<div id="' . get_class($this) . '">
				<br /><b>' . __('Choose SMS you want to activate, and customize their text.') . '</b><br /><br />';
        $html .=
                __('On the right side you can see a preview to check that everything is ok.') . '<br /><br />
			<div class="clear"></div>
				<div class="wrap woocommerce">
<form action="' . Mage::getUrl('adminoctopushsms/adminhtml_index/saveMessages') . '" method="post" id="edit_form" enctype="multipart/form-data" >
    <div>
        <input name="form_key" type="hidden" value="' . Mage::getSingleton('core/session')->getFormKey() . '"/>
        <input id="action" name="action" value="edit" type="hidden">    
    </div>						
						<h3>' . __('SMS for Admin') . '</h3>
						<div class="os_row">' .
                $admin_html . '
						</div>
						<br />
						<input name="save" class="button-primary" type="submit" value="' . __('Update') . '" />
						<br /><br />
						<h3>' . __('SMS for Customer') . '</h3>
						<div class="os_row">' .
                $customer_html . '
						</div>
						<br />
						<input class="button-primary" type="submit" name="save2" value="' . __('Update') . '" class="button" />
						<input class="button-primary" type="submit" name="resettxt" value="' . __('Reset all messages') . '" class="button" />
					</form>
				</div>
			</div>';
        return $html;
    }

    /**
     * Get html fragment for this hook
     * @param type $hookId the id of the hook
     * @param type $hookName the short description of the hook
     * @param type $bAdmin
     * @param type $comment
     * @param type $bPaid
     * @return string
     */
    private function _get_code($hookId, $hookName, $bAdmin = false, $comment = null, $bPaid = false) {

        $message = $this->message->get_message($hookId, $bAdmin);
        //echo var_dump($message->debug());
        //$defaultLanguage = (int)$this->context->language->id;

        $keyActive = $this->message->_get_isactive_hook_key($message);

        //To test with dummy values
        $values = $this->message->get_sms_values_for_test($hookId);

        $key = $this->message->_get_hook_key($message);

        $code = '
		<div class="ows">
			<table class="messages_data">
				<tr valign="top" class="sms">
				<th scope="row" class="titledesc text_td">
					<label for="octopush_sms_email">' . __($hookName);

        //if option is not free and the customer pay for it
        if ($bPaid && (int) Mage::getModel('octopushsms/setting')->load('1')->getData('freeoption') == 0) {
            $code .= '<br/><span style="font-weight: normal">' . __('Sent only if customer pay the option') . '</span>';
        }
        $code.='</label>
				</th>
				<td class="forminp forminp-' . $message->getData('id_message') . ' data_td">';
        $code .= '<input ' . ($this->message->is_active($message) == 1 ? 'checked' : '') . ' type="checkbox" name="' . $keyActive . '" value="1"/> ' . __('Activate') . ' ?<br/>';

        $messageHook = $message->getData('message');
        $txt = $this->api->replace_for_GSM7($messageHook ? $messageHook : $this->message->get_sms_default_text($message));
        //TODO test
        $txt_test = $this->api->replace_for_GSM7(str_replace(array_keys($values), array_values($values), $txt));
        $bGSM7 = $this->api->is_GSM7($txt_test);

        $code .= '<textarea name="' . $key . '" rows="4" class="message_textarea">' . $txt
                . '</textarea>
								 <br/><span class="description">' .
                (!$bGSM7 ? '<img src="../img/admin/warning.gif"> ' . __('This message will be divided in 70 chars parts, because of non standard characters : ') . ' ' . $this->api->not_GSM7($txt_test) : __('This message will be divided in 160 chars parts')) .
                '</span>'
                . '<br/>';
        $code.= '<span class="description">' . __('Variables you can use : ') . ' ' . implode(', ', array_keys($values)) . '</span>								
					 </td>
					 <td class="forminp forminp-' . $hookId . '-example" class="text_td">
				<br />
								<textarea class="check" readonly rows="4" class="message_textarea">' . $txt_test . '</textarea>';
        $code .= '</td>
			</tr>
			</table>
			</div>';
        //TODO ? no mulitlangual support
        return $code;
    }

    /**
     * Update option corresponding to the hook
     * @param type $hook
     * @param type $b_admin
     */
    public function update_message_option($hook, $b_admin = false) {
        //if is active
        $hook_is_active = $this->api->_get_isactive_hook_key($hook, $b_admin);
        if (array_key_exists($hook_is_active, $_POST)) {
            $value = wc_clean($_POST[$hook_is_active]);
            //save the option
            update_option($hook_is_active, (int) $value);
        } else {
            update_option($hook_is_active, 0);
        }
        //message text
        $hook_key = $this->api->_get_hook_key($hook, $b_admin);
        if (array_key_exists($hook_key, $_POST)) {
            $value = stripslashes($_POST[$hook_key]);
            //save the option
            update_option($hook_key, $this->api->replace_for_GSM7(trim($value)));
        }
        //specific case of 'action_order_status_update'
        if ($hook == 'action_order_status_update') {
            global $wp_post_statuses;
            foreach ($wp_post_statuses as $key => $value) {
                if (strstr($key, 'wc-')) {
                    $this->update_message_option($hook . "_$key", $b_admin);
                }
            }
        }
    }

}
