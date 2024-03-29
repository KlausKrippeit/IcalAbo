<?php
namespace CallEventCalendar;

use PHPHtmlParser\Dom;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Relationship\Attendee;
use Jsvrcek\ICS\Model\Relationship\Organizer;

use Jsvrcek\ICS\Utility\Formatter;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;
use \DateTime;
use \DateInterval;

class EventCalendar {

    const ZMF_PROGRAM_URL = 'http://zmf.de/programm2019';
    private $textHeader = false;

    public function setTextHeader($textHeader) {
        if ($textHeader == 'text') {
            $this->textHeader = true;
        }
    }

    public function showme() {

        $dom = new Dom;
        $dom->loadFromFile(EventCalendar::ZMF_PROGRAM_URL);
        $event = [];
        $return = [];
        $a = $dom->find('.default-zmf-grid .programm__details');

        foreach ($a as $content) {
            foreach ($content->getChildren() as $chn) {
                if (preg_match ('/(^programm__)(.*)/', $chn->getTag()->getAttribute('class')['value'], $matches)){
                    $event[$matches[2]] = trim($chn->text(true));
                }
            }
            $return[] = $event;
        }

        return $return;
    }

    public function parseDate($dateString) {
        // weekday: (^.{2})
        // date : (31|30|[012]\d|\d)\.(0\d|1[012]|\d)\.(19[789]\d|20[0123]\d|[01]\d)
        // time: (([01]?\d|2[0-3]):([0-5]?\d)) Uhr
        //$pattern = '/(^.{2}) (31|30|[012]\d|\d)\.(0\d|1[012]|\d)\.(19[789]\d|20[0123]\d|[01]\d) ([01]?\d|2[0-3]:[0-5]?\d)&nbsp;Uhr, (.*)/';
        $pattern = '/(^.{2}) (31|30|[012]\d|\d)\.(0\d|1[012]|\d)\.(19[789]\d|20[0123]\d|[012]\d) ([012][0-9]:[0-5]?\d)&nbsp;Uhr, (.*)/';
        $ret = [];

        if(preg_match($pattern, $dateString, $ma) ) {
            //var_dump($ma);
            $ret['weekday'] = $ma[1];
            $day = $ma[2] > 9 ? $ma[2] : '0' . $ma[2];
            $month = $ma[3] > 9 ? $ma[3] : '0' . $ma[3];
            $year = $ma[4];
            $ret['eventDate'] = $day.$month.$year;
            $ret['time'] = $ma[5];
            $ret['location'] = $ma[6];
        }
        return $ret;

    }
    
    public function soldout($event) {

        $pattern = '/^(AUSVERKAUFT!)/';
        $ret['soldout'] = '';

        if (preg_match($pattern, $event, $ma)) {
            $ret['soldout'] = $ma[1];
        }

        return $ret;
    }

    public function ics($events) {

        date_default_timezone_set('Europe/Berlin');
        $calendar = new Calendar();
        $calendar->setProdId('-//localhost//NONSGML CallEventCalendar//App//DE')
            ->setTimezone(new \DateTimeZone('Europe/Berlin'));

        foreach ($events as $event) {
            $date = DateTime::createFromFormat('jmyH:i', $event['eventDate'] . $event['time'], new \DateTimeZone('Europe/Berlin'));
            $endDate = clone $date;
            $endDate->add(new DateInterval('PT1H'));
            $summary = html_entity_decode($event['soldout'] . ' ' . $event['title'], ENT_QUOTES, 'UTF-8');
            $eventId = md5(uniqid(mt_rand() . $event['location'], true));
            $eventOne = new CalendarEvent();
            $eventOne->setStart($date)
                ->setEnd($endDate)
                ->setSummary($summary)
                ->setDescription($event['location'] . ' | ' . html_entity_decode($event['text'], ENT_QUOTES, 'UTF-8'))
                ->setUid($eventId);
            $calendar->addEvent($eventOne);

        }

        $calendarExport = new CalendarExport(new CalendarStream, new Formatter());
        $calendarExport->addCalendar($calendar);
        $streamOut = $calendarExport->getStream();
        
        if ($this->textHeader) {
            header('Content-type:text/plain; charset=UTF-8');
        } else {
            header('Content-type:text/calendar; charset=UTF-8');
            header('Content-Disposition: attachment; filename="EventCalendar.ics"');
        }
        Header('Content-Length:'.strlen($streamOut));
        Header('Connection: close');
        echo $streamOut;

    }

}