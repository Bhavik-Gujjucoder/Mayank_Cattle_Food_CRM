<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Use .env.dusk.local APP_URL (subdirectory installs) even when config is cached.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->applyDuskAppUrl();

        Browser::$baseUrl = $this->baseUrl();
    }

    protected function applyDuskAppUrl(): void
    {
        $appUrl = rtrim((string) env('APP_URL', ''), '/');

        if ($appUrl === '') {
            return;
        }

        config(['app.url' => $appUrl, 'dusk.domain' => $appUrl]);
        URL::forceRootUrl($appUrl);
    }

    /**
     * @return string
     */
    protected function baseUrl()
    {
        $appUrl = rtrim((string) env('APP_URL', ''), '/');

        return $appUrl !== '' ? $appUrl : rtrim(config('app.url'), '/');
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
