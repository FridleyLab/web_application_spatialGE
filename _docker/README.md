## How to download or build the Docker image for the spatialGE library

Dockerfile contains the reference for a built image, build using:

docker build -t spatialge-april-redis .

If not available, use _Dockerfile instead to rebuild the image using the following command:

docker build -t spatialge-april-redis -f _Dockerfile .