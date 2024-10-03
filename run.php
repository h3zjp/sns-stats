<?php

	# エラー表示
	ini_set( 'display_errors', 1 );

	# Endpoint
	$endpoint1 = 'https://bsky.social/xrpc/app.bsky.actor.getProfile';
  # https://github.com/mattn/bsky を使う前提のサンプル
	$endpoint2 = 'http://127.0.0.1:10010/post';

	# ファイルパス
	$access_csv = 'スクリプト設置フォルダのフルパス/accessJwt.csv';
	$file = 'スクリプト設置フォルダのフルパス/count.csv';

	# 対象DID
	$actor = 'did:plc:4xotibizlnzg5b3vljqpsvp3';

	# アクセストークン読み取り
	$filer1 = fopen($access_csv, 'r');
	$rcsv1 = fgetcsv($filer1);
	$accessJwt = $rcsv1[0];
	fclose($filer1);

	# 取得
	$ch = curl_init($endpoint1 . '?actor=' . $actor);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessJwt));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$json = mb_convert_encoding($response, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$result = json_decode($json, true);

	# ファイル読み取り
	$filer2 = fopen($file, 'r');
	$rcsv2 = fgetcsv($filer2);
	$post_past = $rcsv2[2];
	$follow_past = $rcsv2[3];
	$follower_past = $rcsv2[4];
	fclose($filer2);

	# 変数定義
	$req_date = date('Y-m-d T', $_SERVER['REQUEST_TIME']);
	$post_now = $result['postsCount'];
	$follow_now = $result['followsCount'];
	$follower_now = $result['followersCount'];

	# Bluesky歴をカウント
	date_default_timezone_set('Asia/Tokyo');
	$t1 = mktime(12, 17, 4, 2, 20, 2024);
	$t2 = time();
	$one_day = 60 * 60 * 24;
	$bsky_date = sprintf('%d', ($t2 - $t1) / $one_day);
	$bsky_date_form = number_format($bsky_date);

	# 取得日, Bluesky歴, 投稿数, フォロー数, フォロワー数
	$wcsv = array($req_date, $bsky_date, $post_now, $follow_now, $follower_now);

	# ファイル書き込み
	$filew = fopen($file, 'w');
	fputcsv($filew, $wcsv);
	fclose($filew);

	# 計算
	$post = $post_now - $post_past;
	$per_post = sprintf('%.2f', $post_now / $bsky_date);
	$post_form = number_format($post);
	$post_now_form = number_format($post_now);
	$follow = $follow_now - $follow_past;
	$follow_form = number_format($follow);
	$follow_now_form = number_format($follow_now);
	$follower = $follower_now - $follower_past;
	$follower_form = number_format($follower);
	$follower_now_form = number_format($follower_now);
	$follow_ratio = sprintf('%.2f', $follow_now / $follower_now);

	# 文章整形
	$post_data = <<< EOM
【Bluesky Stats】 ({$req_date})

この垢 (3代目) 作成から {$bsky_date_form} 日が経過

投稿：{$post_now_form} (前日比：{$post_form}, {$per_post}/日)

フォロー：{$follow_now_form} (前日比：{$follow_form})
フォロワー：{$follower_now_form} (前日比：{$follower_form}, FF比：{$follow_ratio})
#bsky_stats
EOM;

	# 投稿
	$data = [
		'note' => $post_data
	];

	$json_data = json_encode($data);

	$ch = curl_init($endpoint2);
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
