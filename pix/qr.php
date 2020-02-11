<?php

require_once("../classes/phpqrcode/qrlib.php");
QRcode::png(rawurldecode($_GET["txt"]));
