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
```

####Elasticsearch
The Debian package for relevant version of Elasticsearch, 1.7.2, can be found at
https://download.elasticsearch.org/elasticsearch/elasticsearch/elasticsearch-1.7.2.deb - download
it, and to install (with administrator privileges) type
```bash
cd /path/to/download
sudo dpkg -i elasticsearch-1.7.2.deb
```

By default the server binds to an address of 0.0.0.0, so if installing on a public machine you wis
to restrict the visibility.  For a single VPS instance edit the file
/etc/elasticsearch/elasticsearch.yml and update the value 'network.bind_host' as follows.

```
network.bind_host: localhost
```
Restart elasticsearch
```
sudo service elasticsearch restart
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
cd elastica
git checkout dev2
```
