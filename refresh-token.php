<?php

	# エラー表示
	ini_set( 'display_errors', 1 );

	$endpoint    = 'https://bsky.social/xrpc/com.atproto.server.refreshSession';
	$access_csv  = 'スクリプト設置フォルダのフルパス//accessJwt.csv';
	$refresh_csv = 'スクリプト設置フォルダのフルパス//refreshJwt.csv';

	$filer = fopen($refresh_csv, 'r');
	$rcsv = fgetcsv($filer);
	$refreshJwt = $rcsv[0];
	fclose($filer);

	$ch = curl_init($endpoint);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $refreshJwt));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$json = mb_convert_encoding($response, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$result = json_decode($json, true);

	$accessJwt = $result['accessJwt'];
	$refreshJwt = $result['refreshJwt'];

	file_put_contents($access_csv, $accessJwt, LOCK_EX);
	file_put_contents($refresh_csv, $refreshJwt, LOCK_EX);

?>
