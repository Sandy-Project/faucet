                </div>
<?php if (!$isAdmin): ?>
                <div class='span3'>
                    <h4>Earn More <?php echo UP_COIN_NAME; ?>s</h4>
                    <p><?php echo $links; ?></p>
                    <?php echo getAd($squareAds); ?>
                    <?php echo getAd($textAds); ?>
                    <?php echo getAd($squareAds); ?>
                </div>
<?php endif; ?>
            </div>
            <strong style='font-size: 125%'>Powered by <a href='https://github.com/earth-faucet/faucet'>EarthFaucet v0.1</a>Based on MiniFaucet!</strong>
            <br />
        </div>
    </body>
</html>
