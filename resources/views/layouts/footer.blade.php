@php
    /** @var string $translations */
    $translations = cache('translations_' . app()->getLocale());

    /** @var \Illuminate\Support\Collection $modules */
@endphp
<script>
  window.modules = @js($modules->pluck('modules')->collapse()->all());
  window.translations = @js($translations);
</script>

@routes
@tag('app')
