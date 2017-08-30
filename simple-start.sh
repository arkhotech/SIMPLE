#!/bin/bash

#variables:   APPLICATION_NAME
#  DEPLOYMENT_ID
#  DEPLOYMENT_ID
#  DEPLOYMENT_GROUP_NAME
#  DEPLOYMENT_GEOUP_ID
#  LIFECYCLE_EVENT

if [ -z $INSTALL_HOME ]; then
   INSTALL_HOME=/home/simple
fi

DOCKER_DIR="/docker-simple/simple"

DOCKER_SIMPLE_URL="https://github.com/sebekmsd/docker-simple.git/"

cd $INSTALL_HOME 

if [ ! -d $INSTALL_HOME/docker-simple ]; then
    echo "Descargando codigo de simple"
    git clone $DOCKER_SIMPLE_URL
    chown -R simple:simple $INSTALL_HOME/docker-simple
else
    cd $INSTALL_HOME/docker-simple
    git pull origin master	
	
fi

echo "Instalando servidor SIMPLE"

cd $INSTALL_HOME/$DOCKER_DIR

./install.sh $INSTALL_HOME

#Comunicar que se ha instalado correctamente

aws lambda invoke \
    --invocation-type RequestResponse \
    --function-name simpleSlackMessage \
    --region us-east-2 --payload \
'{  "message": "prueba","payload": "La aplicación SIMPLE ha sido desplegada en el servidor", "status": "SUCCEEDED"}'  output.txt

