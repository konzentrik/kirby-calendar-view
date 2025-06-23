# Kirby Calendar View

Subscribe to a certain calendar URL and see all your planned and published posts in your default calendar app.

## Installation

Use one of these methods to install the plugin:

- composer (recommended): `composer require konzentrik/kirby-calendar-view`
- zip file: unzip [main.zip](https://github.com/konzentrik/kirby-calendar-view/releases/latest) as folder `site/plugins/kirby-calendar-view`

## Usage

First configure a secret in the `config.php` file:

```php
'konzentrik.calendarview' => [
    'secret' => 'YOUR-SECRET',
],
```

Then set your timezone:

```php
'konzentrik.calendarview' => [
    'secret' => 'YOUR-SECRET',
    'timezone' => 'Europe/Berlin',
],
```

Tell the plugin which pages contain your posts:

```php
'konzentrik.calendarview' => [
    'secret' => 'YOUR-SECRET',
    'timezone' => 'Europe/Berlin',
    'pages' => [
        'blog',
        'notes',
    ],
],
```

You can also use collections as a source:

```php
'konzentrik.calendarview' => [
    'secret' => 'YOUR-SECRET',
    'timezone' => 'Europe/Berlin',
    'collections' => [
        'blog',
        'notes',
    ],
],
```

Then subscribe to the calendar URL: `https://yourdomain.com/YOUR-SECRET/calendarview.ics`

## Options

Please prefix every option with `konzentrik.calendarview.`.

| Option             | Default           | Description                                                  |
| ------------------ | ----------------- | ------------------------------------------------------------ |
| `licenseKey`       | `''`              | Your license key                                             |
| `secret`           | `''`              | Your secret key                                              |
| `timezone`         | `'Europe/Berlin'` | Your local timezone                                          |
| `duration`         | `30`              | How long should the calendar entry be displayed (in minutes) |
| `pages`            | `[]`              | An array of pages where your posts are in                    |
| `collections`      | `[]`              | An array of collection of posts                              |
| `templates`        | `[]`              | Only show posts having a specific template                   |
| `titleField`       | page title        | Set a field to use as title                                  |
| `dateField`        | `date`            | Set a youd date field                                        |
| `descriptionField` | `''`              | Set a field to use as description                            |

## License

Kirby Calendar View can be used in a limited free mode. In order to use the full featured version, you'll have to purchase a valid Kirby license & a valid plugin license.

Copyright 2025 © konzentrik GmbH
