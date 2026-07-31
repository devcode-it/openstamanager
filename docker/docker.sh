#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ ! -f "$SCRIPT_DIR/Dockerfile" ]; then
    echo "Errore: Dockerfile non trovato in $SCRIPT_DIR"
    exit 1
fi

echo "Inserisci il numero di versione di OSM, verrà usato anche per Docker (2.7-beta, oppure 2.7.1, ecc):"
read version

docker image rm devcodesrl/openstamanager:latest 2>/dev/null

docker build --no-cache -t devcodesrl/openstamanager:$version "$SCRIPT_DIR"

if [[ $version != *"-beta"* ]]; then
    docker tag devcodesrl/openstamanager:$version devcodesrl/openstamanager:latest
    docker push devcodesrl/openstamanager:latest
fi

docker push devcodesrl/openstamanager:$version