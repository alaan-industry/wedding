<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Wait until a JavaScript expression is truthy in the browser, such as after a Livewire round trip.
 */
function waitUntil(PendingAwaitablePage|AwaitableWebpage|Webpage $page, string $condition, string $description, int $timeoutMs = 5000): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    $description = json_encode($description);

    $page->script(<<<JS
        () => new Promise((resolve, reject) => {
            const startedAt = Date.now();
            const isMet = () => {
                try {
                    return Boolean({$condition});
                } catch {
                    return false;
                }
            };
            const check = () => {
                if (isMet()) {
                    return resolve(true);
                }

                if (Date.now() - startedAt > {$timeoutMs}) {
                    return reject(new Error('Timed out waiting for ' + {$description}));
                }

                setTimeout(check, 50);
            };

            check();
        })
        JS);

    return $page;
}

/**
 * Wait until the visible page text contains the given text, ignoring CSS text-transform.
 */
function waitForVisibleText(PendingAwaitablePage|AwaitableWebpage|Webpage $page, string $text): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    $needle = json_encode(mb_strtolower($text));

    return waitUntil($page, "document.body.innerText.toLowerCase().includes({$needle})", "the text \"{$text}\" to appear");
}

/**
 * Wait until the visible page text no longer contains the given text.
 */
function waitForVisibleTextToDisappear(PendingAwaitablePage|AwaitableWebpage|Webpage $page, string $text): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    $needle = json_encode(mb_strtolower($text));

    return waitUntil($page, "! document.body.innerText.toLowerCase().includes({$needle})", "the text \"{$text}\" to disappear");
}

/**
 * Break the seal on the invitation envelope and wait for it to fade away, unless it was already opened in this session.
 */
function openEnvelope(PendingAwaitablePage|AwaitableWebpage|Webpage $page): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    $isSealed = $page->script("() => sessionStorage.getItem('invitation-opened') !== '1'");

    if ($isSealed) {
        $page->click('[data-test="envelope"] button');
    }

    return waitUntil(
        $page,
        "getComputedStyle(document.querySelector('[data-test=\"envelope\"]')).display === 'none'",
        'the envelope to open',
        timeoutMs: 8000,
    );
}
