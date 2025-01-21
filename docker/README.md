# OpenSTAManager Docker Image

<p align="center">
  <a href="https://openstamanager.com">
    <img src="https://shop.openstamanager.com/wp-content/uploads/2015/04/logo_full-2.png">
  </a>

  <p align="center">
    Il software gestionale open-source per l'assistenza tecnica e la fatturazione.
    <br>
    <br>
    <a href="https://www.openstamanager.com">Sito web</a>
    &middot;
    <a href="https://docs.openstamanager.com/">Documentazione</a>
    &middot;
    <a href="https://forum.openstamanager.com">Forum</a>
  </p>
</p>


[![GitHub release](https://img.shields.io/github/release/devcode-it/openstamanager/all.svg)](https://github.com/devcode-it/openstamanager/releases)
[![Downloads](https://img.shields.io/github/downloads/devcode-it/openstamanager/total.svg)](https://github.com/devcode-it/openstamanager/releases)
[![SourceForge](https://img.shields.io/sourceforge/dt/openstamanager.svg?label=SourceForge)](https://sourceforge.net/projects/openstamanager/)
[![license](https://img.shields.io/github/license/devcode-it/openstamanager.svg)](https://github.com/devcode-it/openstamanager/blob/master/LICENSE)

![Screenshot](https://raw.githubusercontent.com/devcode-it/openstamanager/master/assets/src/img/screenshot.jpg)

### Avvio rapido con MySQL incluso (consigliato)

Con questo comando si scarica e avvia l'ultima versione stabile disponibile e l'immagine di MySQL su cui salvare i dati:

```bash
docker compose up -d
```

### Avvio rapido senza MySQL

Con questo comando si scarica e avvia solo l'immagine di OpenSTAManager, senza il database (dovrai creare autonomamente un container con MySQL):

```bash
docker run -d \
    -p 8080:80 \
    --name openstamanager \
    devcodesrl/openstamanager:latest
```

### Salvataggio file e backup

E' consigliato montare un volume per le cartelle `/files`, `/backup` o entrambe, così da poter salvare nella macchina host gli allegati e il backup:

```bash
docker run -d \
    -p 8080:80 \
    --name openstamanager \
    -v ./percorso-locale-files:/var/www/html/files \
    -v ./percorso-locale-backup:/var/www/html/backup \
    devcodesrl/openstamanager:latest
```

oppure nel file `docker-compose.yml`:

```bash
services:
  app:
    build:
      context: .
    container_name: openstamanager
    ports:
      - "8090:80"
    depends_on:
      - db
    environment:
      - APP_ENV=local
      - DB_HOST=db
      - DB_PORT=3306
      - DB_DATABASE=openstamanager
      - DB_USERNAME=root
      - DB_PASSWORD=secret
    volumes:
      - /percorso-locale-files:/var/www/html/files
      - /percorso-locale-backup:/var/www/html/backup

  db:
    image: mysql:8.0
    container_name: mysql
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: openstamanager
    ports:
      - "3306:3306"
```