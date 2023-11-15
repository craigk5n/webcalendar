<?php
$intro = '';
if ($databaseCurrent) {
    $intro .= translate('Your WebCalendar database is configured and current with the installed version of WebCalendar.') . "\n";
    if (!getenv('WEBCALENDAR_USE_ENV')) {
        $intro .= translate('You can use these pages to change your settings and to re-add the default admin user.') . "\n";
    } else {
        $intro .= translate('You can re-add the default admin user.') . "\n";
    }
} else {
    $text = translate('This wizard will guide you through the XXX process.');
    $text = str_replace('XXX', strtolower($installType), $text);
    $intro .= $text;
}
?>
<p><?php echo $intro; ?></p>

</p>
<?php
printNextPageButton($action);
?>