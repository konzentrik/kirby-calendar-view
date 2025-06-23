<?php

namespace konzentrik\CalendarView;

use Kirby\Cms\App as Kirby;
use Kirby\Http\Response;
use Kirby\Cms\Pages;
use DateTimeZone;
use DateTime;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('konzentrik/calendarview', [
    'routes' => [
        [
            'pattern' => '(:any)/calendarview.ics',
            'action'  => function ($secret) {
                if ($secret !== option("konzentrik.calendarview.secret", null)) {
                    return new Response('Not allowed', 'text/plain', 401);
                }

                $calendarId = kirby()->url('index', true)->domain();

                $licenseManager = new LicenseManager(instanceId: $calendarId);
                $licenseActive = $licenseManager->isActivated();

                // Set basic date variables
                $localTimeZone = new DateTimeZone(option("konzentrik.calendarview.timezone", 'Europe/Berlin'));
                $utc = new DateTimeZone('UTC');
                $currentYear = date('Y');
                $freePlanMaxDate = date('Y-m-d', strtotime('+14 days'));

                // Get all blogposts
                $sources = [];
                foreach (option("konzentrik.calendarview.pages", []) as $source) {
                    $sources[] = page($source)->index(true);
                }

                foreach (option("konzentrik.calendarview.collections", []) as $source) {
                    $sources[] = kirby()->collection($source);
                }

                $posts = new Pages($sources);
                $posts = $licenseActive ? $posts->filterBy('date', 'date >=', $currentYear . '-01-01') : $posts->filterBy('date', 'date >=', $currentYear . '-01-01')->filterBy('date', 'date <=', $freePlanMaxDate);

                if ($templates = option("konzentrik.calendarview.templates", false)) {
                    $posts = $posts->filterBy("intendedTemplate", "in", $templates);
                }

                $posts = $posts->sortBy('date', 'asc');

                $ical = [];

                // Headers
                $ical[] = 'BEGIN:VCALENDAR';
                $ical[] = 'VERSION:2.0';
                $ical[] = 'PRODID:-//' . $calendarId . '//Kirby Calendar View//EN';

                foreach ($posts as $post) {
                    $uid = $post->id();
                    $url = $post->url();

                    $description = ($descriptionField = option("konzentrik.calendarview.descriptionField", false)) ? $post->$descriptionField()->text() ?? '' : '';
                    $title = ($titleField = option("konzentrik.calendarview.titleField", false))
                        ? $post->$titleField()->text() ?? $post->title()->value()
                        : $post->title()->value();

                    $date = ($dateField = option("konzentrik.calendarview.dateField", false))
                        ? $post->$dateField()
                        : $post->date();

                    $postDate = $date->toDate('Y-m-d H:i:s'); // UTC
                    if (is_null($date)) {
                        continue;
                    }

                    // Get the post date in local timezone
                    $dateStart = new DateTime($postDate, $localTimeZone);
                    $dateStart->setTimezone($utc);
                    $start = $dateStart->format('Ymd\THis\Z');

                    $ical[] = 'BEGIN:VEVENT';
                    $ical[] = 'UID:' . md5($uid) . '@' . $calendarId;
                    $ical[] = 'DTSTAMP:' . $start;
                    $ical[] = 'DTSTART:' . $start;
                    $ical[] = 'DURATION:PT' . option("konzentrik.calendarview.duration", '30') . 'M';
                    $ical[] = 'SUMMARY:' . addcslashes($title, ",;");
                    $ical[] = 'DESCRIPTION:' . addcslashes($description, ",;");
                    $ical[] = 'URL:' . $url;
                    $ical[] = 'END:VEVENT';
                }

                $ical[] = 'END:VCALENDAR';

                return new Response(implode("\r\n", $ical), 'text/calendar');
            }
        ]
    ]
]);
