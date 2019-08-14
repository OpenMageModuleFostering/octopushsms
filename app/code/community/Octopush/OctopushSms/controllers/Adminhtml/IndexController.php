<?php

/*
 * Controller of octopushsms plugin
 * (class has to be inherited from Mage_Core_Controller_action)
 */

class Octopush_OctopushSms_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {

    var $setting = null;
    var $balance = null;
    var $bAuth = null;

    /* protected function _construct()
      {
      parent::_construct();
      $this->setting=Mage::getModel('octopushsms/setting')->load('1');
      } */

    /**
     * TODO remove
     * @return boolean
     */
    public function test() {
        /*$product = Mage::getModel('catalog/product')->load(2);
        Mage::log($product->debug());
        $i = Mage::getModel('cataloginventory/stock_item')
               ->loadByProduct(2);
            Mage::log($i->getQty());
            Mage::log($i->getMinQty());
            Mage::log($i->getNotifyStockQty());*/
        
           /* $messageHelper = Mage::helper('octopushsms/message');
        
        $salesOrder = Mage::getModel('sales/order')->loadByIncrementId(100000030);
        $items = $salesOrder->getAllVisibleItems();
        foreach($items as $i) {
            if ($stockItem->getQty()<$stockItem->getNotifyStockQty()) {
                $params=array('product'=>$i);
                Mage::log($i->getId().' '.$i->getSku().' '.$i->getName());
                $messageHelper->send('action_update_quantity', $params);
            }
            //Mage::log($i->debug());
        }*/
        /*$api = Mage::helper('octopushsms/API');
          


//if method to get the values corresponding to this hook exist we continue
        $api->_recipients = null;
        $api->_phone = null;
        
        $api->_set_phone('+33651534631', 'FR', true);
        //check if everything is valid for sending the sms (if $this->_phone is not set, nothing is send)
        if (!$api->_is_everything_valid_for_sending()) {
            return false;
        }
        $api->_send_sms('ok on y va');*/

/*echo print_r(Mage::helper('octopushsms/statistic')->getSales(),true);
echo print_r(Mage::helper('octopushsms/statistic')->getVisits(),true);
echo print_r(Mage::helper('octopushsms/statistic')->getSubscriptions(),true);*/
        //echo Mage::getLo
    }
    
    public function indexAction() {
        //TODO remove 
        $this->test();
        $this->setting = Mage::getModel('octopushsms/setting')->load('1');
        $this->displaySettings();
    }

    private function displaySettings() {
        $api = Mage::helper('octopushsms/API');
        $api->user_login = $this->setting->getData('email');
        $api->api_key = $this->setting->getData('key');
        $api->sms_sender = $this->setting->getData('sender');

        $this->balance = $api->get_balance();
        $this->bAuth = $this->balance === false || $this->balance === '001' ? false : true;

        Mage::register('setting', $this->setting);
        Mage::register('balance', $this->balance);
        Mage::register('bAuth', $this->bAuth);
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Filter customer
     */
    public function ajaxFilterCustomerAction() {
        $s = !empty($this->getRequest()->getParam('q')) ? stripslashes($this->getRequest()->getParam('q')) : '';
        
        $helperCustomer = Mage::Helper('octopushsms/customer');
        $collection = $helperCustomer->find_customer($s);

        if (count($collection) == 0) {
            echo "<ul></ul>";
            return "";
        }
        echo "<ul>";
        foreach ($collection as $customer) {
            //var_dump($customer->debug());
            if ($customer->getData('billing_telephone')) {
                $api = Mage::Helper('octopushsms/API');
                $phone=$api->convert_phone_to_international($customer->getData('billing_telephone'), $customer->getData('billing_country_id'));
                echo "<li title='{\"id_customer\":\"".$customer->getId()."\",\"firstname\":\"".$customer->getFirstname()."\",\"lastname\":\"".$customer->getLastname()."\",\"telephone\":\"".$phone."\"}'>" . $customer->getName() . " " . $phone . "</li>";
            }
        }
        echo "</ul>";
        return;
    }

    
    public function savesettingsAction() {
        $this->setting = Mage::getModel('octopushsms/setting')->load('1');
//Zend_Debug::dump($this->setting);
//on recuperes les données envoyées en POST
        $this->setting->setData('email', $this->getRequest()->getPost('octopush_sms_email'));
        $this->setting->setData('key', $this->getRequest()->getPost('octopush_sms_key'));
        $this->setting->setData('admin_phone', $this->getRequest()->getPost('octopush_sms_admin_phone'));
        $this->setting->setData('sender', $this->getRequest()->getPost('octopush_sms_sender'));
        $this->setting->setData('admin_alert', $this->getRequest()->getPost('octopush_sms_admin_alert'));
//specific case of radio button group
        $this->setting->setData('free_option', $this->getRequest()->getPost('octopush_sms_free_option'));
        $this->setting->setData('id_product', $this->getRequest()->getPost('octopush_sms_id_product'));
//$this->setting->setData('admin_alert_sent',$this->getRequest()->getPost('octopush_sms_email'));
        $validate = $this->setting->validate();
//Zend_Debug::dump($validate);
        if ($validate === true) {
            $this->setting->save();
            Mage::getSingleton('adminhtml/session')->addSuccess(__('Settings have been save.'));
        } else {
            foreach ($validate as $error) {
                Mage::getSingleton('adminhtml/session')->addError($error);
            }
        }
        $messageHelper=Mage::helper('octopushsms/message');
        $messageHelper->ckeck_balance_for_admin_alert();
        $this->displaySettings();
    }

    public function campaignAction() {
        $this->loadLayout();
        $this->_setActiveMenu('octopushsms/Messages');
        $this->renderLayout();
    }

    public function historyAction() {
        $this->loadLayout();
        $this->_setActiveMenu('octopushsms/Messages');
        $this->renderLayout();
    }

    /**
     * Edit a campaign
     */
    public function editAction() {
        $campaignId = $this->getRequest()->getParam('id');
        $campaignModel = Mage::getModel('octopushsms/campaign')->load($campaignId);
        if ($campaignModel->getId() || $campaignId == 0) {
            Mage::register('campaign_data', $campaignModel);
            $this->loadLayout();
            $this->_setActiveMenu('octopushsms/Messages');
//$this->_addBreadcrumb('test Manager', 'test Manager');
//$this->_addBreadcrumb('Test Description', 'Test Description');
            $this->getLayout()->getBlock('head')
                    ->setCanLoadExtJs(true);
            /* $this->_addContent($this->getLayout()
              ->createBlock('octopushsms/campaign_edit'));
              $this->_addLeft($this->getLayout()
              ->createBlock('octopushsms/campaign_edit_info')
              ); */
            /* ->_addLeft($this->getLayout()
              ->createBlock('octopushsms/campaign_edit_tabs')
              ); */
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')
                    ->addError(__('Campaign does not exist'));
            $this->_redirect('*/*/');
        }
    }

    /**
     * New campaign
     */
    public function newAction() {
        $this->_forward('edit');
    }

    /**
     * Save a campaign
     * @return type
     */
    public function saveCampaignAction() {
        if (!$this->getRequest()->getPost()) {
            $this->_redirect('*/*/');
        }
        try {
            $postData = $this->getRequest()->getPost();
            $campaignModel = Mage::getModel('octopushsms/campaign');
            if ($this->getRequest()->getParam('id_sendsms_campaign') > 0) {
                $campaignModel->load(intval($this->getRequest()->getParam('id_sendsms_campaign')));
            }
            $campaignModel
                    ->addData($postData);
            if (!$this->getRequest()->getParam('id_sendsms_campaign') > 0) {
                $campaignModel->setData('id_sendsms_campaign', null);
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')
                    ->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')
                    ->settestData($this->getRequest()
                            ->getPost());
            $this->_redirect('*/*/edit', array('id' => $this->getRequest()
                        ->getParam('id_sendsms_campaign')));
            return;
        }

        $action = $this->getRequest()->getParam('button_id');
        $helper_api = Mage::Helper('octopushsms/API'); //define API constant
        if ($action == 'sendsms_save' && $campaignModel->getData('status') == 0) {
//create campaign
            $validate = $campaignModel->validate();
            if ($validate === true) {
                if ($campaignModel->save()) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign has been saved.'));
                } else {
                    Mage::getSingleton('adminhtml/session')->addError(print_r($campaignModel->getErrors(), true));
                }
            } else {
                foreach ($validate as $error) {
                    Mage::getSingleton('adminhtml/session')->addError($error);
                }
            }
        } else if ($campaignModel->getData('status') == 0 && isset($_FILES['sendsms_csv']['tmp_name']) && !empty($_FILES['sendsms_csv']['tmp_name'])) {
//import a csv file
//create campaign if it not exists
            $validate = $campaignModel->validate();
            if ($this->getRequest()->getParam('id_sendsms_campaign') <= 0) {
                $campaignModel->setData('ticket', (string) time());
                if (!empty($this->getRequest()->getParam('title'))) {
                    $campaignModel->setData('title', $this->getRequest()->getParam('title'));
                } else {
                    $campaignModel->setData('title', 'CAMPAIGN-' . $campaignModel->getData('ticket'));
                }
                if (!empty($this->getRequest()->getParam('message'))) {
                    $campaignModel->setData('message', $this->getRequest()->getParam('message'));
                } else {
                    $campaignModel->setData('message', __('Write your message here...'));
                }
                if (!empty($this->getRequest()->getParam('date_send'))) {
                    $campaignModel->setData('date_send', $this->getRequest()->getParam('date_send'));
                } else {
                    $campaignModel->setData('date_send', Mage::getModel('core/date')->date('Y-m-d H:m:s'));
                }
                $validate = $campaignModel->validate();
                if ($validate === true) {
                    if ($campaignModel->save()) {
                        Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign has been saved.'));
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError(print_r($campaignModel->getErrors(), true));
                    }
                } else {
                    foreach ($validate as $error) {
                        Mage::getSingleton('adminhtml/session')->addError($error);
                    }
                }
            }
            $tempFile = $_FILES['sendsms_csv']['tmp_name'];
            if (!is_uploaded_file($tempFile)) {
                Mage::getSingleton('adminhtml/session')->addError(__('The file has not been uploaded'));
            } else if ($validate === true) {
                $cpt = 0;
                $line = 0;
                if (($fd = fopen($tempFile, "r")) !== FALSE) {
                    while (($data = fgetcsv($fd, 1000, ";")) !== FALSE) {
                        $line++;
                        if (count($data) >= 1) {
                            $phone = $data[0];
// If not international phone
                            if (substr($phone, 0, 1) != '+')
                                continue;
                            $firstname = isset($data[1]) ? $data[1] : null;
                            $lastname = isset($data[2]) ? $data[2] : null;
// if phone is not valid

                            $recipient = Mage::getModel('octopushsms/recipient');
                            $recipient->setData('id_sendsms_campaign', $campaignModel->getData('id_sendsms_campaign'));
                            $recipient->setData('firstname', $firstname);
                            $recipient->setData('lastname', $lastname);
                            $recipient->setData('phone', $phone);
                            $recipient->setData('status', 0);

//if valid, we save
                            if ($recipient->validate() === true) {
// can fail if that phone number already exist for that campaign
                                try {
                                    $res = $recipient->save();
                                    if ($res->isObjectNew())
                                        $cpt++;
                                } catch (Exception $e) {
                                    
                                }
                            }
                        }
                    }
                    fclose($fd);
                }
                if ($line == 0)
                    Mage::getSingleton('adminhtml/session')->addError(__('That file is not a valid CSV file.'));
                else {
                    Mage::helper('octopushsms/Campaign')->compute_campaign($campaignModel);
                    Mage::getSingleton('adminhtml/session')->addSuccess($cpt . ' ' . __('new recipient(s) were added to the list.') . ($line - $cpt > 0 ? ' ' . ($line - $cpt) . ' ' . __('line(s) ignored.') : ''));
                }
            }
        } else if ($action == 'sendsms_transmit' && $campaignModel->getData('status') <= 1) {
//transmit the campaign to Octopush
            $validate = $campaignModel->validate();
            if ($validate === true) {
                $campaignModel->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign has been saved.'));
            } else {
                foreach ($validate as $error) {
                    Mage::getSingleton('adminhtml/session')->addError($error);
                }
            }
// if it's the first time we call "transmit"
            if ($validate === true && $campaignModel->getData('status') == 0) {
//check if there is a french number (beginning with +33)
//if this is the case, verify that the mention "STOP au XXXXX" is here                    
                $count = Mage::Helper('octopushsms/Campaign')->nbr_french_recipient($campaignModel);
                if ($count > 0 && strpos($campaignModel->getData('message'), _STR_STOP_) == false) {
                    Mage::getSingleton('adminhtml/session')->addError($helper_api->get_error_SMS(_ERROR_STOP_MENTION_IS_MISSING_));
                } else {
                    $campaignModel->setData('date_transmitted', time());
//TODO remove $date = strtotime($this->post['sendsms_date'] . ' ' . (int) (isset($this->post['sendsms_date_hour']) ? $this->post['sendsms_date_hour'] : 0) . ':' . (isset($this->post['sendsms_date_minute']) ? $this->post['sendsms_date_minute'] : 0) . ':00');
//$this->_campaign->date_send = date('Y-m-d H:i:s', $date);
                    $campaignModel->setData('status', 1);
                }
                $campaignModel->save();
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign is currently being transmitted, please do not close the window.'));
            }
        } else if ($action == 'sendsms_validate' && $campaignModel->getData('status') == 2) {
//validate the campaign to say to octopush that this campaign can be send
            if ($campaignModel->validate() === true) {
//TODO remove$this->_campaign->title = sanitize_text_field($this->post['sendsms_title']);
//$date = strtotime($this->post['sendsms_date'] . ' ' . (int) (isset($this->post['sendsms_date_hour']) ? $this->post['sendsms_date_hour'] : 0) . ':' . (isset($this->post['sendsms_date_minute']) ? $this->post['sendsms_date_minute'] : 0) . ':00');
//$this->_campaign->date_send = date_i18n('Y-m-d H:i:s', $date);
                $campaignModel->save();

//TODO ? $date = new DateTime(strtotime($this->_campaign->date_send));
//$date->setTimezone(new DateTimeZone('Europe/Paris'));
                $xml0 = $helper_api->validate_campaign($campaignModel->getData('ticket'), $campaignModel->getData('date_send'));
                $xml = simplexml_load_string($xml0);
                if ($xml->error_code == '000') {
                    $campaignModel->setData('status', 3);
                    $campaignModel->setData('date_validation', time());
                    Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign is now validated and will be sent at') . ' ' . Mage::helper('core')->formatDate($campaignModel->getData('date_send'),'medium',true));
                    $campaignModel->save();
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($helper_api->get_error_SMS($xml->error_code));
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError($helper_api->get_error_SMS($xml->error_code));
            }
        } else if ($action == 'sendsms_cancel' && $campaignModel->getData('status') >= 1 && $campaignModel->getData('status') < 3 && !($campaignModel->getData('status') == 3 && time() > $campaignModel->getData('date_send'))) {
//cancel a campaign if it is possible
            if ($campaignModel->getData('nb_recipients') > 0) {
                $xml = $helper_api->cancel_campaign($campaignModel->getData('ticket'));
                $xml = simplexml_load_string($xml);
                if ($xml->error_code == '000' || intval($xml->error_code) == _ERROR_BATCH_SMS_NOT_FOUND_) {
                    $campaignModel->setData('status', 4);
                    $campaignModel->save();
                    Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign is now canceled on octopushsms and can be deleted'));
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($helper_api->get_error_SMS($xml->error_code));
                }
            } else {
                $campaignModel->setData('status', 4);
                $campaignModel->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign is now canceled and can be deleted'));
            }
        } else if ($action == 'sendsms_duplicate' && $campaignModel->getData('id_sendsms_campaign')) {
//duplicate a campaign
            $newCampaign = Mage::helper('octopushsms/Campaign')->duplicate($campaignModel);
            $campaignModel = $newCampaign;
            Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign has been duplicated, you are now working on the new one.'));
        } else if ($action == 'sendsms_delete' && ($campaignModel->getData('status') == 0 || $campaignModel->getData('status') >= 3)) {
//delete the campaign if it is possible
            $res = $campaignModel->delete();
            if ($res == false) {
                Mage::getSingleton('adminhtml/session')->addError(__('Your campaign can not be deleted.'));
            } else {
                $campaignModel->setData('id_sendsms_campaign', 0);
                Mage::getSingleton('adminhtml/session')->addSuccess(__('Your campaign has been deleted.'));
                $this->_redirect('*/*/campaign');
                return;
            }
        }
        $this->_redirect('*/*/edit', array('id' => $campaignModel->getData('id_sendsms_campaign')));
    }

    /**
     * Delete a campaign.
     */
    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $campaignModel = Mage::getModel('octopushsms/campaign');
                $campaignModel->setId($this->getRequest()
                                ->getParam('id'))
                        ->delete();
                Mage::getSingleton('adminhtml/session')
                        ->addSuccess(__('successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * For grid ajax reload
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('octopushsms/campaign_grid')->toHtml()
        );
    }

    /**
     * For grid ajax reload
     */
    public function gridRecipientAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            $campaignModel = Mage::getModel('octopushsms/campaign')->load($this->getRequest()->getParam('id'));
            if ($campaignModel->getId()) {
                Mage::register('campaign_data', $campaignModel);
            }
        }
        $this->loadLayout();
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('octopushsms/recipient_grid')->toHtml()
        );
    }

    /**
     * For grid ajax reload TODO useful?
     */
    public function deleteRecipientAction() {
        $campaign = null;
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $recipientModel = Mage::getModel('octopushsms/recipient')->load($this->getRequest()->getParam('id'));
//get the recipient campaign
                $campaign = Mage::getModel('octopushsms/campaign')->load(intval($recipientModel->getData('id_sendsms_campaign')));
                if ($campaign->getId() && $campaign->getData('status') == 0) {
                    $recipientModel->delete();
                    Mage::helper('octopushsms/Campaign')->compute_campaign($campaign);
                    Mage::getSingleton('adminhtml/session')
                            ->addSuccess(__('successfully deleted'));
                } else {
                    Mage::getSingleton('adminhtml/session')
                            ->addError(__('Internal error: you can\'t delete recipient in campaign in status ' . $campaign->getData('status')));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/edit', array('id' => $campaign->getId()));
    }

    /**
     * Action for all ajax call
     */
    public function ajaxAddRecipientAction() {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $api = Mage::helper('octopushsms/API');

        $post = $_REQUEST;
//$phone = $this->getRequest()->getParam(''phone'];
        $id = $this->getRequest()->getParam('id_sendsms_campaign'); //$this->getRequest()->getParam(''id_sendsms_campaign']
//TODO remove var_dump($this->getRequest()->getParams()); return;
        $id_customer = $this->getRequest()->getParam('id_customer');
        $phone = $this->getRequest()->getParam('phone');
        $title = $this->getRequest()->getParam('title');
        $iso_country = $this->getRequest()->getParam('iso_country');
        $message = $this->getRequest()->getParam('message');
        $date_send = $this->getRequest()->getParam('date_send');

// if phone is not valid
        if (!Zend_Validate::is($phone, 'NotEmpty') || !preg_match('/^\+[0-9]{6,16}$/', $phone)) {
            $errors['error'] = __('That phone number is invalid.');
            $jsonData = Mage::helper('core')->jsonEncode($errors);
            $this->getResponse()->setBody($jsonData);
            return;
        }

// if we know the country, try to convert the phone to international
        if ($iso_country) {
            $phone = $api->convert_phone_to_international($phone, $iso_country);
            if (is_null($phone)) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('error' => __('The phone number and the country does not match.'))));
            }
        }

        if (!$id) {
            $campaign = Mage::getModel('octopushsms/campaign');
            $campaign->setData('ticket', (string) time());
            $campaign->setData('title', ($title == '' ? 'CAMPAIGN-' . $campaign->getData('ticket') : $title));
            $campaign->message = $message;
//TODO remove $date = strtotime($this->getRequest()->getParam(''sendsms_date'] . ' ' . (int) (isset($this->getRequest()->getParam(''sendsms_date_hour']) ? $this->getRequest()->getParam(''sendsms_date_hour'] : 0) . ':' . (isset($this->getRequest()->getParam(''sendsms_date_minute']) ? $this->getRequest()->getParam(''sendsms_date_minute'] : 0) . ':00');
            if ($date_send)
                $campaign->setData('date_send', $date_send);
            $campaign->save();
        } else {
            $campaign = Mage::getModel('octopushsms/campaign')->load($id);
        }

//create recipients
        $recipient = Mage::getModel('octopushsms/recipient');
        $recipient->setData('id_sendsms_campaign', $campaign->getData('id_sendsms_campaign'));
        $recipient->setData('id_customer', $id_customer);
        $recipient->setData('firstname', isset($post['sendsms_firstname']) ? $post['sendsms_firstname'] : '');
        $recipient->setData('lastname', isset($post['sendsms_lastname']) ? $post['sendsms_lastname'] : '');
        if ($recipient->getData('firstname') == '' && isset($post['firstname'])) {
            $recipient->setData('firstname', $post['firstname']);
        }
        if ($recipient->getData('lastname') == '' && isset($post['lastname'])) {
            $recipient->setData('lastname', $post['lastname']);
        }
        $recipient->setData('phone', $phone);
        $recipient->setData('iso_country', $post['iso_country']);
        $recipient->setData('status', 0);
// can fail if that phone number already exist for that campaign
        try {
            $recipient->save();
            $helperCampaign = Mage::helper('octopushsms/Campaign');
            $helperCampaign->compute_campaign($campaign);
            $jsonData = '{"campaign":' . Zend_Json::encode($campaign) . ',"recipient":' . Zend_Json::encode($recipient) . "}";
            $this->getResponse()->setBody($jsonData);
            return;
        } catch (Exception $e) {
            $jsonData = Mage::helper('core')->jsonEncode(array('error' => __('That phone number is already in the list.')));
            $this->getResponse()->setBody($jsonData);
            return;
        }
    }

    /**
     * Transmit the campaign on octopush server.
     * @return type
     */
    public function ajaxTransmitOwsAction() {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        if ($this->getRequest()->getParam('id_sendsms_campaign')) {

            $campaign = Mage::getModel('octopushsms/campaign')->load($this->getRequest()->getParam('id_sendsms_campaign'));
            $helperCampaign = Mage::helper('octopushsms/Campaign');
            $helperAPI = Mage::helper('octopushsms/API');
//send 200 by 200 recipients
            $result = $helperCampaign->get_recipient_to_transmit($campaign);
            $size = count($result);
            $total_rows = $helperCampaign->get_total_nbr_of_recipient_to_transmit();
            Mage::log('Ajax transmitOWS ' . $size . " " . $total_rows, Zend_Log::DEBUG);
            $finished = false;
            $campaign_can_be_send = false;
            if ((int) $size == (int) $total_rows)
                $finished = true;

            $error = false;
            $message = false;
//if there are other recipients to add
            if ($size != 0) {
//send recipient
                $recipients = array();
                foreach ($result as $recipient) {
                    $recipients[$recipient['phone']] = $recipient;
                }
// call OWS and get XML result                    
                $xml0 = $helperAPI->send_trame($campaign->getData('ticket'), $recipients, $campaign->getData('message'), $finished);
                $xml = simplexml_load_string($xml0);
                Mage::log('Ajax transmitOWS xml result' . print_r($xml, true), Zend_Log::DEBUG);

                if ($xml->error_code == '000') {
// success
                    foreach ($xml->successs->success as $success) {
                        $phone = (string) $success->recipient;
                        $recipients[$phone]['price'] = $success->cost;
//TODO $recipients[$phone]->nb_sms = $success->sms_needed;
                        $recipients[$phone]['status'] = 0;
                        $recipients[$phone]['ticket'] = (string) $xml->ticket;
                        $recipients[$phone]['transmitted'] = 1;
                        $recipients[$phone]['country_code'] = (string) $xml->country_code;
                    }

// errors
                    foreach ($xml->failures->failure as $failure) {
                        $phone = (string) $failure->recipient;
                        $recipients[$phone]['price'] = 0;
//TODO $recipients[$phone]->nb_sms = 0;
                        $recipients[$phone]['status'] = $failure->error_code;
                        $recipients[$phone]['ticket'] = (string) $xml->ticket;
                        $recipients[$phone]['transmitted'] = 1;
                        $recipients[$phone]['country_code'] = (string) $xml->country_code;
                    }

// convert recipient to Recipient model
                    foreach ($recipients as $key => $recipient) {
// update th recipient information
                        $collectionRecipient = Mage::getModel('octopushsms/recipient')->getCollection();
                        $collectionRecipient->addFieldToFilter('phone', $key);
                        $collectionRecipient->addFieldToFilter('id_sendsms_campaign', $campaign->getData('id_sendsms_campaign'));
                        foreach ($collectionRecipient as $recipientDb) {
                            $recipientDb->setData('price', $recipient['price']);
                            $recipientDb->setData('status', $recipient['status']);
                            $recipientDb->setData('ticket', $recipient['ticket']);
                            $recipientDb->setData('iso_country', $recipient['country_code']);
//TODO country
                            $recipientDb->setData('transmitted', $recipient['transmitted']);
                            $query = $recipientDb->save();
                            Mage::log("save query below: ", Zend_Log::DEBUG); //I get this string
                            Mage::log($query->toString(), Zend_Log::DEBUG);
                        }
                    }

// update the campaign totals
                    $campaign->setData('date_transmitted', (string) time());
                    $helperCampaign->compute_campaign($campaign, 1);
                    $campaign->setData('status_label', $helperCampaign->get_status($campaign->getData('status')));
                    $message=__('Transfer in progress : ') . ($total_rows - $size) . ' ' . __('recipients left');
                } else {
                    $error = $helperAPI->get_error_SMS($xml->error_code);
                }
            }

//if there is no more recipient to send, we check the status on octopush
//if size=0, no more recipients to send, we only have to check the status
//until octopush finish to do what he has to do
            if (!$error && $finished) {
                $xml0 = $helperAPI->get_campaign_status($campaign->getData('ticket'));
                $xml = simplexml_load_string($xml0);
                if ($xml->error_code == '000') {
                    $campaign->setData('status', 2);
                    $campaign->setData('status_label', $helperCampaign->get_status($campaign->status));
                    $campaign->setData('price', floatval($xml->cost));
                    $campaign->save();
                    $campaign_can_be_send = true;
                } else if ($xml->error_code == _ERROR_BATCH_SMS_PROSSESSING_) {
                    //we wait until octopush finish to build
                    sleep(10);
                    $message = $helperAPI->get_error_SMS($xml->error_code);
                } else {
                    $error = $helperAPI->get_error_SMS($xml->error_code);
                }
            }
            $jsonData = Mage::helper('core')->jsonEncode(array('campaign' => $campaign->getData(), 'finished' => $campaign_can_be_send, 'total_rows' => $total_rows - $size, 'error' => $error, 'message' => $message));
            $this->getResponse()->setBody($jsonData);
            return;
        }
    }

    public function ajaxCountRecipientFromQueryAction() {
        $campaignId = $this->getRequest()->getParam('id_sendsms_campaign');
        $campaignModel = Mage::getModel('octopushsms/campaign')->load($campaignId);
        $result = Mage::helper('octopushsms/Campaign')->get_recipients_from_query($this, $campaignModel, true);
        $jsonData = Mage::helper('core')->jsonEncode(array('total_rows' => (int) $result));
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
        return;
    }

    public function ajaxAddRecipientsFromQueryAction() {
        $campaignId = $this->getRequest()->getParam('id_sendsms_campaign');
        if (!$campaignId) {
            $jsonData = Mage::helper('core')->jsonEncode(array('error' => __('campaignId is null.')));
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($jsonData);
            return;
        }
        $campaign = Mage::getModel('octopushsms/campaign')->load($campaignId);
        $collection = Mage::helper('octopushsms/Campaign')->get_recipients_from_query($this, $campaign, false);

        $cpt = 0;
        $api = Mage::helper('octopushsms/API');
        foreach ($collection as $customer) {
            $recipient = Mage::getModel('octopushsms/recipient');
            $recipient->setData('id_sendsms_campaign', $campaign->getData('id_sendsms_campaign'));
            $recipient->setData('id_customer', $customer->getData('entity_id'));
            $recipient->setData('firstname', $customer->getData('firstname'));
            $recipient->setData('lastname', $customer->getData('lastname'));
            $phone = $api->convert_phone_to_international($customer->getData('billing_telephone'), $customer->getData('billing_country_id'));
            if (is_null($phone))
                continue;
            $recipient->setData('phone', $phone);
            $recipient->setData('iso_country', $customer->getData('billing_country_id'));
            $recipient->setData('status', 0);
// can fail if that phone number already exist for that campaign
            try {
                $recipient->save();
                $cpt++;
            } catch (Exception $e) {
                //do nothing, we skip this customer
            }
        }
        $helperCampaign = Mage::helper('octopushsms/Campaign');
        $helperCampaign->compute_campaign($campaign);
        $jsonData = Mage::helper('core')->jsonEncode(array('campaign' => Zend_Json::encode($campaign), 'total_rows' => (int) $cpt));
        $this->getResponse()->setBody($jsonData);
        return;
    }

    /**
     * Action to display news from octopush
     */
    public function newsAction() {
        $this->loadLayout();
        $this->_setActiveMenu('octopushsms/Messages');
        $this->renderLayout();
    }

    /**
     * Action to display the messages configuration page
     */
    public function messagesAction() {
        $this->loadLayout();
        $this->_setActiveMenu('octopushsms/Messages');
        $this->renderLayout();
    }

    public function saveMessagesAction() {
        if (!$this->getRequest()->getPost()) {
            $this->_redirect('*/*/');
        }
        
        //var_dump($this->getRequest()->getParams());
        
        //if clear all messages
        if ($this->getRequest()->getParam('action') == 'clear') {
            $messageHelper = Mage::helper('octopushsms/message');
            //put default text for all messages
            $collection = Mage::getModel('octopushsms/message')->getCollection();
            foreach ($collection as $message) {
                $message->setData('message', $messageHelper->get_sms_default_text_from_message($message));
                $message->save();
            }
        } else {
            //for each post
            foreach ($this->getRequest()->getParams() as $key => $message) {
                if (is_int($key)) {
                    //load message from database
                    $messageModel = Mage::getModel('octopushsms/message')->load($key);
                    if ($messageModel->hasData()) {
                        $messageModel->setData('is_active', array_key_exists('isactive', $message));
                        $messageModel->setData('message', $message['message']);
                        $messageModel->save();
                    }
                }
            }
        }
        $this->_redirect('routeradmin/adminhtml_index/messages');
    }

}
