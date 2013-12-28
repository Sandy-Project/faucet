<?php

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    exit("Sorry, this version of MiniFaucet will only run on PHP version 5.3 or greater!\n");
}

require_once 'config.php';
require_once 'coindata.php';
require_once 'recaptchalib.php';
require_once 'validator.php';
require_once 'Coinbase/Coinbase.php';
require_once 'jsonRPCClient.php';
require_once 'Slim/Slim.php';

define("COIN_NAME", getCoinName($coinType));
define("UP_COIN_NAME", ucfirst(COIN_NAME));
define("SUB_UNIT", getSubunitDivider($coinType));
define("SUB_UNIT_NAME", getSubunitName($coinType));

function urlFor($name, $params = array(), $appName = 'default')
{
    return \Slim\Slim::getInstance($appName)->urlFor($name, $params);
}

function getAd($arr)
{
    return !empty($arr) ? $arr[rand(0, count($arr)-1)] : "banner here\n";
}

function relative_time($date)
{
    $diff = time() - $date;
    $poststr = $diff > 0 ? " ago" : "";
    $adiff = abs($diff);
    if ($adiff<60) {
        return $adiff . " second" . plural($adiff) . $poststr;
    }
    if ($adiff<3600) { // 60*60
            return round($adiff/60) . " minute" . plural($adiff) . $poststr;
    }
    if ($adiff<86400) { // 24*60*60
            return round($adiff/3600) . " hour" . plural($adiff) . $poststr;
    }
    if ($adiff<604800) { // 7*24*60*60
            return round($adiff/86400) . " day" . plural($adiff) . $poststr;
    }
    if ($adiff<2419200) { // 4*7*24*60*60
            return $adiff . " week" . plural($adiff) . $poststr;
    }
    return "on " . date("F j, Y", strtotime($date));
}

function plural($a)
{
        return ($a > 1 ? "s" : "");
}

function checkaddress($address)
{
    global $allowEmail, $allowCoin, $coinType;
    if ($allowCoin && determineValidity($address, $coinType)) {
        return true;
    }
    if ($allowEmail && (filter_var($address, FILTER_VALIDATE_EMAIL) !== false)) {
        return true;
    }
    return false;
}

function getIP()
{
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } elseif (getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = "UNKNOWN";
    }
    return $ip;
}

function getserverbalance($force = false)
{
    global $apiKey, $guid, $rpchost;
    if (!$force) {
        // we store the server balance in sql with a spec address called 'SERVERBALANCE'
        $balance_sql = "SELECT balance FROM balances WHERE email='SERVERBALANCE' ";
        $balance_sql .= "AND totalbalance > ".(time() - 1800).";";
        $balance_query = sql_query($balance_sql);
        if ($balance_query->num_rows) {
            $balance = fetch_one($balance_query);
            return $balance;
        }
    }
    try {
        if (!empty($apiKey)) {
            $balance = getCoinbaseBalance();
        } elseif (!empty($guid)) {
            $balance = getBlockchainBalance();
        } elseif (!empty($rpchost)) {
            $balance = getBitcoindBalance();
        } else {
            $balance = -1;
        }
        $date = time();
        $insert_sql = "INSERT INTO balances(balance, totalbalance, email, referredby) ";
        $insert_sql .= "VALUES($balance, '$date', 'SERVERBALANCE', 0) ON DUPLICATE KEY ";
        $insert_sql .= "UPDATE balance = $balance, totalbalance = '$date';";
        sql_query($insert_sql);
        return $balance;
    } catch (Exception $e) {
        return 0;
    }
}

function getCoinbaseBalance()
{
    global $apiKey;
    $coinbase = new Coinbase($apiKey);
    return $coinbase->getBalance() * SUB_UNIT;
}

function getBlockchainBalance()
{
    global $guid, $firstpassword;
    $url = "https://blockchain.info/merchant/$guid/balance?password=$firstpassword";
    return json_decode(file_get_contents($url))->balance;
}

function getBitcoindBalance()
{
    global $rpchost, $rpcssl, $rpcport, $rpcuser, $rpcpassword;
    $bitcoin = new jsonRPCClient(sprintf('http%s://%s:%s@%s:%d/', $rpcssl ? "s" : "", $rpcuser, $rpcpassword, $rpchost, $rpcport));
    return $bitcoin->getbalance() * SUB_UNIT;
}

class NoCashException extends Exception
{
}

function sendMoney($address, $balance)
{
    global $apiKey, $guid, $rpchost;
    if (!empty($apiKey)) {
        return sendCoinbaseMoney($address, $balance);
    } elseif (!empty($guid)) {
        return sendBlockchainMoney($address, $balance);
    } elseif (!empty($rpchost)) {
        return sendBitcoindMoney($address, $balance);
    } else {
        throw new Exception("The site doesnt set wallet provider");
    }
}

function sendCoinbaseMoney($address, $balance)
{
    global $apiKey, $cashoutMessage, $fee;
    $balance = $balance / SUB_UNIT;
    try {
        $coinbase = new Coinbase($apiKey);
        $response = $coinbase->sendMoney($address, sprintf("%.8f", $balance), $cashoutMessage, $fee > 0 ? ($fee / SUB_UNIT) : null);
    } catch (Exception $e) {
        $response = $e->getMessage();
        if (strpos($response, "You don't have that much") !== false) {
            throw new NoCashException($response, 0, $e);
        } else {
            throw new Exception($response, 0, $e);
        }
    }
    return $response;
}

function sendBlockchainMoney($address, $balance)
{
    global $guid, $firstpassword, $secondpassword, $cashoutMessage, $fee;

    $url = "https://blockchain.info/merchant/$guid/payment?";
    $url .= "password=$firstpassword&second_password=$secondpassword&to=$address&amount=$balance&";
    if ($fee >= 50000) {
        $url .= "fee=$fee";
    }
    $url .= "note=" . urlencode($cashoutMessage);
    $response = json_decode(file_get_contents($url));
    if (isset($response->error)) {
        if ($response->error == 'No free outputs to spend') {
            throw new NoCashException();
        } else {
            throw new Exception($response->error);
        }
    }
    return $response;
}

function sendBitcoindMoney($address, $balance)
{
    global $rpchost, $rpcssl, $rpcport, $rpcuser, $rpcpassword, $cashoutMessage, $fee;
    $balance = $balance / SUB_UNIT;
    try {
        $bitcoin = new jsonRPCClient(sprintf('http%s://%s:%s@%s:%d/', $rpcssl ? "s" : "", $rpcuser, $rpcpassword, $rpchost, $rpcport));
        if ($fee > 0) {
            $bitcoin->settxfee(round($fee / SUB_UNIT, 8));
        }
        $response = $bitcoin->sendtoaddress($address, $balance, $cashoutMessage);
    } catch (Exception $e) {
        $response = $e->getMessage();
        if (strpos($response, "Insufficient funds") !== false) {
            throw new NoCashException($response, 0, $e);
        } else {
            throw new Exception($response, 0, $e);
        }
    }
    return $response;
}

function sql_query($sql)
{
    global $mysqli;
    return $mysqli->query($sql);
}

function fetch_row($query)
{
    return $query->fetch_row();
}

function fetch_all($query, $resulttype = MYSQLI_NUM)
{
    if (method_exists($query, 'fetch_all')) { # Compatibility layer with PHP < 5.3
        $res = $query->fetch_all($resulttype);
    } else {
        for ($res = array(); $tmp = $query->fetch_array($resulttype);) {
            $res[] = $tmp;
        }
    }
    return $res;
}

function fetch_one($query)
{
    $row = $query->fetch_row();
    return $row[0];
}
