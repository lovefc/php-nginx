#!/bin/bash

NAME=$1
#echo $NAME
ID=`ps -ef | grep "$NAME" | grep -v "grep" | awk '{print $2}'`

echo $ID

#echo $ID
#echo "---------------"
for id in $ID
do
kill -9 $id
#echo "killed $id"
done
#echo "---------------"

#关掉fpm
kill -INT `cat /run/php7.4-fpm.pid`