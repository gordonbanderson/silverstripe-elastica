#Tasks
## Notes
On UNIX based machines where the webserver is running as a different user than that using the shell, you will need to
prefix the command with a sudo to the relevant user.  For example in Debian, whose webservers run as www-data use the
following example as a guide:

```bash
	sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

###Reindex
Execute a reindex of all of the classes configured to be indexed.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

##Delete Index
Delete the configured index.  Reindexing as above will restore the index as functional.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-DeleteIndexTask
```

###Aliases
You will probably want to add aliases for these tasks.  On Debian the file to edit is ~/.bash_aliases.  Add likes of
the following:

```bash
alias reindex_ss_elastica='sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask progress=250'
alias delete_index_ss_elastica='sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-DeleteIndexTask'
```
Now you can just navigate on the command line to the root of the SilverStripe install and type this to reindex:
```bash
reindex_ss_elastica
```
