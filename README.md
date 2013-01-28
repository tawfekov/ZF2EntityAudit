ZF2EntityAudit Demo Application
===============================
An example application to demonstrate ZF2EntityAudit Doctrine2 auditing.

To use this branch run ``` git branch -f demo origin/demo ``` then ``` git checkout demo ```

Install
=======
# install composer via ``` curl -s http://getcomposer.org/installer | php ```(on windows, download http://getcomposer.org/installer and execute it with PHP)
# run ``` php composer.phar install ```
# run ``` ./vendor/bin/doctrine-module orm:schema-tool:update --force ```
# for php 5.4 cd to ``` public ``` and run ``` php -S localhost:8088 ```
