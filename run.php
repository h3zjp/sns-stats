<?php

	# エラー表示
	ini_set( 'display_errors', 1 );

	# フォルダパス
	$folder = 'スクリプト設置フォルダのフルパス(/で終わる)を記述';

	# ライブラリ読み込み
	require $folder . 'vendor/autoload.php';
	use Abraham\TwitterOAuth\TwitterOAuth;

	# 旧Twitter API Key
	$twtr_apikey      = 'API Key (Consumer Key)';
	$twtr_apisecret   = 'API Secret (Consumer Secret)';
	$twtr_accesstoken = 'OAuth Access Token';
	$twtr_tokensecret = 'OAuth Access Token Secret';

	# オブジェクトを生成
	$twtr_connection = new TwitterOAuth($twtr_apikey, $twtr_apisecret, $twtr_accesstoken, $twtr_tokensecret);
	$twtr_connection->setApiVersion('2');

	# API実行データを取得
	$request = $twtr_connection->get('users/me', ['user.fields' => 'public_metrics']);

	# ファイル読み取り
	$filer = fopen($folder . 'count.csv', 'r');
	$rcsv = fgetcsv($filer, 1);
	$tweet_past = $rcsv[2];
	$favorite_past = $rcsv[3];
	$list_past = $rcsv[4];
	$follow_past = $rcsv[5];
	$follower_past = $rcsv[6];
	fclose($filer);

	# 変数定義
	$req_date = date('Y-m-d T', $_SERVER['REQUEST_TIME']);
	$tweet_now = $request->data->public_metrics->{'tweet_count'};
	$favorite_now = $request->data->public_metrics->{'like_count'};
	$list_now = $request->data->public_metrics->{'listed_count'};
	$follow_now = $request->data->public_metrics->{'following_count'};;
	$follower_now = $request->data->public_metrics->{'followers_count'};

	# Twitter歴をカウント
	date_default_timezone_set('Asia/Tokyo');
	$t1 = mktime(23, 30, 58, 2, 28, 2016);
	$t2 = time();
	$one_day = 60 * 60 * 24;
	$twit_date = sprintf('%d', ($t2 - $t1) / $one_day);
	$twit_date_form = number_format($twit_date);

	# 取得日, Twitter歴, ツイート数, いいね数, リスト登録数, フォロー, フォロワー
	$wcsv = array($req_date, $twit_date, $tweet_now, $favorite_now, $list_now, $follow_now, $follower_now);

	# ファイル書き込み
	$filew = fopen($folder . 'count.csv', 'w');
	fputcsv($filew, $wcsv);
	fclose($filew);

	# 計算
	$tweet = $tweet_now - $tweet_past;
	$per_tweet = sprintf('%.2f', $tweet_now / $twit_date);
	$tweet_form = number_format($tweet);
	$tweet_now_form = number_format($tweet_now);
	$favorite = $favorite_now - $favorite_past;
	$per_fav = sprintf('%.2f', $favorite_now / $twit_date);
	$favorite_form = number_format($favorite);
	$favorite_now_form = number_format($favorite_now);
	$list = $list_now - $list_past;
	$per_list = sprintf('%.2f', $list_now / $twit_date);
	$follow = $follow_now - $follow_past;
	$follow_form = number_format($follow);
	$follow_now_form = number_format($follow_now);
	$follower = $follower_now - $follower_past;
	$follower_form = number_format($follower);
	$follower_now_form = number_format($follower_now);
	$follow_ratio = sprintf('%.2f', $follow_now / $follower_now);

	# 出力する文章を生成
	$post_data = <<< EOM
【統計】 ({$req_date})

この垢 (4代目) 作成から {$twit_date_form} 日が経過

投稿：{$tweet_now_form} (前日比：{$tweet_form}, {$per_tweet}/日)
いいね：{$favorite_now_form} (前日比：{$favorite_form}, {$per_fav}/日)

フォロー：{$follow_now_form} (前日比：{$follow_form})
フォロワー：{$follower_now_form} (前日比：{$follower_form}, FF比：{$follow_ratio})
#ついったーすたっつ
EOM;

	# 投稿
	$twtr_result = $twtr_connection->post('tweets', ['text' => $post_data], ['jsonPayload' => true]);

?>
