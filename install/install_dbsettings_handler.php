<?php

// Write all the config settings to includes/settings.php
$fileContent = "<?php\n";
$fileContent .= "/* updated using WebCalendar " . $PROGRAM_VERSION
    . " via install/index.php on " . date('D, d M Y H:i:s T') . "\n";

//echo "<h2>POST</h2><pre>"; print_r($_POST); echo "</pre>";
foreach ($config_possible_settings as $key => $type) {
    $value = '';
    if (isset($_POST[$key])) {
        $value = $_POST[$key];
    } else if (isset($_SESSION[$key])) {
        $value = $_SESSION[$key];
    }
    // Handle special types like boolean
    switch ($type) {
        case 'boolean':
            $value = (!empty($value) && $value != 'N') ? 'true' : 'false';
            break;
        case 'string':
            // For readonly and single_user, convert "Y" or "N" to true or false respectively
            if ($key == 'readonly' || $key == 'single_user') {
                $value = ($value == "Y") ? 'true' : 'false';
            }
            break;
    }
    if (!empty($value)) {
        $fileContent .= "$key: $value\n";
        $_SESSION[$key] = $value;
    }
}

$fileContent .= "# end settings.php */\n";
$fileContent .= "?>";

// Write the content to the file
file_put_contents(__DIR__ . '/../includes/settings.php', $fileContent);

// Now read it back in to update our session
setSettingsInSession();

// Handle the user's db setting changes.
redirectToNextAction();
