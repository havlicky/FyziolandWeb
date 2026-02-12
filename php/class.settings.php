<?php

class Settings {
    
    // připojení do db
    static public $dbServer = "uvdb29.active24.cz";
    static public $dbName = "fyziolandc";
    static public $dbLogin = "fyziolandc";
    static public $dbPassword = "vAZfB3TTfkbnfEjckXxE";
    
    // heslo k AES_ENCRYPT a AES_DECRYPT
    static public $mySqlAESpassword = "wbQbRR9ddbtR9hG5FBzE";
    
    // přístupové údaje do API gosms.cz, jsou dostupné v administraci gosms.cz
    static public $goSMSclientId = "6952_3uqomssdaaqs40ws4gwksg4g0w8c48ocgcos0oowgogs8wwcwk";
    static public $goSMSclientSecret = "bixn8jti8y88cwwos8oswogk4gsw8ws4o8c00c4kc48wkwwow";
    static public $goSMSchannel = 210261;
    
    // časy, ve kterých lze provádět rezervace
    static public $timeFrom = 7;
    static public $timeTo = 19;
    
    // časy, ve kterých se rezervace neprovádí
    static public $notAllowedTimes = array(12);
    
}
