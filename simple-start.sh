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

DOKER_SIMPLE_URL="https://github.com/sebekmsd/docker-simple.git/"

cd $INSTALL_HOME 

if [ ! -d $INSTALL_HOME/docker-simple ]; then
    echo "Descargando codigo de simple"
	git clone $DOCKER_SIMPLE_URL
	
fi

echo "Instalando servidor SIMPLE"

cd $INSTALL_HOME/docker-simple/docker

./install.sh
