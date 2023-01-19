<?php

// PHP 4.1.0 may have issues with the above code.
unset ( $_SESSION['webcal_login'] );
unset ( $_SESSION['webcalendar_session'] );

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.php';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();

// TODO make this an option for external users.
$htmlmail = false;

load_user_preferences('guest');

$WebCalendar->setLanguage();

require 'includes/classes/WebCalMailer.php';
$mail = new WebCalMailer;

/*
From the documentation, the following settings are important here.

Allow self-registration ($ALLOW_SELF_REGISTRATION = Y):
  If enabled, new users are permitted to setup their own accounts and log into WebCalendar
  without admin intervention.  Use with caution!
Restrict self-registration to blacklist ($SELF_REGISTRATION_BLACKLIST = Y):
  If enabled, admin can configure the includes/blacklist.php to restrict or permit
  self-registration based on the user's IP. This will restrict access to existing
  users or new user's set up by admin. Details and examples are available in the top 
  of the file.
Generate passwords and send to new users ($SELF_REGISTRATION_FULL = Y):
  If enabled, self-registration user's will be emailed a randomly generated password
   that they can then use to access WebCalendar normally. Hopefully, this will prevent
   some spammers and hackers from misusing the self-registration process.
*/

$appStr  = generate_application_name();
$notauth = print_not_auth();

$error = (empty($ALLOW_SELF_REGISTRATION) || $ALLOW_SELF_REGISTRATION != 'Y'
  ? $notauth : '');

if (empty($SELF_REGISTRATION_FULL) || $SELF_REGISTRATION_FULL != 'Y')
  $SELF_REGISTRATION_FULL = 'N';

// Do email the user their password?  Or do we allow them to set it here?
$form_control = ($SELF_REGISTRATION_FULL == 'Y' ? 'email' : 'full');

/**
 * See if username and email are unique.
 *
 * param: $isWhat  string  What are we looking for; user login or user email?
 * param: $isWher  string  Where are we looking; "login" or "email"?
 *
 * Return true if all is OK.
 */
function checks($isWhat, $isWher)
{
  global $control, $error;

  if (!strlen($isWhat)) {
    $error = ($isWher == 'login'
      ? translate('Username cannot be blank.')
      : translate('Email address cannot be blank.'));
    return false;
  }
  $res = dbi_execute('SELECT cal_' . $isWher . ' FROM webcal_user
    WHERE cal_' . $isWher . ' = ?', array($isWhat));

  if ($res) {
    $row = dbi_fetch_row($res);

    if ($row[0] == $isWhat) {
      $control = '';
      $error = ($isWher == 'login'
        ? translate('Username already exists.')
        : translate('Email address already exists.'));
      return false;
    }
  }
  return true;
}

/**
 *  Generate unique password.
 */
function generate_password()
{
  $pass = '';
  $pass_length = 8;
  $salt = 'abchefghjkmnpqrstuvwxyz0123456789';
  srand((float) microtime() * 1000000);
  $i = 0;
  while ($i < $pass_length) {
    $pass .= substr($salt, rand() % 33, 1);
    $i++;
  }
  return $pass;
}

$uemail =
  $ufirstname =
  $ulastname =
  $upassword1 =
  $upassword2 =
  $user = '';

// We can limit what domain is allowed to self register.
// $self_registration_domain should have this format "192.168.220.0:255.255.240.0";
$valid_ip = validate_domain();

if (empty($valid_ip))
  $error = $notauth;

// We could make $control a unique value if necessary.
$control = getPostValue('control');
$illegalCharStr = translate('Illegal characters in login XXX.');

if (empty($error) && !empty($control)) {
  $uemail     = getPostValue('uemail');
  $ufirstname = getPostValue('ufirstname');
  $uis_admin  = 'N';
  $ulastname  = getPostValue('ulastname');
  $user       = trim(getPostValue('user'));

  if ($user != addslashes($user))
    $error = str_replace('XXX', htmlentities($user), $illegalCharStr);

  // Check to make sure user doesn't already exist.
  checks($user, 'login');

  // Check to make sure email address doesn't already exist.
  checks($uemail, 'email');
}

if (empty($error) && !empty($control)) {
  if ($control == 'full') {
    // Process full account addition.
    $upassword1 = getPostValue('upassword1');
    $upassword2 = getPostValue('upassword2');

    // Do some checking of user info.
    if (!empty($user) && !empty($upassword1)) {
      $user = trim($user);

      if ($user != addslashes($user))
        $error = str_replace('XXX', htmlentities($user), $illegalCharStr);
    } elseif ($upassword1 != $upassword2) {
      $control = '';
      $error = translate('The passwords were not identical.');
    }

    if (empty($error)) {
      if (user_add_user(
        $user,
        $upassword1,
        $ufirstname,
        $ulastname,
        $uemail,
        $uis_admin
      ) ) {
        echo "Success: $user"; exit;
        activity_log(
          0,
          'system',
          $user,
          LOG_NEWUSER_FULL,
          translate('New user via self-registration.')
        );
      } else {
        $error = dbi_error();
      }
    }
  } elseif ($control == 'email') {
    // Process account info for email submission.
    // Need to generate unique passwords and email them to the new user.
    $new_pass = generate_password();
    // TODO allow admin to approve account and emails prior to processing.
    user_add_user(
      $user,
      $new_pass,
      $ufirstname,
      $ulastname,
      $uemail,
      $uis_admin
    );

    $tempName = trim($ufirstname . ' ' . $ulastname);
    $msg = str_replace(
      ', XXX.',
      (strlen($tempName) ? ', ' . $tempName . '.' : '.'),
      translate('Hello, XXX.')
    ) . "\n\n"
      . translate('A new WebCalendar account has been set up for you.')
      . "\n\n"
      . str_replace('XXX', $user, translate('Your username is XXX.'))
      . "\n\n"
      . str_replace('XXX', $new_pass, translate('Your password is XXX.'))
      . "\n\n"
      . str_replace(
        'XXX',
        $appStr,
        translate('Please visit XXX to log in and start using your account!')
      )
      . "\n";

    // Add URL to event, if we can figure it out.
    if (!empty($SERVER_URL)) {
      $url = $SERVER_URL . 'login.php';

      if ($htmlmail == 'Y')
        $url = activate_urls($url);

      $msg .= "\n\n" . $url;
    }
    $msg .= "\n\n"
      . translate('You may change your password after logging in the first time.')
      . "\n\n" . translate('If you received this email in error') . "\n\n";
    $adminStr = translate('Administrator', true);
    $name = $appStr . ' ' . translate('Welcome') . ': ' . $ufirstname;
    // Send via WebCalMailer class.
    $mail->WC_Send($adminStr, $uemail, $ufirstname . ' '
      . $ulastname, $name, $msg, $htmlmail, $EMAIL_FALLBACK_FROM);
    activity_log(
      0,
      'system',
      $user,
      LOG_NEWUSER_EMAIL,
      translate('New user via email.')
    );
  }
}

echo send_doctype($appStr);

echo $ASSETS;

echo "<script>\n";
?>
  var validform = false, xlate = [];
  xlate['inputPassword'] = '<?php etranslate('You have not entered a password.', true);?>';
  xlate['noBlankUsername'] = '<?php etranslate('Username cannot be blank.', true);?>';
  xlate['noBlankEmail'] = '<?php etranslate('Email cannot be blank.', true);?>';
  xlate['passwordsNoMatch'] = '<?php etranslate('The passwords were not identical.', true);?>';
</script>
<link href="css_cacher.php?login=__public__" rel="stylesheet" />
<link href="includes/css/styles.css" rel="stylesheet" />

<?php 
 // Print custom header (since we do not call print_header function)
 if (!empty($CUSTOM_SCRIPT) && $CUSTOM_SCRIPT == 'Y') {
   load_template($login, 'S');
 }
?>

</head>
<body id="register" class="container-fluid">
<div class="container">
<h2><?php echo $appStr . ' ' . translate('Registration');?></h2>

<!-- Error Alert -->
<div id="main-dialog-alert" class="alert alert-danger" style="<?php echo empty($error)? "display: none" : "display: block"?>">
    <span id="infoMessage"><?php echo translate('Error'). ": $error";?></span>
    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
</div>

<?php if (!empty($control)) { ?>
  <div class="row">
    <div><?php etranslate('Welcome to WebCalendar');?></div>
  </div>
  <?php if ($SELF_REGISTRATION_FULL == 'Y') { ?>
    <div class="row">
      <div><?php etranslate('Your email should arrive shortly.');?><br>
      <a href="login.php"><?php etranslate('Return to Login screen');?></a></div>
    </div>
  <?php } ?>
<?php } else { ?>
  <form action="register.php" method="post" onSubmit="return valid_form()"
    name="selfreg">
    <input type="hidden" name="control" value="<?php echo $form_control;?>" />
    <div class="form-group row">
      <label for="login" class="text-info"><?php etranslate('Username')?>:</label><br>
      <input type="text" name="user" id="user" class="form-control" value="<?php echo htmlspecialchars($user);?>"
        size="20" maxlength="20" onchange="valid_form();">
    </div>

    <div class="form-group row">
      <label for="login" class="text-info"><?php etranslate('First Name')?>:</label><br>
      <input type="text" name="ufirstname" id="ufirstname" class="form-control" value="<?php echo htmlspecialchars($ufirstname);?>"
        size="25" maxlength="25">
    </div>

    <div class="form-group row">
      <label for="login" class="text-info"><?php etranslate('Last Name')?>:</label><br>
      <input type="text" name="ulastname" id="ulastname" class="form-control" value="<?php echo htmlspecialchars($ulastname);?>"
        size="25" maxlength="25">
    </div>

    <div class="form-group row">
      <label for="login" class="text-info"><?php etranslate('E-mail address')?>:</label><br>
      <input type="text" name="uemail" id="uemail" class="form-control" value="<?php echo htmlspecialchars($uemail);?>"
        size="40" maxlength="75" onchange="valid_form();">
    </div>

    <?php if ($SELF_REGISTRATION_FULL != 'Y') { ?>
      <div class="form-group row">
        <label for="login" class="text-info"><?php etranslate('Password')?>:</label><br>
        <input type="password" name="upassword1" id="upassword1" class="form-control" value="<?php echo htmlspecialchars($upassword1);?>"
          size="30" maxlength="32" onchange="valid_form();">
      </div>

      <div class="form-group row">
        <label for="login" class="text-info"><?php etranslate('Password (again)')?>:</label><br>
        <input type="password" name="upassword2" id="upassword2" class="form-control" value="<?php echo htmlspecialchars($upassword2);?>"
          size="30" maxlength="32" onchange="valid_form();">
      </div>
    <?php } else { ?>
      <div class="form-group row">
        <?php etranslate('Your account information will be emailed to you.'); ?>
      </div>
    <?php } ?>

    <div class="form-group row justify-content-md-center">
      <button id="submitButton" type="submit" class="btn btn-primary"><?php etranslate('Submit');?></button>
    </div>

    </form>
<?php } ?>    

<br />
<span class="cookies"><?php etranslate('cookies-note');?></span><br />
<hr />
<a href="<?php echo $PROGRAM_URL;?>" id="programname"><?php echo $PROGRAM_NAME;?></a>

<?php
// Print custom trailer (since we do not call print_trailer function).
if (!empty($CUSTOM_TRAILER) && $CUSTOM_TRAILER == 'Y') {
  $res = dbi_execute('SELECT cal_template_text FROM webcal_report_template
    WHERE cal_template_type = \'T\' and cal_report_id = 0');
  if ($res) {
    if ($row = dbi_fetch_row($res))
      echo $row[0];

    dbi_free_result($res);
  }
}
?>

</div>

<script>
  var validform = false;

  function valid_form() {
    validform = true;

    $(':input[type="submit"]').prop('disabled', false);
    if ($('#upassword1').length && $('#upassword1').val().length == 0) {
      $('#infoMessage').html(xlate['inputPassword']);
      $('#main-dialog-alert').show();
      validform = false;
    }
    if ($('#upassword1').length && $('#upassword1').val().length == 0) {
      $('#infoMessage').html(xlate['inputPassword']);
      $('#main-dialog-alert').show();
      validform = false;
    }
    if ($('#user').val().length == 0) {
      $('#infoMessage').html(xlate['noBlankUsername']);
      $('#main-dialog-alert').show();
      validform = false;
    }
    if ($('#uemail').val().length == 0) {
      $('#infoMessage').html(xlate['noBlankEmail']);
      $('#main-dialog-alert').show();
      validform = false;
    }
    if ($('#upassword1').length && $('#upassword1').val() != $('#upassword2').val()) {
      $('#infoMessage').html(xlate['passwordsNoMatch']);
      $('#main-dialog-alert').show();
      validform = false;
    }

    // Only do AJAX checks if we still think form is valid.
    if (validform) {
      checkers('user', 'register');
      checkers('uemail', 'email');
    }

    $(':input[type="submit"]').prop('disabled', !validform);

    return validform;
  }

  function checkers(formfield, params) {
    var val = $('#' + formfield).val();
    $.post('ajax.php', {
        page: params,
        name: val
      },
      function(data, status) {
        var stringified = JSON.stringify(data);
        console.log("checkers Data: " + stringified + "\nStatus: " + status);
        try {
          var response = jQuery.parseJSON(stringified);
          console.log('checkers response=' + response);
        } catch (err) {
          alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
          return;
        }
        if (response) {
          $('#infoMessage').html('<?php etranslate('Error'); ?>: ' + response);
          $('#main-dialog-alert').show();
          validform = false;
          if(!validform) {
            $(':input[type="submit"]').prop('disabled', true);
          }
          return;
        }
        if(!validform) {
          $(':input[type="submit"]').prop('disabled', true);
        } else {
          $('#main-dialog-alert').hide();
        }
      });
  }

  $(document).ready(function() {
    valid_form();
  });
</script>

</body>
</html>
