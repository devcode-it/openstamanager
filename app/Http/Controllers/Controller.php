<?php

/** @noinspection ClassNameCollisionInspection */

namespace App\Http\Controllers;

use App\ModuleServiceProvider;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use ReflectionClass;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function setLanguage(Request $request): JsonResponse
    {
        $locale = $request->input('locale');
        $languages = self::getLanguages();
        if ($languages->contains($locale)) {
            session()->put('locale', $locale);
            session()->save();
            app()->setLocale($locale);

            return response()->json(['locale' => app()->getLocale()]);
        }

        return response()->json(['success' => false, 'message' => __("Locale isn't available"), 'locale' => app()->getLocale()], 400);
    }

    /**
     * @return Collection<string>
     */
    public static function getLanguages(): Collection
    {
        return collect(File::glob(lang_path('*.json')))
            ->map(static fn (string $file) => File::name($file));
    }

    /**
     * @return Collection<string>
     *
     * @throws JsonException
     */
    public static function getTranslations(): Collection
    {
        return self::getLanguages()
            ->mapWithKeys(fn (string $locale) => [$locale => Json::decode(File::get(lang_path("$locale.json")))]);
    }

    /**
     * @return Collection<array{
     *     name: string,
     *     description: string,
     *     slug: string,
     *     author: string,
     *     version: string,
     *     url: string,
     *     module_path: string
     * }>
     */
    public function getModules(): Collection
    {
        return collect(app()->getLoadedProviders())
            ->keys()
            ->filter(static fn (string $provider) => (new ReflectionClass($provider))->isSubclassOf(ModuleServiceProvider::class))
            ->map(static fn (string $provider) => app()->getProvider($provider))
            ->mapWithKeys(static fn (ModuleServiceProvider $provider) => [$provider::slug() => [
                'name' => $provider::name(),
                'description' => $provider::description(),
                'slug' => $provider::slug(),
                'author' => $provider::author(),
                'version' => $provider::version(),
                'url' => $provider::url(),
                'module_path' => $provider::modulePath(),
                'has_bootstrap' => $provider::hasBootstrap(),
            ]]);
    }
}
