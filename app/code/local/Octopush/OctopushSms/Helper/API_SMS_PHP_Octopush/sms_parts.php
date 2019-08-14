<?php
// Exemple de envoi instantane de SMS

require('sms.inc.php');

$user_login = '************@********';
$api_key = '****************'; 

$sms_recipients_1 = array('0600000000');
$sms_recipients_2 = array('0600000001');
$sms_recipients_3 = array('0600000002');
$sms_recipients_n = array('0600000003');
$sms_text = 'test de campagne compositée avec un long sms. test de campagne composite avec un long sms. test de campagne composite avec un long sms. test de campagne composite avec un long sms. test de campagne composite avec un long sms. STOP au XXXXX';

$sms_type = SMS_PREMIUM; // ou encore SMS_STANDARD,SMS_WORLD
$sms_mode = INSTANTANE; // ou encore DIFFERE
$sms_sender = 'XXXXX';
$user_batch_id = '5222erz98tre3ffe' . rand(1, 10000);
echo 'user_batch_id created : ' . $user_batch_id . '<br />';

$sms = new SMS();

$sms->set_user_login($user_login);
$sms->set_api_key($api_key);
$sms->set_sms_mode($sms_mode);
$sms->set_sms_text($sms_text);
$sms->set_sms_recipients($sms_recipients_1);
$sms->set_sms_type($sms_type);
$sms->set_sms_sender($sms_sender);
$sms->set_user_batch_id($user_batch_id);
$sms->set_finished(0);

$xml1 = $sms->sendSMSParts();
$sms->set_sms_recipients($sms_recipients_2);
$xml2 = $sms->sendSMSParts();
$sms->set_sms_recipients($sms_recipients_3);
$xml3 = $sms->sendSMSParts();
$sms->set_sms_recipients($sms_recipients_n);
$sms->set_finished(1);
$xml4 = $sms->sendSMSParts();

//echo $xml4;
//echo '<br />';
echo '<textarea style="width:600px;height:600px;">' . $xml4 . '</textarea>';
?>