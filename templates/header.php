<html>
    <head>
        <title><?php echo  $title." - ".$siteName; ?></title>
        <link href='//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css' rel='stylesheet'>
        <link href='favicon.ico' rel='shortcut icon' type='image/x-icon' />
        <script src='//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js'></script>
        <script src='//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js'></script>
        <style type='text/css'>
            body {
                background-color: #EEE0A7;
            }
            .container {
                text-align: center;
            }
            .tenpx {
                margin-bottom: 10px;
            }
            ul, li {
                list-style-type: none;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='row-fluid'>
                <div class='span12 header'>
                    <h2><?php echo $siteName; ?></h2>
                    <p>Absolutely free <?php echo UP_COIN_NAME; ?>s!</p>
                </div>
            </div>
            <div class='row-fluid'>
<?php if ($isAdmin): ?>
                <div class='span12'>
<?php else: ?>
                <div class='span3'>
                    <?php echo getAd($squareAds); ?>
                    <h2>Our Rewards</h2>
                    <p>There is an equal chance to get either of the rewards.</p>
<?php foreach($rewards as $r): ?>
                        <strong><?php echo number_format($r); ?></strong> <?php echo SUB_UNIT_NAME; ?> <br />
<?php endforeach; ?>
                    <?php echo getAd($squareAds); ?>
                </div>
                <div class='span6'>
                    <?php echo getAd($bannerAds); ?>
                    <?php echo getAd($textAds); ?>
<?php endif; ?>
<?php if(!empty($error)): ?>
                    <div class='alert alert-error'><?php echo $error; ?></div>
<?php endif; ?>
<?php if(!empty($success)): ?>
                    <div class='alert alert-success'><?php echo $success; ?></div>
<?php endif; ?>
