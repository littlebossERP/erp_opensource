<?php

require_once 'FtpProtocol.php';

gftp\FtpProtocol::registerDriver('ftp', 'gftp\drivers\FtpDriver', 21);
gftp\FtpProtocol::registerDriver('ftps', 'gftp\drivers\FtpsDriver', 21);


