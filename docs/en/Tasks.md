#Tasks
## Notes
On UNIX based machines where the webserver is running as a different user than
that using the shell, one will need to prefix the command with a sudo to the
relevant user.  For example in Debian, whose webservers run as `www-data` use
the following example as a guide:

```bash
	sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

##Index/Reindex
Execute a reindex of all of the classes configured to be indexed.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```
Without this task being executed it will not be possible to search as the index
will be empty, and thus return no results.

##Delete Index
Delete the configured index.  Reindexing as above will restore the index as
functional.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-DeleteIndexTask
```

##Search Index
Not so much a task but a convenient way to test if SiteTree data has been
indexed correctly.  Simply call the search index task with a query being passed
using the 'q' parameter
```bash
sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-SearchIndexTask q=rain
```
Output looks like this for each of the maximum of 20 results.
```bash
Mount Everest the Reconnaissance, 1921 52
-  the <strong class="highlight">rain</strong> away and stop the
floods. <strong class="highlight">Rain</strong> fell heavily in spite of the noise, but the bridge was
finished
-  in a drenching cloud of <strong class="highlight">rain</strong>, the Tibetans
found shelter in some caves, and persuaded us to camp. An uneven
-  under his cloak and crept inside it. "Now," said he, when
he was safely sheltered from the <strong class="highlight">rain</strong>, "you
```

##Aliases
It is useful to add aliases for these tasks.  On Debian systems the file to edit
is `~/.bash_aliases.`  Add likes of the following:

```bash
alias ssreindex='sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask progress=250'
alias ssdeleteindex='sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-DeleteIndexTask'
```
One can then navigate on the command line to the root of the SilverStripe
install and type this to reindex:
```bash
ssreindex
```
