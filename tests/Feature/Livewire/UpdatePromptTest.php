<?php

use App\Contracts\ReadsAppBuild;
use App\Contracts\ReadsInstallChannel;
use App\Services\Api\QuestifyApiClient;
use App\Services\AppBuild;
use App\Services\AppUpdateService;
use App\Services\InstallChannel;
use App\Services\StoreLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Native\Mobile\Facades\Browser;

function fakeAppUpdate(array $data, int $status = 200): void
{
    Http::fake([
        '*/api/v1/app-update' => Http::response(['data' => $data], $status),
        '*/api/v1/info' => Http::response(['data' => ['auth_methods' => ['email' => true]]], 200),
    ]);
}

/** Answers /app-update with each payload in turn, one per request. */
function fakeAppUpdateSequence(array ...$payloads): void
{
    $sequence = Http::sequence();

    foreach ($payloads as $data) {
        $sequence->push(['data' => $data], 200);
    }

    Http::fake([
        '*/api/v1/app-update' => $sequence,
        '*/api/v1/info' => Http::response(['data' => ['auth_methods' => ['email' => true]]], 200),
    ]);
}

function channelIs(?string $channel): void
{
    app()->instance(ReadsInstallChannel::class, new class($channel) implements ReadsInstallChannel
    {
        public function __construct(private ?string $channel) {}

        public function read(): ?string
        {
            return $this->channel;
        }
    });
    app()->forgetInstance(InstallChannel::class);
}

beforeEach(function () {
    Cache::flush();
    app()->forgetInstance(AppUpdateService::class);
    app()->forgetInstance(QuestifyApiClient::class);
});

it('shows nothing when no update is waiting', function () {
    fakeAppUpdate(['update_available' => false, 'update_required' => false]);

    Livewire::test('update-prompt')->call('check')->assertSet('show', false);
});

it('shows nothing when the backend cannot be reached', function () {
    fakeAppUpdate([], 500);

    Livewire::test('update-prompt')->call('check')->assertSet('show', false);
});

it('offers an optional update the player can put off', function () {
    channelIs('appstore');
    fakeAppUpdate(['update_available' => true, 'update_required' => false, 'latest_version' => '1.2.0', 'store_url' => 'https://apps.apple.com/app/id1']);

    Livewire::test('update-prompt')
        ->call('check')
        ->assertSet('show', true)
        ->assertSet('required', false)
        ->assertSee('Questify 1.2.0')
        ->assertSee(__('update.open_store'))
        ->assertSee(__('update.later'))
        ->call('later')
        ->assertSet('show', false);

    Livewire::test('update-prompt')->call('check')->assertSet('show', false);
});

it('offers the next version after the player put one off', function () {
    fakeAppUpdateSequence(
        ['update_available' => true, 'update_required' => false, 'latest_version' => '1.2.0'],
        ['update_available' => true, 'update_required' => false, 'latest_version' => '1.3.0'],
    );

    Livewire::test('update-prompt')->call('check')->call('later');

    Livewire::test('update-prompt')->call('check')->assertSet('show', true);
});

it('locks a required update with no way to put it off', function () {
    fakeAppUpdate(['update_available' => true, 'update_required' => true, 'latest_version' => '1.2.0', 'store_url' => 'https://apps.apple.com/app/id1']);

    Livewire::test('update-prompt')
        ->call('check')
        ->assertSet('required', true)
        ->assertSee(__('update.required_body'))
        ->assertDontSee(__('update.later'))
        ->call('later')
        ->assertSet('show', true);
});

it('keeps a required update after later was said to the same version', function () {
    fakeAppUpdateSequence(
        ['update_available' => true, 'update_required' => false, 'latest_version' => '1.2.0'],
        ['update_available' => true, 'update_required' => true, 'latest_version' => '1.2.0'],
    );

    Livewire::test('update-prompt')->call('check')->call('later');

    Livewire::test('update-prompt')->call('check')->assertSet('show', true)->assertSet('required', true);
});

it('names TestFlight for a beta build', function () {
    channelIs('testflight');
    fakeAppUpdate(['update_available' => true, 'update_required' => false, 'latest_version' => '1.2.0', 'latest_build' => 9, 'store_url' => 'https://testflight.apple.com/join/x']);

    Livewire::test('update-prompt')
        ->call('check')
        ->assertSee(__('update.open_testflight'))
        ->assertSee('1.2.0 (9)')
        ->assertDontSee(__('update.open_store'));
});

it('leaves the button out when there is no address to open', function () {
    fakeAppUpdate(['update_available' => true, 'update_required' => false, 'latest_version' => '1.2.0', 'store_url' => null]);

    Livewire::test('update-prompt')
        ->call('check')
        ->assertDontSee(__('update.open_store'))
        ->assertSee(__('update.later'));
});

it('tells the API the version, build and channel on every request', function () {
    channelIs('testflight');
    config(['nativephp.version' => '1.4.0', 'nativephp.version_code' => 77]);
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    app(QuestifyApiClient::class)->get('/categories');

    Http::assertSent(fn ($request): bool => $request->hasHeader('X-App-Version', '1.4.0')
        && $request->hasHeader('X-App-Build', '77')
        && $request->hasHeader('X-App-Channel', 'testflight'));
});

it('counts an unreadable install as the App Store', function () {
    foreach ([null, 'debug', 'appstore'] as $read) {
        channelIs($read);

        expect(app(InstallChannel::class)->current())->toBe('appstore')
            ->and(app(InstallChannel::class)->isBeta())->toBeFalse();
    }
});

it('words every string for a player, not for a web page', function () {
    foreach (['en', 'da'] as $locale) {
        app()->setLocale($locale);
        $text = collect(__('update'))->implode(' ');

        expect(preg_match('/\b(page|link|url|browser|site|web|404|500)\b/i', $text))->toBe(0);
    }
});

it('has the same update strings in English and Danish', function () {
    expect(array_keys(trans('update', [], 'da')))->toEqual(array_keys(trans('update', [], 'en')));
});

it('reports the build number the phone has, not the one baked into the config', function () {
    config(['nativephp.version_code' => 46]);
    app()->instance(ReadsAppBuild::class, new class implements ReadsAppBuild
    {
        public function read(): ?int
        {
            return 47;
        }
    });
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    app(QuestifyApiClient::class)->get('/categories');

    Http::assertSent(fn ($request): bool => $request->hasHeader('X-App-Build', '47'));
});

it('falls back to the configured build number when the phone cannot say', function () {
    config(['nativephp.version_code' => 46]);

    expect(app(AppBuild::class)->number())->toBe(46);
});

it('opens a TestFlight address in the TestFlight app', function (string $url) {
    expect((new StoreLink)->target($url))->toBe('itms-beta://beta.itunes.apple.com/v1/app/6762279177');
})->with([
    'https://testflight.apple.com/v1/app/6762279177',
    'https://beta.itunes.apple.com/v1/app/6762279177',
]);

it('leaves other addresses as they are and opens them in the browser here', function () {
    expect((new StoreLink)->target('https://apps.apple.com/app/id1'))->toBe('https://apps.apple.com/app/id1');

    Browser::shouldReceive('open')->once()->with('https://apps.apple.com/app/id1');

    (new StoreLink)->open('https://apps.apple.com/app/id1');
});
