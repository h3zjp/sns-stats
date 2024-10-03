<?php

	# エラー表示
	ini_set( 'display_errors', 1 );

	# 送信先URL
	# https://github.com/mattn/algia を使う前提のサンプル
	$put_url = 'http://127.0.0.1:10000/post';

	# フォルダパス
	$folder = 'スクリプト設置フォルダのフルパス(/で終わる)を記述';

	# SQL取得
	$sql1   = "SELECT encode(event_pubkey::bytea, 'hex'), COUNT(*) AS event_count FROM events WHERE encode(event_pubkey::bytea, 'hex') = 'bc4520d31c7597810e35ec21dab851eb8d617f22f3c7d67ac2de37b9b7275a1b' AND first_seen >= '2023-09-14 20:52:38' AND first_seen < '" . date('Y-m-d') . " 00:00:00' AND event_kind = 1 GROUP BY event_pubkey ORDER BY event_count DESC";
	$sql6   = "SELECT encode(event_pubkey::bytea, 'hex'), COUNT(*) AS event_count FROM events WHERE encode(event_pubkey::bytea, 'hex') = 'bc4520d31c7597810e35ec21dab851eb8d617f22f3c7d67ac2de37b9b7275a1b' AND first_seen >= '2023-09-14 20:52:38' AND first_seen < '" . date('Y-m-d') . " 00:00:00' AND event_kind = 6 GROUP BY event_pubkey ORDER BY event_count DESC";
	$sql7   = "SELECT encode(event_pubkey::bytea, 'hex'), COUNT(*) AS event_count FROM events WHERE encode(event_pubkey::bytea, 'hex') = 'bc4520d31c7597810e35ec21dab851eb8d617f22f3c7d67ac2de37b9b7275a1b' AND first_seen >= '2023-09-14 20:52:38' AND first_seen < '" . date('Y-m-d') . " 00:00:00' AND event_kind = 7 GROUP BY event_pubkey ORDER BY event_count DESC";
	$pgcon = pg_connect("host=127.0.0.1 port=5432 dbname=nostr user=nostr password=password");
	$res1 = pg_query($pgcon, $sql1);
	$res6 = pg_query($pgcon, $sql6);
	$res7 = pg_query($pgcon, $sql7);
	$record1 = pg_fetch_all($res1);
	$record6 = pg_fetch_all($res6);
	$record7 = pg_fetch_all($res7);
	pg_close($pgcon);

	# ファイル読み取り
	$filer = fopen($folder . 'count.csv', 'r');
	$rcsv = fgetcsv($filer);
	$kind1_past = $rcsv[2];
	$kind6_past = $rcsv[3];
	$kind7_past = $rcsv[4];
	fclose($filer);

	# 変数定義
	$req_date = date('Y-m-d T', $_SERVER['REQUEST_TIME']);
	$kind1_now = $record1[0]['event_count'];
	$kind6_now = $record6[0]['event_count'];
	$kind7_now = $record7[0]['event_count'];

	# Nostr歴をカウント
	date_default_timezone_set('Asia/Tokyo');
	$t1 = mktime(15, 47, 46, 2, 19, 2024);
	$t2 = time();
	$one_day = 60 * 60 * 24;
	$nostr_date = sprintf('%d', ($t2 - $t1) / $one_day);
	$nostr_date_form = number_format($nostr_date);

	# 取得日, Nostr歴, 投稿数, リポスト数, リアクション数
	$wcsv = array($req_date, $nostr_date, $kind1_now, $kind6_now, $kind7_now);

	# ファイル書き込み
	$filew = fopen($folder . 'count.csv', 'w');
	fputcsv($filew, $wcsv);
	fclose($filew);

	# 計算
	$kind1 = $kind1_now - $kind1_past;
	$per_kind1 = sprintf('%.2f', $kind1_now / $nostr_date);
	$kind1_form = number_format($kind1);
	$kind1_now_form = number_format($kind1_now);
	$kind6 = $kind6_now - $kind6_past;
	$per_kind6 = sprintf('%.2f', $kind6_now / $nostr_date);
	$kind6_form = number_format($kind6);
	$kind6_now_form = number_format($kind6_now);
	$kind7 = $kind7_now - $kind7_past;
	$per_kind7 = sprintf('%.2f', $kind7_now / $nostr_date);
	$kind7_form = number_format($kind7);
	$kind7_now_form = number_format($kind7_now);

	# 文章整形
	$post_data = <<< EOM
【Nostr Stats】 ({$req_date})

この垢 (3代目) 作成から {$nostr_date_form} 日が経過

Post (kind 1)：{$kind1_now_form} (前日比：{$kind1_form}, {$per_kind1}/日)

Repost (kind 6)：{$kind6_now_form} (前日比：{$kind6_form}, {$per_kind6}/日)
Reaction (kind 7)：{$kind7_now_form} (前日比：{$kind7_form}, {$per_kind7}/日)
#nostr_stats
EOM;

	# 投稿
	$data = [
		'note' => $post_data
	];

	$json_data = json_encode($data);

	$ch = curl_init($put_url);
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
