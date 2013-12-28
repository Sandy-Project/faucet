<?php

require 'core.php';

session_start();

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$checkaddress = function ($app, $need = true) {
    return function () use ($app, $need) {
        if ($need) {
            if (empty($_SESSION['address'])) {
                $app->redirect($app->urlFor('root'));
            }
        } else {
            if (!empty($_SESSION['address'])) {
                $app->redirect($app->urlFor('faucet'));
            }
        }
    };
};

$checkclaim = function ($app) {
    return function () use ($app) {
        global $dispenseTime, $recaptchaPub;
        $address = $_SESSION['address'];
        $ip = getIP();
        $sql = "SELECT dispensed FROM dispenses WHERE email='$address' OR ip='$ip' ";
        $sql .= "ORDER BY id DESC LIMIT 1";
        $lastclaim_query = sql_query($sql);
        $canclaim = true;

        if ($lastclaim_query->num_rows) {
            $lastclaim = fetch_one($lastclaim_query);
            $lastclaim = strtotime($lastclaim);
            if ($lastclaim + $dispenseTime > time()) {
                $canclaim = false;
                $app->view()->setData('nextclaim', relative_time($lastclaim + $dispenseTime));
            }
        }

        $app->view()->setData('canclaim', $canclaim);
        if ($canclaim) {
            $app->view()->setData('recaptcha', recaptcha_get_html($recaptchaPub));
        }
    };
};

$app->hook('slim.before.dispatch', function () use ($app) {
    global $siteName, $squareAds, $textAds, $bannerAds, $rewards, $links;
    global $cashout;
    $address = null;
    if (isset($_SESSION['address'])) {
        $address = $_SESSION['address'];
    }

    $flash = $app->view()->getData('flash');

    $error = '';
    if (isset($flash['error'])) {
        $error = $flash['error'];
    }
    $success = '';
    if (isset($flash['success'])) {
        $success = $flash['success'];
    }

    $app->view()->setData('success', $success);
    $app->view()->setData('error', $error);
    $app->view()->setData('address', $address);
    $app->view()->setData('siteName', $siteName);
    $app->view()->setData('squareAds', $squareAds);
    $app->view()->setData('textAds', $textAds);
    $app->view()->setData('bannerAds', $bannerAds);
    $app->view()->setData('rewards', $rewards);
    $app->view()->setData('links', $links);
    $app->view()->setData('cashout', $cashout);
    $app->view()->setData('isAdmin', false);
});

$app->get("/", $checkaddress($app, false), function () use ($app) {
    global $minReward, $maxReward, $dispenseTimeText, $apiKey, $guid;
    global $allowEmail, $allowCoin;
    $id = $app->request()->get('id');
    if (!is_null($id) && is_numeric($id)) {
        $_SESSION['referer'] = $id;
    }

    if (!empty($apiKey)) {
        $app->view()->setData('wallet', "<a href='https://coinbase.com'>Powered by Coinbase</a>");
    } elseif (!empty($guid)) {
        $app->view()->setData('wallet', "<a href='http://earthchain.info/'>Powered by Earthchain.info</a>");
    }

    $addr = array();
    if ($allowCoin) {
        $addr[] = COIN_NAME;
    }
    if ($allowEmail) {
        $addr[] = "email";
    }
    $app->view()->setData('addressType', implode("/", $addr));
    $app->view()->setData('minReward', $minReward);
    $app->view()->setData('maxReward', $maxReward);
    $app->view()->setData('dispenseTimeText', $dispenseTimeText);
    $app->render('main.php', array('title' => 'Home'));
})->name('root');

$app->get("/about", function () use ($app) {
    $app->render('about.php', array('title' => 'About'));
})->name('about');

$checkadmin = function ($app) {
    return function () use ($app) {
        $app->view()->setData('isAdmin', isset($_SESSION['isadmin']) ? $_SESSION['isadmin'] : false);
    };
};

$app->get("/admin(/:cmd)", $checkadmin($app), function ($cmd = null) use ($app) {
    global $recaptchaPub, $fee;

/*
    if (($cmdget = $app->request()->get('cmd')) != null) {
        $cmd = $cmdget;
    }
*/
    $flash = $app->view()->getData('flash');
    $isadmin = $app->view()->getData('isAdmin');
    switch ($cmd) {
        default:
defaultlabel:
            if (!isset($_SESSION['isadmin'])) {
                $app->view()->setData('recaptcha', recaptcha_get_html($recaptchaPub));
            }
            $app->view()->setData('serverbalance', number_format(getserverbalance()));
            $app->render('admin.php', array('title' => 'Admin'));
    }
})->name('admin');

$app->post("/admin", $checkadmin($app), function () use ($app) {
    global $adminSeccode, $recaptchaPrv;
    $isadmin = $app->view()->getData('isAdmin');
    $cmd = $app->request()->post('cmd');
    switch ($cmd) {
        case "updatebalance":
            if (!$isadmin) {
                goto defaultlabel;
            }
            $balance = getserverbalance(true);
            if ($balance > 0) {
                $app->flash('success', "Balance is updated");
            } else {
                $app->flash('error', "Balance is not updated or balance is empty");
            }
            break;
        case "logout":
            unset($_SESSION['isadmin']);
            break;
        case "login":
            $seccode = $app->request()->post('seccode');
            if (!empty($adminSeccode) && $seccode === $adminSeccode) {
                $resp = recaptcha_check_answer($recaptchaPrv, getIP(),
                    $app->request()->post('recaptcha_challenge_field'), $app->request()->post('recaptcha_response_field'));
                if ($resp->is_valid) {
                    $_SESSION['isadmin'] = true;
                } else {
                    $app->flash('error', "CAPTCHA incorrect. Please try again.");
                }
            } else {
                $app->flash('error', "Invalid security code.");
            }
            break;
        default:
defaultlabel:
            break;
    }
    $app->redirect($app->urlFor('admin'));
})->name('post_admin');

$app->get("/faucet", $checkaddress($app, true), $checkclaim($app), function () use ($app) {
    global $referPercent, $forcewait;
    $flash = $app->view()->getData('flash');
    $address = $app->view()->getData('address');

    $amount = null;
    if (isset($flash['amount'])) {
        $amount = $flash['amount'];
    }
    $sentamount = null;
    if (isset($flash['sentamount'])) {
        $sentamount = $flash['sentamount'];
    }

    $query_balance = sql_query("SELECT * FROM balances WHERE email='$address'");
    if ($query_balance->num_rows) {
        $balance = $query_balance->fetch_assoc();
    } else {
        $balance = array('balance' => 0, 'totalbalance' => 0, 'id' => 0);
    }

    $app->view()->setData('balance_current', $balance["balance"]);
    $app->view()->setData('balance_alltime', $balance["totalbalance"]);
    $reflink = "http://" . $_SERVER['SERVER_NAME'] . $app->urlFor('root') . "?id=" . $balance["id"];
    $app->view()->setData('reflink', $reflink);
    $app->view()->setData('serverbalance', number_format(getserverbalance()));
    $app->view()->setData('forcewait', $forcewait);
    $app->view()->setData('referPercent', $referPercent);

    $app->view()->setData('amount', $amount);
    $app->view()->setData('sentamount', $sentamount);
    $app->render('faucet.php', array('title' => 'Faucet'));
})->name('faucet');

$app->post("/claim", $checkaddress($app, true), $checkclaim($app), function () use ($app) {
    global $mysqli, $rewards, $recaptchaPrv, $referPercent;

    $address = $app->view()->getData('address');
    $resp = recaptcha_check_answer($recaptchaPrv, getIP(),
        $app->request()->post('recaptcha_challenge_field'), $app->request()->post('recaptcha_response_field'));
    if ($resp->is_valid) {
        $canclaim = $app->view()->getData('canclaim');
        if (!$canclaim) {
            $app->redirect($app->urlFor('faucet'));
        }
        $referral = isset($_SESSION['referer']) ? $_SESSION['referer'] : 0;
        $amount = $rewards[rand(0, count($rewards)-1)];
        $sql = "INSERT INTO balances(balance, totalbalance, email, referredby) ";
        $sql .= "VALUES($amount, $amount, '$address', $referral) ON DUPLICATE KEY ";
        $sql .= "UPDATE balance = balance + $amount, totalbalance = totalbalance + $amount;";
        sql_query($sql);
        if ($mysqli->affected_rows == 2) {
            // existing user, check referral
            $referral_query = sql_query("SELECT referredby FROM balances WHERE email='$address'");
            $referral = fetch_one($referral_query);
        }

        $ua = $mysqli->real_escape_string($_SERVER['HTTP_USER_AGENT']);
        $ip = getIP();
        $date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO dispenses(amount, dispensed, email, ip, useragent) ";
        $sql .= "VALUES('$amount', '$date', '$address', '$ip', '$ua')";
        sql_query($sql);

        if ($referral != 0) {
            $referredamount = $amount * ($referPercent / 100);
            $sql = "UPDATE balances SET balance = balance + $referredamount, totalbalance = totalbalance + $referredamount ";
            $sql .= "WHERE id='$referral'";
            sql_query($sql);
        }

        $app->view()->setData('canClaim', true);
        $app->view()->setData('nextClaim', relative_time(time()+1));
        $app->flash('amount', $amount);
    } else {
        $app->flash('error', "CAPTCHA incorrect. Please try again.");
    }
    $app->redirect($app->urlFor('faucet'));
})->name('claim');

$app->post("/cashout", $checkaddress($app, true), function () use ($app) {
    global $cashout;

    $address = $app->view()->getData('address');
    $balance_query = sql_query("SELECT balance FROM balances WHERE email='$address'");
    if ($balance_query->num_rows) {
        $balance = fetch_one($balance_query);
        if ($balance >= $cashout) {
            sql_query("UPDATE balances SET balance = balance - $balance WHERE email='$address'");
            // race attacks check
            $balance_query = sql_query("SELECT balance FROM balances WHERE email='$address'");
            $balancecheck = fetch_one($balance_query);
            if ($balancecheck >= 0) {
                try {
                    sendMoney($address, $balance);
                    $app->flash('sentamount', true);
                } catch (NoCashException $e) {
                    $app->flash('error', "The site does not have enough coins to pay out! No balance deducted.");
                    sql_query("UPDATE balances SET balance = balance + $balance WHERE email='$address'");
                } catch (Exception $e) {
                    $response = $e->getMessage();
                    $app->flash('error', "An error has occured - $response");
                    sql_query("UPDATE balances SET balance = balance + $balance WHERE email='$address'");
                }
            }
        } else {
            $app->flash('error', "Amount is too small");
        }
    } else {
        $app->flash('error', "You don't have enough coins to cash out");
    }
    $app->redirect($app->urlFor('faucet'));
})->name('cashout');

$app->post("/faucet", function () use ($app) {
    global $mysqli, $allowEmail, $allowCoin;
    $address = $app->request()->post('address');

    if (!checkaddress($address)) {
        $err = array();
        if ($allowCoin) {
            $err[] = COIN_NAME;
        }
        if ($allowEmail) {
            $err[] = "email";
        }
        $app->flash('error', "Not a valid ".implode("/", $err)." address!");
        $app->redirect($app->urlFor('root'));
    }

    $_SESSION['address'] = $mysqli->real_escape_string($address);
    $app->redirect($app->urlFor('faucet'));
})->name("post_faucet");

$app->get('/(:segments+)', function ($segments) use ($app) {
    $app->redirect($app->urlFor('root'));
})->name('catchall');

$app->run();
