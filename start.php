<?php
chdir(dirname(__FILE__));
require_once 'vendor/autoload.php';
$cal = new CallEventCalendar\EventCalendar();
//var_dump();
$g = $cal->showme();
$cal->setTextHeader($_GET['th']);
foreach ($g as $key => $item) {
    //var_dump($item);;
    $g[$key] = array_merge($g[$key], $cal->parseDate($item['date']));
    $g[$key] = array_merge($g[$key], $cal->soldout($item['text']));
}
$cal->ics($g);
