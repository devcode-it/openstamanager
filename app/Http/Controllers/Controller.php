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
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionException;

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
            $request->session()->put('locale', $locale);
            $request->session()->save();
            $request->user()?->settings()->set('locale', $locale);
            app()->setLocale($locale);

            return response()->json(['locale' => app()->getLocale()]);
        }

        return response()->json(['success' => false, 'message' => __("Locale isn't available"), 'locale' => app()->getLocale()], 400);
    }

    /**
     * @return Collection<int, string>
     */
    public static function getLanguages(): Collection
    {
        return collect(File::glob(lang_path('*.json')))
            ->merge(File::directories(lang_path()))
            ->map(static fn (string $file) => File::name($file));
    }

    /**
     * @return Collection<string, array{
     *     name: string,
     *     description: string,
     *     slug: string,
     *     author: string,
     *     version: string,
     *     url: string,
     *     modulePath: string,
     *     namespace: string
     * }>
     */
    public function getModules(): Collection
    {
        return collect(app()->getLoadedProviders())
            ->keys()
            ->filter(
                /**
                 * @param class-string $provider
                 *
                 * @throws ReflectionException
                 */
                static fn (string $provider) => (new ReflectionClass($provider))->isSubclassOf(ModuleServiceProvider::class))
            ->map(static fn (string $provider): ?ServiceProvider => app()->getProvider($provider))
            ->mapWithKeys(static function (?ServiceProvider $provider) {
                if ($provider instanceof ModuleServiceProvider) {
                    return [$provider->slug() => [
                        'name' => $provider->name(),
                        'description' => $provider->description(),
                        'slug' => $provider->slug(),
                        'author' => $provider->author(),
                        'version' => $provider->version(),
                        'url' => $provider->url(),
                        'modulePath' => $provider::modulePath(),
                        'namespace' => $provider::namespace(),
                    ]];
                }
                return [];
            });
    }
}
