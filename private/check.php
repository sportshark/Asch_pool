<?php
error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
$config = include('../config.php');

$m = new Memcached();
$m->addServer('localhost', 11211);
$lisk_host = $m->get('lisk_host');
$lisk_port = $m->get('lisk_port');

$mysqli=mysqli_connect($config['host'], $config['username'], $config['password'], $config['bdd']) or die(mysqli_error($mysqli));
$df = 0;
$delegate = $config['delegate_address'];
$pool_fee = floatval(str_replace('%', '', $config['pool_fee']));
$pool_fee_payout_address = $config['pool_fee_payout_address'];
$protocol = $config['protocol'];


echo "\nFetching data...\n";
//Retrive Public Key
$ch1 = curl_init($protocol.'://'.$lisk_host.':'.$lisk_port.'/api/accounts?address='.$delegate);                                                                      
curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "GET");                                                                                      
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);     
$result1 = curl_exec($ch1);
$publicKey_json = json_decode($result1, true); 
$publicKey = $publicKey_json['account']['publicKey'];
$pool_balance = $publicKey_json['account']['balance'];
$balanceinlsk_p = floatval($pool_balance/100000000);

$existQuery = "SELECT address,balance FROM miners WHERE balance!='0'";
$existResult = mysqli_query($mysqli,$existQuery)or die("Database Error");
$total = 0;
while ($row=mysqli_fetch_row($existResult)){
	$payer_adr = $row[0];
	$balance = $row[1];
	$balanceinlsk = floatval($balance/100000000);
	echo "\n".$payer_adr.' -> '.$balanceinlsk;
	$total = $total + $balanceinlsk;
}

echo "\nUsers Table:".$total;
echo "\n------Owned:".$balanceinlsk_p;

if ($balanceinlsk_p > $total) {
	echo "\nCorrect - balance valid\n\n";
} else {
	echo "\n!!! Incorrect - balance invalid !!!\n\n";
}
?>
