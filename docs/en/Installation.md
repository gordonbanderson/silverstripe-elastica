#Installation
##System Level
Prior to elastic being installed, Java must be available to the command line interpreter.

###Debian
####Java
Instructions to install Oracle's Java can be found at http://tecadmin.net/install-oracle-java-8-jdk-8-ubuntu-via-ppa/ - it boils down to the following 3 lines:

```bash
$ sudo add-apt-repository ppa:webupd8team/java
$ sudo apt-get update
$ sudo apt-get install oracle-java8-installer
```bash

####Elastic
The Debian package for Elastic can be found at https://www.elastic.co/downloads/elasticsearch - download it, and to install (with administrator privileges) type
```bash
cd /path/to/download
sudo dpkg -i elasticpackagename.deb
```

##SilverStripe
Install core module and dependencies
```bash
composer --verbose require silverstripe-australia/elastica --profile
```

If you wish to use this codebase, if a pull request has not been accepted:
```bash
rm -rf elastica
git clone git@github.com:gordonbanderson/silverstripe-elastica.git elastica
```
