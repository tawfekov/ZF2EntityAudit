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

Auditing is done in it's own transaction after a flush has been performed.  Auditing takes two flushes in one transaction to complete.  


Install
=======

Download ZF2EntityAudit with composer 

```php
php composer.phar require "tawfekov/zf2entityaudit": "dev-master"
```


Enable ZF2EntityAudit in `config/application.config.php`: 
```php
return array(
    'modules' => array(
        'ZF2EntityAudit'
        ...
    ),
```

Copy `config/zf2entityaudit.global.php.dist` to `config/autoload/zf2entityaudit.global.php` and edit setting as

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

Use the Doctrine command line tool to update the database and create the auditing tables :

```shell
vendor/bin/doctrine-module orm:schema-tool:update
```


Routing
-------

To map a route to an audited entity include route information in the audit => entities config

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

```
    <?php
        $options = $this->auditOptions($revisionEntity->getTargetEntityClass());
        $routeOptions = array_merge($options['defaults'], $revisionEntity->getEntityKeys());
    ?>
    <a class="btn btn-info" href="<?=
        $this->url($options['route'], $routeOptions);
    ?>">Data</a>
```

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


View Helpers
------------

Return the audit service.  This is a helper class.  The class is also available via dependency injection factory ```auditService```
This class provides the following:

1. setComment();
    Set the comment for the next audit transaction.  When a comment is set it will be read at the time the audit Revision is created and added as the comment.

2. getEntityValues($entity, $cleanRevision = false);
    Returns all the fields and their values for the given entity.  Does not include many to many relations.
    $cleanRevision is used internally to strip the reference to Revision from the results if a RevisionEntity is passed.

3. getEntityIdentifierValues($entity, $cleanRevision = false);
    Return all the identifying keys and values for an entity.
    
4. getRevisionEntities($entity)
    Returns all RevisionEntity entities for the given audited entity or RevisionEntity.
    
````
$view->auditService();
```

Return the latest revision entity for the given entity.
```
$view->auditCurrentRevisionEntity($entity);
```

Return a paginator object attached to every RevisionEntity for the given audited entity class.  This is not specific to an entity: this returns every revision entity for the _class name_.
```
$view->auditEntityPaginator($page, $entityClassName);
```

Return the configuration for a specific entity or if not specified returns all entity configurations.  Used internally for routing.
```
$view->auditOptions($entityName = null);
```

Return all RevisionEntity entities for the given entity.
```
$view->auditRevisionEntityPaginator($page, $entity);
```

Return a paginator for all Revision entities.
```
$view->auditRevisionPaginator($page);
```


ZfcUser 
=======

ZfcUser integration maps revisions to users if a user is logged in.  Auditing of revisions without a valid user are mapped as anonymous.


Inspired by SimpleThings
