<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php'; 

$helpStr = translate('Help');
$titleStr = translate ( 'User Access Control' );
$descStr = translate('User Access Control allows for finer control of user access and permissions than possible before. Users can also grant default and per individual permission if authorized by the administrator.');
$inviteStr = translate ( 'Can Invite' );
$inviteTStr = translate('If disabled, this user will not see you in the participants list.');
$emailStr = translate('Can Email');
$emailTStr = translate('If disabled, this user will not be able to send you emails.');
$timeStr = translate ( 'Can See Time Only' );
$timeTStr = translate('If enabled, this user will not be able to view the details of any of your entries.');
 
print_header('', '', '', true, false, true);
echo $helpListStr;
echo <<<EOT

<div class="helpbody">
  <h2>{$helpStr}:{$titleStr}</h2>
  <p>{$descStr}</p>
  <p><label>{$inviteStr}:</label>
   {$inviteTStr}</p>
  <p><label>{$emailStr}:</label>
   {$emailTStr}</p>
  <p><label>{$timeStr}:</label>
   {$timeTStr}</p>
</div>

EOT;

echo print_trailer( false, true, true ); ?>

