<?php require 'header.php'; ?>

<h2>Welcome</h2>
<p>Your address is: <?php echo $address; ?></p>

<?php if(!empty($amount)): ?>
    <div class='alert alert-success'>Congrats! You have claimed <strong><?php echo $amount; ?></strong> <?php echo SUB_UNIT_NAME; ?>.</div>
<?php endif; ?>
<?php if(!empty($sentamount)): ?>
    <div class='alert alert-success'>Successful cashout to <?php echo $address; ?> - enjoy!</div>
<?php endif; ?>

<div class='well'>
    Your balance: Current: <strong><?php echo number_format($balance_current); ?></strong> <?php echo SUB_UNIT_NAME; ?> | All time: <strong><?php echo number_format($balance_alltime); ?></strong> <?php echo SUB_UNIT_NAME; ?><br />
    Cash out amount: <?php echo number_format($cashout); ?> <?php echo SUB_UNIT_NAME; ?><br />
<?php if($balance_current >= $cashout): ?>
        <form action='<?php echo urlFor("cashout"); ?>' method='post'>
            <input type='submit' class='btn btn-success' value='Cash out all'>
        </form>
<?php else: ?>
        <button type='button' disabled='disabled' class='btn btn-success'>Cash out all</button><br />
<?php endif; ?>
    <b>Server Balance:</b> <?php echo $serverbalance; ?> <?php echo SUB_UNIT_NAME; ?>
</div>
<div class='well'><?php echo getAd($bannerAds); ?></div>
<div class='well'>
    <strong>Get a Dispense: </strong>
<?php if($canclaim): ?>
    <form action='<?php echo urlFor("claim"); ?>' method='post'>
        <div style='margin: 0 auto; width: 318px'>
            <?php echo $recaptcha; ?>
        </div>
        <input type='hidden' name='claim' value='true'>
        <input type='submit' id='claimbtn' value='Wait <?php echo $forcewait ?>s' class='btn btn-success btn-large disabled'>
        <script>
            var secsLeft = <?php echo $forcewait; ?>;
            setInterval(function(){
                secsLeft--;
                if(secsLeft > 0){
                    $('#claimbtn').val('Wait ' + secsLeft + 's');
                } else if(secsLeft == 0){
                    $('#claimbtn').removeClass('disabled');
                    $('#claimbtn').val('Claim');
                }
            }, 1000);
        </script>
    </form>
<?php else: ?>
    You can claim again in <?php echo $nextclaim; ?>.<br />
    <strong>Try these sites:</strong>
    <?php echo $links; ?>
<?php endif; ?>
</div>

<?php if(!empty($reflink)): ?>
<div class='well'>
    <p><strong>Refer and get <?php echo $referPercent; ?>% of every dispense!</strong></p>
    <p>If a user enters their address using your referral link, we lock that in forever!</p>
    <p>Your link: <strong><?php echo $reflink; ?></strong></p>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>