<?php require 'header.php'; ?>

<h3>Get free <?php echo UP_COIN_NAME; ?>s from <?php echo $siteName; ?>!</h3>
<div class='ad well'>
    <p>Want to get free <?php echo UP_COIN_NAME; ?>s?</p>
    <form action='<?php echo urlFor("post_faucet"); ?>' method='POST'>
        <input type='text' name='address' placeholder='eYjTEuTiy4Zco2AwgWJpJSHGKuTm6vzzHR'>
        <input type='submit' class='hidead btn btn-success buttonmargin input-block-level' value='Go'>
    </form>
    <p>Enter your <?php echo $addressType; ?> address to get started.</p>
</div>
<div class='well'><?php echo getAd($bannerAds); ?></div>
<div>
    <ul>
        <li><i class='icon-hand-right'></i> Earn <strong><?php echo number_format($minReward); ?></strong> to <strong><?php echo number_format($maxReward); ?></strong> <?php echo SUB_UNIT_NAME; ?> per dispense!</li>
        <li><i class='icon-time'></i> Get a dispense every <strong><?php echo $dispenseTimeText; ?></strong>!</li>
        <li><i class='icon-ok'></i> No work needed - just click!</li>
        <li><i class='icon-bell'></i> <strong>Instant</strong> as soon as you reach cashout threshold of <?php echo number_format($cashout); ?> <?php echo SUB_UNIT_NAME; ?></li>
<?php if(!empty($wallet)): ?>
        <li><i class='icon-globe'></i> <?php echo $wallet; ?> - no transaction fees and offchain!</li>
<?php endif; ?>
</div>
<?php echo getAd($bannerAds); ?>
<?php echo getAd($textAds); ?>

<?php require 'footer.php'; ?>