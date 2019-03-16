<?php

// Middleware per l'input
$app->add($container['filter']);

// Middleware per i contenuti di base
$app->add(new \Middlewares\ContentMiddleware($container));
