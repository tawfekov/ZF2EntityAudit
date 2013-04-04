ZF2EntityAudit
==============

An auditing module for Doctrine 2.  Requires ZfcUser to map revisions to users.

About
=====

This module takes a configuration of entities you'd like to audit and creates 
entities to audit them.  Included is a view layer to browse the audit records.
Routing back to live application data is supported and view helpers
allow you to find and browse to the latest audit record from a given audited entity.

Revisions pool all audited entities into revision buckets.  Each bucket contains the revision entity for each 
audited record in a transaction.

Auditing is done in it's own transaction after a flush has been performed.  Auditing takes one transaction
and two flushes to complete.  


Install
=======
1. Download ZF2EntityAudit with composer 
```php
php composer.phar require "tawfekov/zf2entityaudit": "dev-master"


2. enable ZF2EntityAudit in `config/application.config.php`: 
```php
return array(
    'modules' => array(
        'ZF2EntityAudit'
        ...
    ),
```

3. copy `config/zf2entityaudit.global.php.dist` to `config/autoload/zf2entityaudit.global.php` and edit setting as

```php
return array(
    'audit' => array(
        'datetime.format' => 'r',
        'paginator.limit' => 20,
        
        'tableNamePrefix' => '',
        'tableNameSuffix' => '_audit',
        'revisionTableName' => 'Revision',
        'revisionEntityTableName' => 'RevisionEntity',
        
        'entities' => array(           
            'Db\Entity\Song' => array(),
            'Db\Entity\Performer' => array(),
        ),
    ),
);
```

4. use doctrine command line tool to update the database and created the auditing tables :
```shell
vendor/bin/doctrine-module orm:schema-tool:update
```


Routing
-------

To map a route to an audited entity include route information in the audit=>entities config

```
    'Db\Entity\song' => array(
        'route' => 'default',
        'defaults' => array(
            'controller' => 'song',
            'action' => 'detail',
        ),
    ),
```

Identifier column values from the audited entity will be added to defaults to generate urls through routing.
This is how to map from your application to it's current revision entity:

```
    <?php
    $currentRevisionEntity = $this->auditCurrentRevisionEntity($this->song);
    ?>

    <a class="btn btn-info" href="<?=
        $this->url('audit/revision-entity',
            array(
                'revisionEntityId' => $currentRevisionEntity->getId()
            )
        );
    ?>">
        <i class="icon-list"></i>
    </a>
```

Routes are used in audit views 

```
    <?php
        $options = $this->auditOptions($revisionEntity->getTargetEntityClass());
        $routeOptions = array_merge($options['defaults'], $revisionEntity->getEntityKeys());
    ?>
    <a class="btn btn-info" href="<?=
        $this->url($options['route'], $routeOptions);
    ?>">Data</a>
```


Audit Records
=============

Routes are added to /audit  This module provides the view layer for browsing the audit log
from this route.  You can protect this route using any ACL module.  We suggest [BjyAuthorize].


ZfcUser 
=======

ZfcUser integration maps revisions to users if a user is logged in.  Auditing of revisions without
a valid user will still produce an audit record.


Contributors
============

on Github : [tawfekov]
on Twitter: [@tawfekov]

Thanks also flys to [TomHAnderson] for his [Contributions].

[repository]:https://github.com/tawfekov/ZF2EntityAudit-demo
[Contributions]:https://github.com/tawfekov/ZF2EntityAudit/graphs/contributors
[BjyAuthorize]:github.com/bjyoungblood/BjyAuthorize
[TomHAnderson]:https://github.com/TomHAnderson
[tawfekov]:https://github.com/tawfekov
[@tawfekov]:http://twitter.com/tawfekov

Inspired by SimpleThings