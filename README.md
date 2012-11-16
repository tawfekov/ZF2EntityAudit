#ZF2 Module enable Doctrine to Audit selected entities. 
-------------------------------------------------------
#Demo : 
-------
you can get a fully working demo application on this git repository : https://github.com/tawfekov/ZF2EntityAudit-demo.
I will publish an on line demo soom .

#Documenation :  
--------------
1- you can download ZF2EntityAudit form composer 
```php
php composer.phar require "tawfekov/zf2entityaudit": "dev-master"
```
or by adding it to `composer.json` then using `php composer.phar update` to install it 

2- enable ZF2EntityAudit in `config/application.config.php` to be like this : 
```php
<?php
return array(
    'modules' => array(
        'Application',
        'DoctrineModule',
        'DoctrineORMModule',
        'ZF2EntityAudit' // add this line.
        .............
    ),
    ..............
```
3- copy `config/zf2entityaudit.global.php.dist` to `config\autoload\zf2entityaudit.global.php` and insert the wanted entities to be audited like example below : 
```php
<?php
return array(
    "audited_entities" => array(
        "Application\Entity\Entity1",
        "Application\Entity\Entity2",
        "Application\Entity\Entity3",
    ),
);
```

4- use doctrine command line tool to update the database and created the auditing tables :
```shell
./doctrine orm:schema-tool:update 
```

5- viewing the auditing records :
to be updated soon ....



6-if you found any problem or you have any idea about improving this module please let  me know 

on Github : [tawfekov]
on Twitter: [@tawfekov] 



[tawfekov]:https://github.com/tawfekov
[@tawfekov]:http://twitter.com/tawfekov
