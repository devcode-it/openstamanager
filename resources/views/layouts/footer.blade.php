@php
    /** @var string $translations */
    $translations = cache('translations_' . app()->getLocale());

    /** @var \Illuminate\Support\Collection $modules */
@endphp
<script>
  window.importPath = '{{Str::contains(vite_asset(''), config('vite.dev_url')) ? config('vite.dev_url') : '.'}}';
  window.modules = @js($modules->pluck('modules')->collapse()->all());
  window.translations = @js($translations);
</script>

@routes
@vite('app')
