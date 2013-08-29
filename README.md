#ZF2EntityAudit [![Build Status](https://travis-ci.org/tawfekov/ZF2EntityAudit.png?branch=master)](https://travis-ci.org/tawfekov/ZF2EntityAudit)
[![Total Downloads](https://poser.pugx.org/tawfekov/zf2entityaudit/downloads.png)](https://packagist.org/packages/tawfekov/zf2entityaudit)
It is a ZF2  module , about 42kb sized , plug and play architecture , super fast & super easy to use , Its job to audit Doctrine 2 entities in ZF2 and provide web interface to browse the audit log , inspired by https://github.com/simplethings/EntityAudit.

##Documenation
#### Version 0.1 Stable :
please refer to the [documentation page] 
(https://github.com/tawfekov/ZF2EntityAudit/wiki/0.1-documentation)


#### Version 0.2 Stable  :
this versions is currently good for production 
please refer to the [documentation page] 
(https://github.com/tawfekov/ZF2EntityAudit/wiki/0.2-documentation)

#### Upgrading :
It has a simple console script that will you to upgrade between 0.1 to 0.2 , I had use it in production & successfully upgraded about 10000 revisions . 
please refer to the [documentation page] 
(https://github.com/tawfekov/ZF2EntityAudit/wiki/How-to-upgrade-between-0.1-&-0.2-%3F)

#### PHP Unit Testing :
ZF2EntityAudit tests are executed on every commit on [Travis-ci.org](https://travis-ci.org/tawfekov/ZF2EntityAudit) againt both MYSQL & Sqlite with 94% code coverage .

I'm not an expert in Postgresql though I had provided both tests & configuration but its always fail for some crazy reason. if you have good experience in Postgresql try the following : `DB=pgsql phpunit` & ZF2EntityAudit will try to test itself against Postgresql .

#### Support :
Please don't be shy and share me with your problems & ideas about this module , I will be more than happy to hear form you .
feel free to submit bugs to  github issue tracker , via [@tawfekov] (http://www.twitter.com/tawfekov) or via 
[tawfekov@gmail.com](tawfeov@gmail.com)

