<?php

// TCPソケットに使う設定を取得
// デフォルト値は「localhost:8080」
$conf = getenv('HTTPTUTE_TCP_SOCK') ?: 'localhost:8080';

// 時間無制限
set_time_limit(0);

// TCPサーバソケットを生成
$srv = @stream_socket_server("tcp://$conf");
if (!$srv) {
    fwrite(STDERR, error_get_last()['message'] . "\n");
    exit(1);
}
echo "Listening HTTP connection on $conf...\n";

// TCPクライアントソケットを受け入れる
while ($con = stream_socket_accept($srv, -1)) {

    // リクエストヘッダを配列で受け取る
    if (!$lines = read_headers($con)) {
        // 正常に読み取りきれなかったら無視
        continue;
    }

    // リクエストラインを解析
    list($method, $path, $version) = explode(' ', rtrim($lines[0]), 3) + ['', '', ''];

    // ディレクトリトラバーサル攻撃対策
    if (strpos($path, '..') !== false) {
        $path = '';
    }

    if ($method !== 'GET') {
        // GET以外は拒否
        write_close(
            $con,
            'This server supports only GET request',
            '400 Bad Request',
            'text/plain'
        );
    } elseif ($path === '/api/streaming') {
        // ストリーミングAPIにアクセスがきた時
        write_streaming_close($con);
    } elseif (!is_file(__DIR__ . '/../assets' . $path)) {
        // ファイルが見つからない時
        write_close(
            $con,
            'No such file or directory',
            '404 Not Found',
            'text/plain'
        );
    } else {
        // ファイルが見つかった時
        write_close(
            $con,
            fopen(__DIR__ . '/../assets' . $path, 'rb'),
            '200 OK',
            mime_content_type(__DIR__ . '/../assets' . $path)
        );
    }

}

/**
 * ターミナルに読み出しつつ，リクエストヘッダを配列で返す関数
 *
 * @param resource $con TCPクライアントソケット
 * @return array HTTPリクエストヘッダの配列
 */
function read_headers($con) {
    $lines = [];
    while (true) {
        $line = fgets($con);
        if ($line === false) return []; // ブラウザ更新連打対策
        echo $line;
        if ($line === "\r\n") break; // 空行が現れたらそこで終わりとみなす
        $lines[] = $line;
    }
    return $lines;
}

/**
 * ターミナルに書き出しつつ，レスポンスヘッダとレスポンスボディを送信して閉じる関数
 *
 * @param resource $con TCPクライアントソケット
 * @param resource|string $body ファイルポインタまたは文字列
 * @param string $status "200 OK" とか "400 Bad Request" とか
 * @param type $type Content-Type の値． "text/plain" とか "text/html" とか
 */
function write_close($con, $body, $status, $type) {
    // 処理用の一時的な関数を作成して変数に代入
    $write = function ($data) use ($con) {
        if (is_resource($data)) {
            // ファイルポインタのときは内容をそのまま移す
            // ターミナルへの表示は省略する
            echo "…データ…\r\n";
            stream_copy_to_stream($data, $con);
            fwrite($con, "\r\n");
            fclose($data);
        } else {
            // 文字列のときは普通に書き込む
            echo "$data\r\n";
            fwrite($con, "$data\r\n");
        }
    };
    $write("HTTP/1.1 $status");
    $write("Content-Type: $type");
    $write('Connection: close');
    $write('');
    $write($body);
    echo "----------------\r\n\r\n";
    fclose($con);
}

/**
 * ターミナルに書き出しつつ，レスポンスヘッダとレスポンスボディを送信して閉じる関数
 * ストリーミング用に1行ごとのテキストを返します
 *
 * @param resource $con TCPクライアントソケット
 */
function write_streaming_close($con) {
    // 処理用の一時的な関数を作成して変数に代入
    $write = function ($data) use ($con) {
        echo "$data\r\n";
        fwrite($con, "$data\r\n");
    };
    $write_chunk = function ($data) use ($write) {
        // 16進数でこれから送るデータの長さをあらかじめ送信しておく
        $write(base_convert(strlen($data), 10, 16));
        // データ本体を送信
        $write($data);
    };
    $write('HTTP/1.1 200 OK');
    $write('Content-Type: text/event-strem');
    $write('Connection: close');
    $write('Transfer-Encoding: chunked');
    $write('');
    for ($i = 0; $i < 3; ++$i) {
        $write_chunk("Hello, World [$i]\n");
        sleep(1);
    }
    $write_chunk('');
    echo "----------------\r\n\r\n";
    fclose($con);
}
