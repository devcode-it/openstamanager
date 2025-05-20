echo "Inserisci il numero di versione di OSM, verrà usato anche per Docker (2.7-beta, oppure 2.7.1, ecc):"
read version

docker build .

images=$(docker image ls)
image_id=$(echo "$images" | sed -z -E 's/.*<none>[[:space:]]+<none>[[:space:]]+([0-9a-f]+)[[:space:]].*/\1/')
echo "IMAGE ID: "$image_id

docker tag $image_id devcodesrl/openstamanager:$version
docker tag $image_id devcodesrl/openstamanager:latest

docker push devcodesrl/openstamanager:$version
docker push devcodesrl/openstamanager:latest