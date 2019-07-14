<?php
chdir(dirname(__FILE__));
require_once 'vendor/autoload.php';
$cal = new CallEventCalendar\EventCalendar();
//var_dump();
$g = $cal->showme();
foreach ($g as $key => $item) {
    //var_dump($item);;
    $g[$key] = array_merge($g[$key], $cal->parseDate($item['date']));
}
$cal->ics($g);
