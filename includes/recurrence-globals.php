<?php

declare(strict_types=1);

function initialize_recurrence_globals(): void
{
  $GLOBALS['byday_names'] = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
  $GLOBALS['byday_values'] = array_flip($GLOBALS['byday_names']);
}
