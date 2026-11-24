<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The Couple
    |--------------------------------------------------------------------------
    */

    'groom' => [
        'first_name' => 'Benjie',
        'full_name' => 'Benjie Alaan',
    ],

    'bride' => [
        'first_name' => 'Monaliza',
        'full_name' => 'Monaliza Donaire',
    ],

    'hashtag' => env('WEDDING_HASHTAG', '#BenjieAndMonalizaMerged'),

    /*
    |--------------------------------------------------------------------------
    | Date & Time
    |--------------------------------------------------------------------------
    |
    | All times are local to the timezone below. Update the ceremony and
    | reception times once they are final with the parish and venue.
    |
    */

    'timezone' => 'Asia/Manila',

    'ceremony_starts_at' => env('WEDDING_CEREMONY_STARTS_AT', '2027-01-30 14:00'),

    'reception_starts_at' => env('WEDDING_RECEPTION_STARTS_AT', '2027-01-30 17:00'),

    'reception_ends_at' => env('WEDDING_RECEPTION_ENDS_AT', '2027-01-30 22:00'),

    'rsvp_deadline' => env('WEDDING_RSVP_DEADLINE', '2027-01-09 23:59'),

    /*
    |--------------------------------------------------------------------------
    | Venues
    |--------------------------------------------------------------------------
    */

    'ceremony' => [
        'name' => 'San Pedro Parish',
        'address' => 'Brgy. San Vicente, Panabo City, Davao del Norte',
        'map_url' => 'https://www.google.com/maps/search/?api=1&query=7.313968,125.702755',
    ],

    'reception' => [
        'name' => 'Casa de 828',
        'address' => 'Panabo City, Davao del Norte',
        'map_url' => 'https://www.google.com/maps/search/?api=1&query='.urlencode('Casa de 828 Panabo City'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Programme
    |--------------------------------------------------------------------------
    */

    'timeline' => [
        ['time' => '1:30 PM', 'title' => 'Guest Arrival', 'description' => 'Find your seat and settle in at San Pedro Parish.'],
        ['time' => '2:00 PM', 'title' => 'Holy Matrimony', 'description' => 'We say "I do" before God, family, and friends.'],
        ['time' => '4:00 PM', 'title' => 'Photos & Cocktails', 'description' => 'Light bites and drinks while we capture the moment.'],
        ['time' => '5:00 PM', 'title' => 'Reception', 'description' => 'Dinner, toasts, and stories at Casa de 828.'],
        ['time' => '7:30 PM', 'title' => 'First Dance & Party', 'description' => 'Bring your dancing shoes. The floor is yours.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dress Code
    |--------------------------------------------------------------------------
    */

    'dress_code' => [
        'title' => 'Formal Attire',
        'description' => 'Come dressed in your formal best — a suit, barong, polo with slacks, cocktail dress, or anything that feels elegant to you. We would love to see you in our wedding palette.',
        'palette' => [
            ['name' => 'Champagne', 'hex' => '#E8D9B5'],
            ['name' => 'Dark Blue', 'hex' => '#1B3A4B'],
            ['name' => 'Dusty Blue', 'hex' => '#8FA8B8'],
            ['name' => 'Dusty Rose', 'hex' => '#D8A7A7'],
            ['name' => 'Light Rose', 'hex' => '#F2D4D0'],
        ],
        'avoid' => 'Kindly avoid white, which we reserve for the bride.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gifts & Questions
    |--------------------------------------------------------------------------
    */

    'gift_note' => 'Your presence on our special day is the greatest gift of all. Should you wish to bless us further, a monetary gift as we begin building our home together would be warmly appreciated.',

    'faqs' => [
        [
            'question' => 'Can I bring a plus one?',
            'answer' => 'We have reserved a specific number of seats in your honor, shown on your personal invitation. Kindly let us know the names of everyone joining you when you RSVP.',
        ],
        [
            'question' => 'What time should I arrive?',
            'answer' => 'Please be at San Pedro Parish at least 30 minutes before the ceremony so you can find your seat and enjoy the quiet moments before we begin.',
        ],
        [
            'question' => 'Is there parking at the venues?',
            'answer' => 'Yes. Casa de 828 has a parking area for guests, and there is parking around San Pedro Parish as well.',
        ],
        [
            'question' => 'Can I take photos during the ceremony?',
            'answer' => 'We are having an unplugged ceremony. Our photographers will capture every moment, so please keep your phones away and be fully present with us. Snap away at the reception!',
        ],
        [
            'question' => 'What if I can no longer attend after I RSVP?',
            'answer' => 'Simply open your personal invitation link again and update your reply before the RSVP deadline, or message us directly.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Little Surprises
    |--------------------------------------------------------------------------
    |
    | Notes the eagle drops when a guest taps it, and the song that plays
    | once the envelope is opened. Place the song inside the public folder;
    | the music button only appears when the file exists.
    |
    */

    'dove_notes' => [
        'Screech! Thank you for flying in to celebrate with us.',
        'Fun fact: eagles mate for life. So will these two.',
        'Benjie\'s best merge yet: git merge monaliza --no-ff',
        'Love is patient, love is kind. (1 Corinthians 13:4)',
        'No bugs in this love story, only features.',
        'Save your best dance moves for the reception. I\'ll be watching.',
        'Every love story is beautiful, but this one is our favourite.',
    ],

    /*
    | Photos shown in the hero panels and the countdown frame, in order.
    | Place them inside the public folder; a styled panel is shown in place
    | of any photo that does not exist yet.
    */

    'photos' => [
        'images/couple-1.jpg',
        'images/couple-2.jpg',
        'images/couple-3.jpg',
        'images/couple-4.jpg',
    ],

    /*
    | Photos from our trips together, shown oldest first in the "Our Adventures"
    | carousel. Each needs a path inside the public folder and the date it
    | was taken; the caption (e.g. the place) is optional. Photos that do
    | not exist are skipped, and the section hides when none exist.
    */

    'adventures' => [
        ['path' => 'images/adventures/01-2017-02.jpg', 'taken_on' => '2017-02-13', 'caption' => null],
        ['path' => 'images/adventures/02-2018-08.jpg', 'taken_on' => '2018-08-05', 'caption' => null],
        ['path' => 'images/adventures/03-2020-01.jpg', 'taken_on' => '2020-01-03', 'caption' => null],
        ['path' => 'images/adventures/04-2020-01.jpg', 'taken_on' => '2020-01-03', 'caption' => null],
        ['path' => 'images/adventures/05-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/06-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/07-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/08-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/09-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/10-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/11-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/12-2020-01.jpg', 'taken_on' => '2020-01-09', 'caption' => null],
        ['path' => 'images/adventures/13-2020-09.jpg', 'taken_on' => '2020-09-13', 'caption' => null],
        ['path' => 'images/adventures/14-2020-09.jpg', 'taken_on' => '2020-09-13', 'caption' => null],
        ['path' => 'images/adventures/15-2020-09.jpg', 'taken_on' => '2020-09-13', 'caption' => null],
        ['path' => 'images/adventures/16-2020-09.jpg', 'taken_on' => '2020-09-20', 'caption' => null],
        ['path' => 'images/adventures/17-2022-12.jpg', 'taken_on' => '2022-12-29', 'caption' => null],
        ['path' => 'images/adventures/18-2023-11.jpg', 'taken_on' => '2023-11-22', 'caption' => null],
        ['path' => 'images/adventures/19-2023-11.jpg', 'taken_on' => '2023-11-24', 'caption' => null],
        ['path' => 'images/adventures/20-2023-11.jpg', 'taken_on' => '2023-11-24', 'caption' => null],
        ['path' => 'images/adventures/21-2023-11.jpg', 'taken_on' => '2023-11-24', 'caption' => null],
        ['path' => 'images/adventures/22-2023-11.jpg', 'taken_on' => '2023-11-25', 'caption' => null],
        ['path' => 'images/adventures/23-2023-11.jpg', 'taken_on' => '2023-11-25', 'caption' => null],
        ['path' => 'images/adventures/24-2023-11.jpg', 'taken_on' => '2023-11-25', 'caption' => null],
        ['path' => 'images/adventures/25-2023-11.jpg', 'taken_on' => '2023-11-28', 'caption' => null],
        ['path' => 'images/adventures/26-2023-11.jpg', 'taken_on' => '2023-11-29', 'caption' => null],
        ['path' => 'images/adventures/27-2024-12.jpg', 'taken_on' => '2024-12-30', 'caption' => null],
        ['path' => 'images/adventures/28-2025-03.jpg', 'taken_on' => '2025-03-30', 'caption' => null],
    ],

    /*
    | The picture chat apps show when the invitation link is shared, ideally a
    | 1200×630 JPG inside the public folder. The first photo above is used
    | until it exists.
    */

    'og_image' => env('WEDDING_OG_IMAGE', 'images/og-image.jpg'),

    'music' => [
        'path' => env('WEDDING_MUSIC_PATH', 'audio/our-song.mp3'),
        'title' => env('WEDDING_MUSIC_TITLE', 'Our song'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrators
    |--------------------------------------------------------------------------
    |
    | Comma separated e-mail addresses of the accounts allowed to manage the
    | guest list from the dashboard.
    |
    */

    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('WEDDING_ADMIN_EMAILS', '')),
    ))),

];
