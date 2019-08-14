<?php

//API give by octopush
require_once __DIR__ . DIRECTORY_SEPARATOR . 'API_SMS_PHP_Octopush/octopush_web_services.inc.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'API_SMS_PHP_Octopush/sms.inc.php';

class Octopush_OctopushSms_Helper_API extends Mage_Core_Helper_Abstract {

    private static $octopush_sms_api;
    public $_phone = null;
    public $_recipients = null;
    public $sms_type = SMS_WORLD; // ou encore SMS_STANDARD,SMS_PREMIUM
    public $sms_mode = DIFFERE; // ou encore DIFFERE
    public $sms_sender;
//for campaign
    private $_recipient;
    private $_paid_by_customer = 0;
    private $_event = '';

    /**
     * Admin messages with their title
     */
    public $admin_config;
    public $customer_config;

    /* TODO remove  public static function get_instance() {
      global $octopush_sms_api;
      if (is_null($octopush_sms_api)) {
      $setting = Mage::getModel('octopushsms/setting')->load('1');
      $octopush_sms_api = new Octopush_OctopushSms_Helper_API($setting->getData('email'), $setting->getData('key'));
      }
      return $octopush_sms_api;
      } */

    /**
     * Octopush user login
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $user_login;

    /**
     * Octopush API Key
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $api_key;

    /**
     * Constructor
     * @param string $user_login
     * @param string $api_key
     */
    public function __construct() {
        $setting = Mage::getModel('octopushsms/setting')->load('1');
        $this->user_login = $setting->getData('email');
        $this->api_key = $setting->getData('key');
        $this->sms_sender = $setting->getData('sender');

        //initialisation of possible admin message
        $this->admin_config = array(
            'action_create_account' => __('Create account'), //don't suppress                
                //'action_send_message' => __('Send Message'),
                //'action_validate_order' => __('Validate Order'),
                //'action_order_return' => __('Order return'),
                //'action_update_quantity' => __('Stock notification'),
                //'action_admin_alert' => __('Admin alert'),
                //'action_daily_report' => __('Daily report', 'octopush-sms')
        );

        $this->customer_config = array(
            'action_create_account' => __('Create account'),
            'action_password_renew' => __('Send SMS when customer has lost his password'),
            //'action_customer_alert' => __('Send SMS when product is available'),
            'action_send_message' => __('Send Message'),
            'action_validate_order' => __('Validate Order'),
            //'action_admin_orders_tracking_number_update' => __('Order tracking number update'),
            'action_order_status_update' => __('Status update', 'octopush-sms'));
    }

    public function get_account() {
        Mage::log("octopush-sms - get_account ,parameters $this->user_login , $this->api_key", Zend_Log::DEBUG, "octopushsms.log");
        if (!empty($this->user_login) && !empty($this->api_key)) {
            $sms = new OWS();
            $sms->set_user_login($this->user_login);
            $sms->set_api_key($this->api_key);
            Mage::log("octopush-sms - get_account ", Zend_Log::DEBUG, "octopushsms.log");
            $xml0 = $sms->get_balance();
            $xml = simplexml_load_string($xml0);
            if (!key_exists('error_code', $xml) || $xml->error_code == '000') {
//if balance greater than alert_level init 'octopush_sms_admin_alert_sent' to say we don't have send the alert sms message
                $setting = Mage::getModel("octopushsms/setting")->load('1');
                $alert_level = $setting->getAdminAlert();
                if ($alert_level > 0 && (float) $xml->credit[0] > $alert_level) {
                    $setting->setAdminAlertSent(0);
                    $setting->save();
                } 
//return the balance
                return (float) $xml->credit[0];
            } else if ($xml->error_code == '001') {
// connexion failed
                return '001';
            } else {
                return false;
            }
        }
        return false;
    }

    public function get_balance() {
        $result = $this->get_account();
        return $result;
    }

    /**
     * Get thenews on octopush server
     * @return string|boolean
     */
    public function get_news() {
        Mage::log("octopush-sms - get_news ", Zend_Log::DEBUG, "octopushsms.log");
        $sms = new SMS();
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $sms->set_user_lang(substr($locale, 0, 2));
        $xml = simplexml_load_string($sms->getNews(), 'SimpleXMLElement', LIBXML_NOCDATA);
        Mage::log("octopush-sms - xml ".$xml->asXML(), Zend_Log::DEBUG, "octopushsms.log");
        
        return $xml;
    }

    public function send_trame($id_campaign, $recipients, $txt, $finished) {
        $values = array('{firstname}' => '(ch1)', '{lastname}' => '(ch2)');
        $this->_event = 'sendsmsFree';
        $this->_paid_by_customer = 0;
        $this->_recipient = $recipients;

//if (!empty($this->user_login) && !empty($this->api_key)) {
        $sms = new SMS();
        $sms->set_user_login($this->user_login);
        $sms->set_api_key($this->api_key);
        $sms->set_sms_mode($this->sms_mode);
        $sms->set_sms_type($this->sms_type);

//campaign message
        $sms_text = str_replace(array_keys($values), array_values($values), $this->replace_for_GSM7($txt));
        $sms->set_sms_text($sms_text);
        $sms->set_sms_sender($this->sms_sender);
        $sms->set_option_transactional(1);

        $phones = array();
        $firstnames = array();
        $lastnames = array();
        foreach ($recipients as $recipient) {
            $phones[] = $recipient['phone'];
            if (strpos($txt, '(ch1)') !== false)
                $firstnames[] = $recipient['firstname'];
            if (strpos($txt, '(ch2)') !== false)
                $lastnames[] = $recipient['lastname'];
        }
        $sms->set_sms_recipients($phones);
        $sms->set_recipients_first_names($firstnames);
        $sms->set_recipients_last_names($lastnames);
        $sms->set_user_batch_id($id_campaign);

        $sms->set_finished($finished ? 1 : 0);

//TODO une fois finis on appelle status en boucle toute les minutes jusqu'à avoir statut
        return $sms->sendSMSParts();
    }

    /**
     * Send a sms (depends of the hook and parameters given)
     * @param type $hookName
     * @param type $hookId
     * @param type $params
     */


    public function _is_everything_valid_for_sending() {
        $setting = Mage::getModel("octopushsms/setting")->load('1');
        Mage::log("_is_everything_valid_for_sending()=".$setting->getKey() ." && ". $setting->getSender() ." && ". $setting->getData('admin_phone') ." && ". !empty($this->_phone) ." && ". is_array($this->_phone), Zend_Log::DEBUG, "octopushsms.log");
        return ( $setting->getKey() && $setting->getSender() && $setting->getData('admin_phone') && !empty($this->_phone) && is_array($this->_phone));
    }

    /**
     * Set the phone attribut (convert the phone number to international phone number)
     * @param type $phone
     * @param type $country iso country code (ex. FR)
     * @param type $b_admin
     */
    public function _set_phone($phone = null, $country = null, $b_admin = false) {
        $this->_phone = null;
        if ($b_admin) {
            $setting = Mage::getModel("octopushsms/setting")->load('1');
            $this->_phone = array($setting->getData('admin_phone'));
        } else if (!empty($country)) {
            if (!empty($phone) && !empty($country)) {
                $this->_phone = array($this->convert_phone_to_international($phone, $country));
            }
        }
    }

    /**
     * Send sms
     * @param type $text_to_send the textto send
     * @param type $recipients the recipients
     * @return boolean
     */
    public function _send_sms($sms_text) {
        Mage::log('_send_sms ' . $sms_text . ' phone:' . print_r($this->_phone, true), Zend_Log::DEBUG, "octopushsms.log");
        $sms = new SMS();

        $sms_type = SMS_WORLD; //SMS_STANDARD; // ou encore SMS_STANDARD,SMS_PREMIUM
        $sms_mode = INSTANTANE; // ou encore DIFFERE
        $sms_sender = Mage::getModel('octopushsms/setting')->load('1')->getData('sender');
        $sms->set_user_login($this->user_login);
        $sms->set_api_key($this->api_key);
        $sms->set_sms_mode($sms_mode);
        $sms->set_sms_text($this->replace_for_GSM7($sms_text));
        $sms->set_sms_recipients($this->_phone);
        $sms->set_sms_type($sms_type);
        $sms->set_sms_sender($sms_sender);
        $sms->set_sms_request_id(uniqid());
        $sms->set_option_with_replies(0);
//$sms->set_sms_fields_1(array(''));
//$sms->set_sms_fields_2(array('a'));
        $sms->set_option_transactional(1);
        $sms->set_sender_is_msisdn(0);
//$sms->set_date(2016, 4, 17, 10, 19); // En cas d'envoi différé.
//$sms->set_request_keys('TRS');

        Mage::log('***** _send_sms ' . print_r($sms, true), Zend_Log::DEBUG, "octopushsms.log");
        $xml = $sms->send();
        Mage::log('***** Result ' . $xml, Zend_Log::DEBUG, "octopushsms.log");
        if ($xml = simplexml_load_string($xml)) {
            if ($xml->error_code != '000') {
                Mage::log($this->get_error_SMS($xml->error_code), Zend_Log::ERR, "octopushsms.log");                
            }
        }
        
        //check account and send sms if necessary
        
    }

    private function _set_recipient($customer) {
        $this->_recipients = $customer;
    }

    public function replace_for_GSM7($txt) {
        $search = array('À', 'Á', 'Â', 'Ã', 'È', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ò', 'Ó', 'Ô', 'Õ', 'Ù', 'Ú', 'Û', 'Ý', 'Ÿ', 'á', 'â', 'ã', 'ê', 'ë', 'í', 'î', 'ï', 'ð', 'ó', 'ô', 'õ', 'ú', 'û', 'µ', 'ý', 'ÿ', 'ç', 'Þ', '°', '¨', '^', '«', '»', '|', '\\');
        $replace = array('A', 'A', 'A', 'A', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'Y', 'Y', 'a', 'a', 'a', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'y', 'c', 'y', 'o', '-', '-', '"', '"', 'I', '/');
        return str_replace($search, $replace, $txt);
    }

    public function is_GSM7($txt) {
        if (preg_match("/^[ÀÁÂÃÈÊËÌÍÎÏÐÒÓÔÕÙÚÛÝŸáâãêëíîïðóôõúûµýÿçÞ°{|}~¡£¤¥§¿ÄÅÆÇÉÑÖØÜßàäåæèéìñòöøùü,\.\-!\"#$%&()*+\/:;<=>?@€\[\]\^\w\s\\']*$/u", $txt))
            return true;
        else
            return false;
    }

    public function not_GSM7($txt) {
        return preg_replace("/[ÀÁÂÃÈÊËÌÍÎÏÐÒÓÔÕÙÚÛÝŸáâãêëíîïðóôõúûµýÿçÞ°{|}~¡£¤¥§¿ÄÅÆÇÉÑÖØÜßàäåæèéìñòöøùü,\.\-!\"#$%&()*+\/:;<=>?@€\[\]\^\w\s\\']/u", "", $txt);
    }

    /**
     * Convert phone number to international phone number.
     * 
     * @global type $wpdb
     * @param type $phone
     * @param type $iso_country
     * @param type $prefix
     * @return type
     */
    public function convert_phone_to_international($phone, $iso_country, $prefix = null) {
        $phonePrefixCollection = Mage::getModel('octopushsms/phoneprefix')->getCollection();
        //$phonePrefixCollection->addFieldToFilter('iso_code', array('eq' => $iso_country));

        $phone = preg_replace("/[^+0-9]/", "", $phone);

        if (is_null($prefix)) {
            $prefixModel = $phonePrefixCollection->addFieldToFilter('iso_code', array('eq' => $iso_country))->getFirstItem();
            if (!empty($prefixModel)) {
                $prefix = $prefixModel->getData('prefix');
            }
        }
        if (empty($prefix)) {
            return null;
        } else {
// s'il commence par + il est déjà international
            if (substr($phone, 0, 1) == '+') {
                return $phone;
            }
// s'il commence par 00 on les enlève et on vérifie le code pays pour ajouter le +
            else if (substr($phone, 0, 2) == '00') {
                $phone = substr($phone, 2);
                if (strpos($phone, $prefix) === 0) {
                    return '+' . $phone;
                } else {
                    return null;
                }
            }
// s'il commence par 0, on enlève le 0 et on ajoute le prefix du pays
            else if (substr($phone, 0, 1) == '0') {
                return '+' . $prefix . substr($phone, 1);
            }
// s'il commence par le prefix du pays, on ajoute le +
            else if (strpos($phone, $prefix) === 0) {
                return '+' . $phone;
            } else {
                return '+' . $prefix . $phone;
            }
        }
    }

    /**
     * Validate the campaign.
     * 
     * @param type $id_campaign
     * @param type $time
     * @return type
     */
    public function validate_campaign($ticket, $time) {
        $sms = new SMS();
        $action = 'send';
        $sms->set_user_login($this->user_login);
        $sms->set_api_key($this->api_key);
        $sms->set_user_batch_id($ticket);
        $sms->set_sms_type(SMS_WORLD);

//Be careful to convert in function of the sms shop time GMT +1
        $gmt_offset = Mage::getModel('core/date')->calculateOffset();
        $sms->set_sms_mode(DIFFERE);
        $date_send = date_parse_from_format("Y-m-d H:i:s", $time - $gmt_offset + 60);
        $sms->set_date($date_send['year'], $date_send['month'], $date_send['day'], $date_send['hour'] - $gmt_offset, $date_send['minute']); // En cas d'envoi diffÈrÈ.
        Mage::log("validate_campaign sms:" . print_r($sms, true) . " JSON_ENCODE_SMS: " . json_encode($sms), Zend_Log::DEBUG, "octopushsms.log");
        $xml = $sms->SMSBatchAction($action);
        Mage::log("validate_campaign($ticket, $time) : $xml", Zend_Log::DEBUG, "octopushsms.log");
        //TODO alert if account under a certain level
        //Octopush_Sms_Admin::get_instance()->action_admin_alert();

        return $xml;
    }

    /**
     * Get the campaign status on octopush.
     * 
     * @param type $id_campaign
     * @param type $time
     * @return type
     */
    public function get_campaign_status($id_campaign) {
        $sms = new SMS();
        $action = 'status';
        $sms->set_user_login($this->user_login);
        $sms->set_api_key($this->api_key);
        $sms->set_user_batch_id($id_campaign);
        Mage::log("get_campaign_status:" . print_r($sms, true), Zend_Log::DEBUG, "octopushsms.log");
        $xml = $sms->SMSBatchAction($action);
        Mage::log("get_campaign_status($id_campaign) : $xml", Zend_Log::DEBUG, "octopushsms.log");
        return $xml;
    }

    /**
     * Cancel a campaign on octopus server if it is possible.
     * @param type $id_campaign
     * @return type
     */
    public function cancel_campaign($id_campaign) {
        $sms = new SMS();
        $action = 'delete';
        $sms->set_user_login($this->user_login);
        $sms->set_api_key($this->api_key);
        $sms->set_user_batch_id($id_campaign);
        $xml = $sms->SMSBatchAction($action);
        Mage::log("cancel_campaign($id_campaign) : $xml", Zend_Log::DEBUG, "octopushsms.log");
        return $xml;
    }

    /**
     * Return error message
     * @param type $code
     * @return type
     */
    public function get_error_SMS($code) {
        if (isset($code) && array_key_exists(intval($code), $GLOBALS['errors'])) {
            return $GLOBALS['errors'][intval($code)];
        }
        return __('Error unknown', 'octopush-sms') . " $code";
    }

}
