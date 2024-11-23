<?php
include("../../includes/functions.php");
include("../../includes/gatewayfunctions.php");
include("../../includes/invoicefunctions.php");
function bluepalWhmcsFrs_config()
{
	$configarray = array(
		"FriendlyName" => array("Type" => "System", "Value" => "درگاه پرداخت بلوپال"),
		"MerchantId" => array("FriendlyName" => "MerchantId", "Type" => "text", "Size" => "100",),
	);
	return $configarray;
}
function bluepalWhmcsFrs_MetaData()
{
	return array(
		'DisplayName' => 'ماژول پرداخت آنلاین بلو پال',
		'APIVersion'  => '1.0.0',
	);
}
function bluepalWhmcsFrs_link($params)
{
	$MerchantId = $params["MerchantId"];
	$invoiceid = $params['invoiceid'];
	$amount = $params['amount'];
	$amount = strtok($amount, '.');
	$callBackUrl = ($params['systemurl'] . 'modules/gateways/callback/bluepalWhmcsFrs.php?invoiceid=' . $invoiceid);

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://bluepal.ir/webservice/rest/PaymentRequest.php',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{
    "MerchantID":"' . $MerchantId . '",
    "Amount":"' . (int)$amount * 10 . '",
    "CallbackURL":"' . $callBackUrl . '",
	"InvoiceID":"' . $invoiceid . '"
}',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
		),
	));
	$response = curl_exec($curl);
	$response_json = json_decode($response, true);
	if (!($response_json["Status"] == 100)) {
		return $response_json["Message"];
	}
	curl_close($curl);
	@session_start();
	$_SESSION['Authority'] = $response_json["Authority"];
	$code =  '<a href="' . $response_json["PaymentUrl"] . '" >پرداخت</a>';
	return $code;
}
function logger($response)
{
	$serializedArray = json_encode($response);
	$message = $serializedArray;
	$logFile = __DIR__ . '/custom_log.json';
	$current = $message;
	file_put_contents($logFile, $current);
}
