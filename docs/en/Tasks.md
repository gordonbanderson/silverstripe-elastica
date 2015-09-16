#Tasks
## Notes
On UNIX based machines where the webserver is running as a different user than that using the shell, you will need to
prefix the command with a sudo to the relevant user.  For example in Debian, whose webservers run as www-data use the
following example as a guide:

```bash
	sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

##Reindex
Execute a reindex of all of the classes configured to be indexed.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

##Delete Index
Delete the configured index.  Reindexing as above will restore the index as functional.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-DeleteIndexTask
```

##Search Index
Not so much a task but a convenient way to test if SiteTree data has been indexed correctly.  Simply called the
search index task with your query being the 'q' parameter
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
