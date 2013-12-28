<?php require 'header.php'; ?>

<h3>Admin Page</h3>
<div class='well'>
<?php if (!$isAdmin): ?>
    <form action='<?php echo urlFor("post_admin"); ?>' method='POST'>
        <p>Security code</p>
        <input type='hidden' name='cmd' value='login'>
        <input type='text' name='seccode'>
        <div style='margin: 0 auto; width: 318px'>
            <?php echo $recaptcha; ?>
        </div>
        <input type='submit' class='hidead btn btn-success buttonmargin input-block-level' value='Login'>
    </form>
<?php else: ?>
    <form action='<?php echo urlFor("post_admin"); ?>' method='POST' class='help-inline'>
        <input type='hidden' name='cmd' value='updatebalance'>
        <input type='submit' class='hidead btn btn-primary buttonmargin input-block-level' value='Update server balance'>
    </form>
    <form action='<?php echo urlFor("post_admin"); ?>' method='POST' class='help-inline'>
        <input type='hidden' name='cmd' value='logout'>
        <input type='submit' class='hidead btn btn-success buttonmargin input-block-level' value='Logout'>
    </form>
    <br>
    <b>Server Balance:</b> <?php echo $serverbalance; ?> <?php echo SUB_UNIT_NAME; ?>
    <ul id="tabs" class="nav nav-pills" data-tabs="tabs">
        <li class="active"><a data-target="#home" data-toggle="tab">Home</a></li>
    </ul>
<?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div class='well'>

    <div id="my-tab-content" class="tab-content">
        <div class="tab-pane active" id="home">
            <h4>Home</h4>
            <p>blablabla</p>
        </div>
    </div>

</div>
<?php endif; ?>

<script>
$('a[data-toggle="tab"]').on('shown', function (e) {
    var contentID = $(e.target).attr("data-target");
    var contentURL = $(e.target).attr("href");
    if (typeof(contentURL) != 'undefined') {
        // state: has a url to load from
        $(contentID).load('<?php echo urlFor("post_admin"); ?>/'+contentURL, function(){
            $('#myTab').tab(); //reinitialize tabs
        });
    }
})
</script>

<?php require 'footer.php'; ?>