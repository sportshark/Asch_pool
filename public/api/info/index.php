<?php
error_reporting(error_reporting() & ~E_NOTICE);
$config = include('../../../config.php');
$delegate = $config['delegate_address'];
$protocol = $config['protocol'];
$m = new Memcached();
$m->addServer('localhost', 11211);
$lisk_host = $m->get('lisk_host');
$lisk_port = $m->get('lisk_port');
$mysqli=mysqli_connect($config['host'], $config['username'], $config['password'], $config['bdd']) or die("Database Error");
$task = "SELECT count(1) FROM blocks";
$response = mysqli_query($mysqli,$task)or die("Database Error");
$row = mysqli_fetch_row($response);
$forged_blocks = $row[0];
//Retrive Public Key
$ch1 = curl_init($protocol.'://'.$lisk_host.':'.$lisk_port.'/api/accounts?address='.$delegate);                                                                      
curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "GET");                                                                                      
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);     
$result1 = curl_exec($ch1);
$publicKey_json = json_decode($result1, true); 
$publicKey = $publicKey_json['account']['publicKey'];
$pool_balance = $publicKey_json['account']['balance'];
$username = $publicKey_json['account']['username'];
$balanceinlsk_p = floatval($pool_balance/100000000);

//get forging delegate info
$ch1 = curl_init($protocol.'://'.$lisk_host.':'.$lisk_port.'/api/delegates/get/?publicKey='.$publicKey);
curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "GET");                                                                                      
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);     
$result1 = curl_exec($ch1);
$d_data = json_decode($result1, true); 
$d_data = $d_data['delegate'];
$rank = $d_data['rate'];
$approval = $d_data['approval'];
$productivity = $d_data['productivity'];
$missedblocks = $d_data['missedblocks'];

$last_update = file_get_contents('../../index.html');
$tmp = explode('<p style="text-align:right">', $last_update);
$last_update = explode('</p>', $tmp[1])[0];

$pool_balance_array = array('lsk' => $balanceinlsk_p, 'raw' => $pool_balance);
$blocks = array('forged' => (int)$forged_blocks, 'missed' => $missedblocks);

$response = array('pool_fee' => $config['pool_fee'],
				  'delegate_address' => $delegate,
				  'payout_threshold' => $config['payout_threshold'],
				  'fixed_withdraw_fee' => $config['fixed_withdraw_fee'],
				  'withdraw_interval_in_sec' => $config['withdraw_interval_in_sec'],
				  'publicKey' => $publicKey,
				  'pool_balance' => $pool_balance_array,
				  'rank' => $rank,
				  'approval' => $approval,
				  'productivity' => $productivity,
				  'blocks' => $blocks,
				  'updated' => $last_update,
				  'success' => true);
die(json_encode($response));
?>
