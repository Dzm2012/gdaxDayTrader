<?php
$secret = "";
$publicKey = "";
$timestamp = "";
$passPhrase = "";
runCoinBase();




function runCoinBase(){
  global $publicKey,$passPhrase,$signature,$timestamp;

  $myMony = 0;
  $myCoin = .2;
  $lastBuy = 97.00;
  $lastSell = 0;
  $threshHold = .02;
  $profit = 0;

  $ext="/products/LTC-USD/ticker";
  $buy = false;

  $lastMarketPrice = 0;
  while(true){
    $signature = signature($ext);
    $obj = json_decode(sendRequest($publicKey,$passPhrase,$signature,$timestamp,$ext), true);
    if($buy){
      $mode = "Buy";
    }
    else{
      $mode="Sell";
    }

    if(!isset($obj['price'])){
      continue;
    }

    if($lastMarketPrice != $obj['price']){
      $lastMarketPrice = $obj['price'];
      formatOutput($myMony,$myCoin,$lastBuy,$lastSell,$profit,$mode,$obj['price']);
    }
    /*
    Buy when
    Last sell > (current price + transaction fees + profit margin)
    transaction fees = current money * .003
    coin = (current money - transaction fees) / current price
    */

    /*
    Sell when
    Last Buy < (current price - transaction fees - profit margin)
    transaction fees = (current price * my coins) *.003
    money = (coins * current price) - transaction fees
    */

    // Price + transaction fee
    $current_price = $obj['price'] + ($obj['price'] * .003);

    if($buy){
      if (($lastSell - $current_price) > $threshHold) {
        $lastBuy = $current_price;
        $myCoin = ($myMony - $current_price)/$current_price;
        $myMony = $myMony - $current_price;
        $buy = false;
        echo "\033[31m";
        formatOutput($myMony,$myCoin,$lastBuy,$lastSell,$profit,$mode,$obj['price']);
        echo "\033[0m";
      }
    }
    else{
      if (($current_price - $lastBuy) > $threshHold) {
        $lastSell = $current_price;
        $myMony = $current_price;
        $myCoin = 0;
        $buy = true;
        $profit += $lastSell - $lastBuy;
        echo "\033[32m";
        formatOutput($myMony,$myCoin,$lastBuy,$lastSell,$profit,$mode,$obj['price']);
        echo "\033[0m";
      }
    }
    sleep(1);
  }
}

function formatOutput($myMony,$myCoin,$lastBuy,$lastSell,$profit, $mode,$marketPrice = 0){
  echo PHP_EOL."******************************************";
  echo PHP_EOL."My Money: ".$myMony;
  echo PHP_EOL."My Coins: ".$myCoin;
  echo PHP_EOL."My Last Buy: ".$lastBuy;
  echo PHP_EOL."My Last Sell: ".$lastSell;
  echo PHP_EOL."My Profit: ".$profit;
  echo PHP_EOL."Current Market Price: ".$marketPrice;
  echo PHP_EOL."Mode: ".$mode;
  echo PHP_EOL."******************************************".PHP_EOL;
}

function signature($request_path='', $body='', $timestamp=false, $method='GET') {
  global $secret, $timestamp;
    $body = is_array($body) ? json_encode($body) : $body;
    $timestamp = $timestamp ? $timestamp : time();
    $timeStamp = $timestamp;
    $what = $timestamp.$method.$request_path.$body;
    return base64_encode(hash_hmac("sha256", $what, base64_decode($secret), true));
}

function sendRequest($KEY,$PASS,$SIGN,$TIME,$EXT){
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.gdax.com".$EXT,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "CB-ACCESS-KEY: ".$KEY,
      "CB-ACCESS-PASSPHRASE: ".$PASS,
      "CB-ACCESS-SIGN: ".$SIGN,
      "CB-ACCESS-TIMESTAMP: ".$TIME,
      "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    return $response;
  }
}


?>