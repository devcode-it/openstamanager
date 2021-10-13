Per contribuire al progetto e quindi implementare nuove funzionalità, migliorare quelle esistenti o risolvere problemi,
è necessario installare alcuni software sul proprio PC e seguire le indicazioni riportate in questo documento.

## Strumenti necessari

- [PHP](https://php.net)
- [Composer](https://getcomposer.org)
- [PNPM](https://pnpm.io/it)
- IDE o editor di testo intelligente:
  - [PHPStorm](https://jetbrains.com/phpstorm)* (consigliato)
  - [VS Code](https://code.visualstudio.com/)
  - Altro editor di testo intelligente
- Plugin PHPStorm (consigliati, **solo se** si è scelto PHPStorm sopra):
  - [.env files support](https://plugins.jetbrains.com/plugin/9525--env-files-support)
  - [.ignore](https://plugins.jetbrains.com/plugin/7495--ignore)
  - [collector](https://plugins.jetbrains.com/plugin/15246-collector)
  - [deep-assoc-completion](https://plugins.jetbrains.com/plugin/9927-deep-assoc-completion)
  - [deep-js-completion](https://plugins.jetbrains.com/plugin/11478-deep-js-completion)
  - [GitToolBox](https://plugins.jetbrains.com/plugin/7499-gittoolbox)
  - [InertiaJS support](https://plugins.jetbrains.com/plugin/17435-inertia-js-support) (non installare se si sceglie il
    plugin Laravel Idea)
  - [Laravel Idea](https://plugins.jetbrains.com/plugin/13441-laravel-idea)*
  - [Laravel Make Integration](https://plugins.jetbrains.com/plugin/14612-laravel-make-integration)
  - [Laravel Tinker](https://plugins.jetbrains.com/plugin/14957-laravel-tinker) (opzionale, però può essere utile)
  - [Open in Github](https://plugins.jetbrains.com/plugin/7190-open-in-github)
  - [PHP Advanced Autocomplete](https://plugins.jetbrains.com/plugin/7276-php-advanced-autocomplete)
  - [PHP Inspections (EA Ultimate)](https://plugins.jetbrains.com/plugin/16935-php-inspections-ea-ultimate-)*
  - [PHP ToolBox](https://plugins.jetbrains.com/plugin/8133-php-toolbox)

  *Nota*: Gli strumenti contrassegnati da `*` sono a pagamento. È disponibile una licenza gratuita presso lo
  sviluppatore dello strumento per studenti e progetti open-source

## Preparazione del progetto dal repo di Github

1. Eseguire i seguenti comandi:

  ```bash
  composer install
  pnpm install
  
  php artisan key:generate
  php artisan migrate
  php artisan vendor:publish
  ```

2. Avviare il server di sviluppo:

  ```bash
   pnpm serve-dev
  ```
