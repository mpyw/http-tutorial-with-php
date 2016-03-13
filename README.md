# PHPでHTTP入門

Apacheとか`php -S`を使わずにPHP自身で簡易的なHTTPサーバになるやつ．

## 01-simple-client-server

HTTPはまだ使わずにTCPでやりとりするだけです．

### server.php

TCP接続を受理し，「Hello World [`N`]」のように1秒ごとにクライアントに送信します．
合計3回繰り返されます．**1人ずつ順番に処理されます．**

### client.php

TCP接続を要求し，サーバからデータを受信します．

### exec.sh

server.php と client.php をいい感じに自動実行＆自動終了してくれるシェルスクリプトです．  
クライアントは2人分実行します．別にこれに頼らず手動で各PHPスクリプトを実行しても構いません．

## 02-fork-tcp-client-server

01-simple-client-serverの同時処理版です．  

### server.php

TCP接続を受理し，「Hello World [`N`]」のように1秒ごとにクライアントに送信します．
合計3回繰り返されます．**複数人同時に処理されます．**

### client.php

TCP接続を要求し，サーバからデータを受信します．

### exec.sh

server.php と client.php をいい感じに自動実行＆自動終了してくれるシェルスクリプトです．  
クライアントは2人分実行します．別にこれに頼らず手動で各PHPスクリプトを実行しても構いません．

## 03-http-10-server (server.php)

HTTP/1.0に対応したサーバで，assets にあるファイルを返します．  
**TCPコネクションを1ファイルごとに毎回生成します．**

## 04-http-11-server (server.php)

HTTP/1.1に対応したサーバで，assets にあるファイルを返します．  
**TCPコネクションを再利用します．**

<!--

## 05-http-11-streaming-server

HTTP/1.1の`Transfer-Encoding: chunked`に対応したサーバ．  

-->
