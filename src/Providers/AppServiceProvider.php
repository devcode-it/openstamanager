<?php

namespace Providers;

use API\Controllers\DataTablesController;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Models\Locale;
use Modules\Impostazioni\API\Controllers\GetImpostazioneProvider;
use Modules\Impostazioni\API\Controllers\ListImpostazioniProvider;
use Modules\Impostazioni\API\Controllers\ListSezioniImpostazioniProvider;
use Modules\Impostazioni\API\Controllers\UpdateImpostazioneProcessor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        
        $this->app->tag(GetImpostazioneProvider::class, ProviderInterface::class);
        $this->app->tag(UpdateImpostazioneProcessor::class, ProcessorInterface::class);
        $this->app->tag(ListSezioniImpostazioniProvider::class, ProviderInterface::class);
        $this->app->tag(ListImpostazioniProvider::class, ProviderInterface::class);
        $this->app->tag(DataTablesController::class, ProcessorInterface::class);    
    }
}
