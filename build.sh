#!/bin/bash

ROOT_DIR=$(pwd)
VERSION=$(< version)
FILENAME="CoolRunner_CoolShipping_$VERSION.zip"
rm -rf _build
cd app/code/local/CoolRunner/CoolShipping
composer update --ignore-platform-reqs
cd $ROOT_DIR
mkdir -p _build
cd _build
mkdir -p step_1
mkdir -p step_2/app/etc/modules
cp -R ../app step_1
cp -R ../skin step_1
cp -R ../app/etc/modules/CoolRunner_CoolShipping.xml step_2/app/etc/modules/CoolRunner_CoolShipping.xml
cp -R ../CoolShipping\ Manual.pdf ./
rm -rf step_1/app/etc/
zip -rq $FILENAME step_1 step_2 CoolShipping\ Manual.pdf
mv -f $FILENAME ../
cd ..
rm -rf _build
echo "Build complete! Release file: $FILENAME"