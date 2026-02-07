<?php

namespace Providers;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\ServiceProvider;
use Models\Locale;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Connect to database at boot
        database();

        $translator = trans_osm();
        $translator->addLocalePath(base_dir().'/locale');
        $translator->addLocalePath(base_dir().'/modules/*/locale');
        $formatter = !empty(config()->get('osm.formatter')) ? config()->get('osm.formatter') : [];

        // Inizializzazione traduzioni
        if (database()->tableExists('zz_settings') && database()->tableExists('zz_langs')) {
            $id_lang = setting('Lingua');
            Locale::setDefault($id_lang);
            Locale::setPredefined();

            $lang = Locale::find($id_lang)->language_code;
            $translator->setLocale($lang, $formatter);
        }

        /*
        Disable: we should use controllers for better performance
        // Register all Providers and Processors from Modules and Plugins
        foreach (get_declared_classes() as $className) {
            if (str_contains($className, 'Modules\\') || str_contains($className, 'API\\') || str_contains($className, 'Plugins\\')) {
                if (in_array(ProviderInterface::class, class_implements($className))) {
                    $this->app->tag($className, ProviderInterface::class);
                }
                if (in_array(ProcessorInterface::class, class_implements($className))) {
                    $this->app->tag($className, ProcessorInterface::class);
                }
            }
        }
        */
    }
}
