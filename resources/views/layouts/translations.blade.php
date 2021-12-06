@php
    /** @var string $translations */
    $translations = cache('translations_' . app()->getLocale());
@endphp
<script>
  window.translations = @js($translations);
</script>
