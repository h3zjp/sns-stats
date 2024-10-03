<?php

	# エラー表示
	ini_set( 'display_errors', 1 );

	# 送信先URL
	$url_domain = 'msky.h3z.jp';

	# ファイルパス
	$file = '/スクリプト設置フォルダのフルパス/count.csv';

	# 取得
	$geturl = 'https://' . $url_domain . '/api/users/show';
	$ch = curl_init($geturl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("username" => "h3zjp")));
	$response = curl_exec($ch);
	curl_close($ch);
	$json = mb_convert_encoding($response, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$result = json_decode($json, true);

	# ファイル読み取り
	$filer = fopen($file, 'r');
	$rcsv = fgetcsv($filer);
	$note_past = $rcsv[2];
	$follow_past = $rcsv[3];
	$follower_past = $rcsv[4];
	fclose($filer);

	# 変数定義
	$req_date = date('Y-m-d T', $_SERVER['REQUEST_TIME']);
	$note_now = $result['notesCount'];
	$follow_now = $result['followingCount'];
	$follower_now = $result['followersCount'];

	# みすきー歴をカウント
	date_default_timezone_set('Asia/Tokyo');
	$t1 = mktime(0, 52, 39, 9, 9, 2022);
	$t2 = time();
	$one_day = 60 * 60 * 24;
	$msky_date = sprintf('%d', ($t2 - $t1) / $one_day);
	$msky_date_form = number_format($msky_date);

	# 取得日, みすきー歴, 投稿数, フォロー数, フォロワー数
	$wcsv = array($req_date, $msky_date, $note_now, $follow_now, $follower_now);

	# ファイル書き込み
	$filew = fopen($file, 'w');
	fputcsv($filew, $wcsv);
	fclose($filew);

	# 計算
	$note = $note_now - $note_past;
	$per_note = sprintf('%.2f', $note_now / $msky_date);
	$note_form = number_format($note);
	$note_now_form = number_format($note_now);
	$follow = $follow_now - $follow_past;
	$follow_form = number_format($follow);
	$follow_now_form = number_format($follow_now);
	$follower = $follower_now - $follower_past;
	$follower_form = number_format($follower);
	$follower_now_form = number_format($follower_now);
	$follow_ratio = sprintf('%.2f', $follow_now / $follower_now);

	# 文章整形
	$post_data = <<< EOM
【Misskey Stats】 ({$req_date})

この垢 (3代目) 作成から {$msky_date_form} 日が経過

投稿：{$note_now_form} (前日比：{$note_form}, {$per_note}/日)

フォロー：{$follow_now_form} (前日比：{$follow_form})
フォロワー：{$follower_now_form} (前日比：{$follower_form}, FF比：{$follow_ratio})
#みすきーすたっつ
EOM;

	# 投稿
	$posturl = 'https://' . $url_domain . '/api/notes/create';
	$data = [
			'i' => 'Misskey Access Token',
			'text' => $post_data,
			'visibility' => 'public'
	];

	$json_data = json_encode($data);

	$ch = curl_init($posturl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	curl_exec($ch);
	curl_close($ch);

?>
