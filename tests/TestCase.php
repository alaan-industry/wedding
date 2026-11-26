<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application, compiling views into a test-only directory.
     *
     * The Lando container compiles Livewire views with absolute /app paths into the shared storage/framework/views,
     * so tests run from the host keep their own absolute compiled path to avoid reading the container's cache.
     */
    public function createApplication(): Application
    {
        $_ENV['VIEW_COMPILED_PATH'] = $_SERVER['VIEW_COMPILED_PATH'] = Application::inferBasePath().'/storage/framework/testing/views';

        return parent::createApplication();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
