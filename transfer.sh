#!/bin/bash
params=" --delete --stats"
exclude="" # --exclude classes/phpqrcode"
here="/var/www/www.eduvidual.org/blocks/eduvidual"
there="moodle@mdcommunity.bmb.gv.at:/data/web/www/vhosts/meine.bildung.at/blocks/eduvidual"


if [ "$1" = "up" ]
then
    echo "Uploading from this server to BMB"
    cmd="rsync $params $exclude -r $here/* $there/"
    $cmd
    echo "ok... done"
elif [ "$1" = "down" ]
then
    echo "Downloading from BMB to this server"
    cmd="rsync $params $exclude -r $there/* $here/"
    $cmd
    echo "ok... done"
else
    echo "Unknown command"
fi
