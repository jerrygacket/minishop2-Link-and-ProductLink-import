#!/bin/bash
#~ set -x

installpath="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

#здесь
datafiles=${installpath}/data			#исходные данные в csv файлах
logpath=${installpath}/log				# для логов
imgdata=${installpath}/img-data			# папка с картинками
sshlog=${logpath}/ssh.log

#там
remoteuser=''
remotehost=''
wwwhost=${remoteuser}'@'${remotehost}
hostroot='/home/'${remoteuser}
wwwroot=${hostroot}/www

echo `date +%F\ %H:%M:%S` "	==Начали=="
echo `date +%F\ %H:%M:%S` "	==== Данные: Готовим для MODX"
echo `date +%F\ %H:%M:%S` "	== Данные: Отделяем товары без картинок"
php ${installpath}/01-сепаратор.php
echo `date +%F\ %H:%M:%S` "	== Данные: Собираем виды товаров"
php ${installpath}/81-виды.php
php ${installpath}/82-модели.php
echo `date +%F\ %H:%M:%S` "	== Данные: Собираем товары без картинок"
php ${installpath}/83-товары.php

php ${installpath}/84-наборы.php
php ${installpath}/85-товары-в-наборы.php


echo `date +%F\ %H:%M:%S` "	== Данные: Отправляем данные"
rsync -crth --progress --partial --force ${datafiles}/modx/ ${wwwhost}:${wwwroot}/assets/

echo `date +%F\ %H:%M:%S` "	== Данные: Отправляем картинки"
rsync -crth --progress --partial --force ${imgdata} ${wwwhost}:${wwwroot}/

echo `date +%F\ %H:%M:%S` "	== Хостинг: импорт на сайт"
echo `date +%F\ %H:%M:%S` ": ssh начали" > ${sshlog}
ssh ${wwwhost} << EOF >> ${sshlog}
php ${hosting}/core/components/minishop2/import/csv-custom.php "assets/81-виды.csv" "pagetitle,longtitle,description,parent,template,class_key,published" 1 "longtitle" 0 ";"
php ${hosting}/core/components/minishop2/import/csv-custom.php "assets/82-модели.csv" "pagetitle,longtitle,description,introtext,parent,template,class_key,published" 1 "longtitle" 0 ";"
php ${hosting}/core/components/minishop2/import/csv-custom.php "assets/83-товары.csv" "pagetitle,longtitle,description,introtext,article,price,tema,tags,tkan,size,razmer,parent,template,class_key,published,new,popular" 1 "article" 0 ";"
php ${hosting}/core/components/minishop2/import/csv-custom.php "assets/картинки.csv" "pagetitle,article,source,gallery" 1 "article" 0 ";"
php ${hosting}/core/components/minishop2/import/linkimport.php "assets/84-наборы.csv" "name,type,class_key,description" 1 "name" 0 ";"
php ${hosting}/core/components/minishop2/import/productlinkimport.php "assets/85-товары-в-наборы.csv" "link,goods" 1 "link" 0 ";"
EOF

echo `date +%F\ %H:%M:%S` ": ssh закончили">> ${sshlog}
echo `date +%F\ %H:%M:%S` ":-----------------------------">> ${sshlog}
