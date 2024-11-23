<?php
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");



$gatewayModuleName = basename(__FILE__, '.php');
$gatewayParams = getGatewayVariables($gatewayModuleName);
if (!$gatewayParams['type']) {
	die("Module Not Activated");
}
$invoiceid = $_GET['invoiceid'];
$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]);
$results = select_query("tblinvoices", "", array("id" => $invoiceid));
$data = mysql_fetch_array($results);
$db_amount = strtok($data['total'], '.');

if (isset($_POST['Authority']) && isset($_POST['InvoiceID']) && isset($_POST['PaymentStatus']) && $_POST['PaymentStatus'] == "OK") {
	$Authority = $_POST['Authority'];
	$fee = 0;
	checkCbTransID($Authority);


	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://bluepal.ir/webservice/rest/PaymentVerification.php',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{
		  "MerchantID":"' . $gatewayParams["MerchantId"] . '",
		  "Amount":"' . $db_amount * 10 . '",
		  "Authority":"' . $_POST["Authority"] . '"
	  }',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
		),
	));


	$response = curl_exec($curl);
	$response = json_decode($response, JSON_OBJECT_AS_ARRAY);

	curl_close($curl);
	if ($response['Status'] == 101 || $response['Status'] == 100) {
		if ($invoiceid == $response["InvoiceID"]) {
			addInvoicePayment($invoiceid, $Authority, $db_amount, $fee, $gatewayModuleName); # Apply Payment to Invoice: invoiceid,  transactionid, amount paid, fees, modulename
			logTransaction($gatewayModuleName, $_POST, "Successful");
			$url = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceid;
			Header('Location: ' . $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceid);
			die("<script>window.location='$url';</script>");
		} else {
			$url = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceid;
			Header('Location: ' . $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceid);
			die("<script>window.location='$url';</script>");
		}
	}
} else {
	logTransaction($gatewayModuleName, 0, "Unsuccessful");
	$url = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceid;
	Header('Location: ' . $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceid);
	die("<script>window.location='$url';</script>");
}
