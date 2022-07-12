#!/bin/bash
git pull
git add .
data=`date +%d-%m-%Y_%H:%M:%S`
git commit -m $data
git push
echo "##### OK #####"
