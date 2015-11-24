<?php
/**
 * The Translatable decorator allows your DataObjects to have versions in different languages,
 * defining which fields are can be translated. Translatable can be applied
 * to any {@link DataObject} subclass, but is mostly used with {@link SiteTree}.
 * Translatable is compatible with the {@link Versioned} extension.
 * To avoid cluttering up the database-schema of the 99% of sites without multiple languages,
 * the translation-feature is disabled by default.
 *
 * Locales (e.g. 'en_US') are used in Translatable for identifying a record by language,
 * see section "Locales and Language Tags".
 *
 * <h2>Configuration</h2>
 *
 * The extension is automatically enabled for SiteTree and SiteConfig records,
 * if they can be found. Add the following to your config.yml in order to
 * register a custom class:
 *
 * <code>
 * MyClass:
 *   extensions:
 *     Translatable
 * </code>
 *
 * Make sure to rebuild the database through /dev/build after enabling translatable.
 * Use the correct {@link set_default_locale()} before building the database
 * for the first time, as this locale will be written on all new records.
 *
 * <h3>"Default" locales</h3>
 *
 * Important: If the "default language" of your site is not US-English (en_US),
 * please ensure to set the appropriate default language for
 * your content before building the database with Translatable enabled:
 * <code>
 * Translatable::set_default_locale(<locale>); // e.g. 'de_DE' or 'fr_FR'
 * </code>
 *
 * For the Translatable class, a "locale" consists of a language code plus a region
 * code separated by an underscore,
 * for example "de_AT" for German language ("de") in the region Austria ("AT").
 * See http://www.w3.org/International/articles/language-tags/ for a detailed description.
 *
 * <h2>Usage</h2>
 *
 * Getting a translation for an existing instance:
 * <code>
 * $translatedObj = Translatable::get_one_by_locale('MyObject', 'de_DE');
 * </code>
 *
 * Getting a translation for an existing instance:
 * <code>
 * $obj = DataObject::get_by_id('MyObject', 99); // original language
 * $translatedObj = $obj->getTranslation('de_DE');
 * </code>
 *
 * Getting translations through {@link Translatable::set_current_locale()}.
 * This is *not* a recommended approach, but sometimes inavoidable (e.g. for {@link Versioned} methods).
 * <code>
 * $origLocale = Translatable::get_current_locale();
 * Translatable::set_current_locale('de_DE');
 * $obj = Versioned::get_one_by_stage('MyObject', "ID = 99");
 * Translatable::set_current_locale($origLocale);
 * </code>
 *
 * Creating a translation:
 * <code>
 * $obj = new MyObject();
 * $translatedObj = $obj->createTranslation('de_DE');
 * </code>
 *
 * <h2>Usage for SiteTree</h2>
 *
 * Translatable can be used for subclasses of {@link SiteTree},
 * it is automatically configured if this class is foun.
 *
 * If a child page translation is requested without the parent
 * page already having a translation in this language, the extension
 * will recursively create translations up the tree.
 * Caution: The "URLSegment" property is enforced to be unique across
 * languages by auto-appending the language code at the end.
 * You'll need to ensure that the appropriate "reading language" is set
 * before showing links to other pages on a website through $_GET['locale'].
 * Pages in different languages can have different publication states
 * through the {@link Versioned} extension.
 *
 * Note: You can't get Children() for a parent page in a different language
 * through set_current_locale(). Get the translated parent first.
 *
 * <code>
 * // wrong
 * Translatable::set_current_locale('de_DE');
 * $englishParent->Children();
 * // right
 * $germanParent = $englishParent->getTranslation('de_DE');
 * $germanParent->Children();
 * </code>
 *
 * <h2>Translation groups</h2>
 *
 * Each translation can have one or more related pages in other languages.
 * This relation is optional, meaning you can
 * create translations which have no representation in the "default language".
 * This means you can have a french translation with a german original,
 * without either of them having a representation
 * in the default english language tree.
 * Caution: There is no versioning for translation groups,
 * meaning associating an object with a group will affect both stage and live records.
 *
 * SiteTree database table (abbreviated)
 * ^ ID ^ URLSegment ^ Title ^ Locale ^
 * | 1 | about-us | About us | en_US |
 * | 2 | ueber-uns | Über uns | de_DE |
 * | 3 | contact | Contact | en_US |
 *
 * SiteTree_translationgroups database table
 * ^ TranslationGroupID ^ OriginalID ^
 * | 99 | 1 |
 * | 99 | 2 |
 * | 199 | 3 |
 *
 * <h2>Character Sets</h2>
 *
 * Caution: Does not apply any character-set conversion, it is assumed that all content
 * is stored and represented in UTF-8 (Unicode). Please make sure your database and
 * HTML-templates adjust to this.
 *
 * <h2>Permissions</h2>
 *
 * Authors without administrative access need special permissions to edit locales other than
 * the default locale.
 *
 * - TRANSLATE_ALL: Translate into all locales
 * - Translate_<locale>: Translate a specific locale. Only available for all locales set in
 *   `Translatable::set_allowed_locales()`.
 *
 * Note: If user-specific view permissions are required, please overload `SiteTree->canView()`.
 *
 * <h2>Uninstalling/Disabling</h2>
 *
 * Disabling Translatable after creating translations will lead to all
 * pages being shown in the default sitetree regardless of their language.
 * It is advised to start with a new database after uninstalling Translatable,
 * or manually filter out translated objects through their "Locale" property
 * in the database.
 *
 * @see http://doc.silverstripe.org/doku.php?id=multilingualcontent
 *
 * @author Ingo Schommer <ingo (at) silverstripe (dot) com>
 * @author Michael Gall <michael (at) wakeless (dot) net>
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 *
 * @package translatable
 */
class Translatable extends DataExtension implements TestOnly {

	const QUERY_LOCALE_FILTER_ENABLED = 'Translatable.LocaleFilterEnabled';

	/**
	 * The 'default' language.
	 * @var string
	 */
	protected static $default_locale = 'en_US';

	/**
	 * The language in which we are reading dataobjects.
	 *
	 * @var string
	 */
	protected static $current_locale = null;

	/**
	 * A cached list of existing tables
	 *
	 * @var mixed
	 */
	protected static $tableList = null;

	/**
	 * An array of fields that can be translated.
	 * @var array
	 */
	protected $translatableFields = null;

	/**
	 * A map of the field values of the original (untranslated) DataObject record
	 * @var array
	 */
	protected $original_values = null;

	/**
	 * If this is set to TRUE then {@link augmentSQL()} will automatically add a filter
	 * clause to limit queries to the current {@link get_current_locale()}. This camn be
	 * disabled using {@link disable_locale_filter()}
	 *
	 * @var bool
	 */
	protected static $locale_filter_enabled = true;

	/**
	 * @var array All locales in which a translation can be created.
	 * This limits the choice in the CMS language dropdown in the
	 * "Translation" tab, as well as the language dropdown above
	 * the CMS tree. If not set, it will default to showing all
	 * common locales.
	 */
	protected static $allowed_locales = null;

	/**
	 * @var boolean Check other languages for URLSegment values (only applies to {@link SiteTree}).
	 * Turn this off to handle language setting yourself, e.g. through language-specific subdomains
	 * or URL path prefixes like "/en/mypage".
	 */
	private static $enforce_global_unique_urls = true;

	/**
	 * Exclude these fields from translation
	 *
	 * @var array
	 * @config
	 */
	private static $translate_excluded_fields = array(
		'ViewerGroups',
		'EditorGroups',
		'CanViewType',
		'CanEditType',
		'NewTransLang',
		'createtranslation'
	);

	/**
	 * Reset static configuration variables to their default values
	 */
	static function reset() {
		self::enable_locale_filter();
		self::$default_locale = 'en_US';
		self::$current_locale = null;
		self::$allowed_locales = null;
	}

	/**
	 * Choose the language the site is currently on.
	 *
	 * If $_GET['locale'] is currently set, then that locale will be used.
	 * Otherwise the member preference (if logged
	 * in) or default locale will be used.
	 *
	 * @todo Re-implement cookie and member option
	 *
	 * @param $langsAvailable array A numerical array of languages which are valid choices (optional)
	 * @return string Selected language (also saved in $current_locale).
	 */
	static function choose_site_locale($langsAvailable = array()) {
		self::set_current_locale(self::default_locale());
		return self::$current_locale;
	}

	/**
	 * Get the current reading language.
	 * This value has to be set before the schema is built with translatable enabled,
	 * any changes after this can cause unintended side-effects.
	 *
	 * @return string
	 */
	static function default_locale() {
		return self::$default_locale;
	}


	/**
	 * Get the current reading language.
	 * If its not chosen, call {@link choose_site_locale()}.
	 *
	 * @return string
	 */
	static function get_current_locale() {
		return (self::$current_locale) ? self::$current_locale : self::choose_site_locale();
	}

	/**
	 * Set the reading language, either namespaced to 'site' (website content)
	 * or 'cms' (management backend). This value is used in {@link augmentSQL()}
	 * to "auto-filter" all SELECT queries by this language.
	 * See {@link disable_locale_filter()} on how to override this behaviour temporarily.
	 *
	 * @param string $lang New reading language.
	 */
	static function set_current_locale($locale) {
		self::$current_locale = $locale;
	}



	/**
	 * @return bool
	 */
	public static function locale_filter_enabled() {
		return self::$locale_filter_enabled;
	}

	/**
	 * Enables automatic filtering by locale. This is normally called after is has been
	 * disabled using {@link disable_locale_filter()}.
	 *
	 * @param $enabled (default true), if false this call is a no-op - see {@link disable_locale_filter()}
	 */
	public static function enable_locale_filter($enabled = true) {
		if ($enabled) {
			self::$locale_filter_enabled = true;
		}
	}

	/**
	 * Disables automatic locale filtering in {@link augmentSQL()}. This can be re-enabled
	 * using {@link enable_locale_filter()}.
	 *
	 * Note that all places that disable the locale filter should generally re-enable it
	 * before returning from that block of code (function, etc). This is made easier by
	 * using the following pattern:
	 *
	 * <code>
	 * $enabled = Translatable::disable_locale_filter();
	 * // do some work here
	 * Translatable::enable_locale_filter($enabled);
	 * return $whateverYouNeedTO;
	 * </code>
	 *
	 * By using this pattern, the call to enable the filter will not re-enable it if it
	 * was not enabled initially.  That will keep code that called your function from
	 * breaking if it had already disabled the locale filter since it will not expect
	 * calling your function to change the global state by re-enabling the filter.
	 *
	 * @return boolean true if the locale filter was enabled, false if it was not
	 */
	public static function disable_locale_filter() {
		$enabled = self::$locale_filter_enabled;
		self::$locale_filter_enabled = false;
		return $enabled;
	}




	function setOwner($owner, $ownerBaseClass = null) {
		parent::setOwner($owner, $ownerBaseClass);

		// setting translatable fields by inspecting owner - this should really be done in the constructor
		if($this->owner && $this->translatableFields === null) {
			$this->translatableFields = array_merge(
				array_keys($this->owner->db()),
				array_keys($this->owner->has_many()),
				array_keys($this->owner->many_many())
			);
			foreach (array_keys($this->owner->has_one()) as $fieldname) {
				$this->translatableFields[] = $fieldname.'ID';
			}
		}
	}

	// FIXME - REMOVING THIS BREAKS TEST, BUT ZERO TEST COVERAGE...
	static function get_extra_config($class, $extensionClass, $args = null) {
		$config = array();
		$config['defaults'] = array(
			"Locale" => Translatable::default_locale() // as an overloaded getter as well: getLang()
		);
		$config['db'] = array(
			"Locale" => "DBLocale",
			//"TranslationMasterID" => "Int" // optional relation to a "translation master"
		);
		return $config;
	}

	/**
	 * Check if a given SQLQuery filters on the Locale field
	 *
	 * @param SQLQuery $query
	 * @return boolean
	 */
	protected function filtersOnLocale($query) {
		foreach($query->getWhere() as $condition) {
			if(preg_match('/("|\'|`)Locale("|\'|`)/', $condition)) return true;
		}
	}

	/**
	 * Changes any SELECT query thats not filtering on an ID
	 * to limit by the current language defined in {@link get_current_locale()}.
	 * It falls back to "Locale='' OR Lang IS NULL" and assumes that
	 * this implies querying for the default language.
	 *
	 * Use {@link disable_locale_filter()} to temporarily disable this "auto-filtering".
	 */
	public function augmentSQL(SQLQuery &$query, DataQuery $dataQuery = null) {
		// If the record is saved (and not a singleton), and has a locale,
		// limit the current call to its locale. This fixes a lot of problems
		// with other extensions like Versioned
		if($this->owner->ID && !empty($this->owner->Locale)) {
			$locale = $this->owner->Locale;
		} else {
			$locale = Translatable::get_current_locale();
		}

		$baseTable = ClassInfo::baseDataClass($this->owner->class);
		if(
			$locale
			// unless the filter has been temporarily disabled
			&& self::locale_filter_enabled()
			// or it was disabled when the DataQuery was created
			&& $dataQuery->getQueryParam(self::QUERY_LOCALE_FILTER_ENABLED)
			// DataObject::get_by_id() should work independently of language
			&& !$query->filtersOnID()
			// the query contains this table
			// @todo Isn't this always the case?!
			&& array_search($baseTable, array_keys($query->getFrom())) !== false
			// or we're already filtering by Lang (either from an earlier augmentSQL()
			// call or through custom SQL filters)
			&& !$this->filtersOnLocale($query)
			//&& !$query->filtersOnFK()
		)  {
			$qry = sprintf('"%s"."Locale" = \'%s\'', $baseTable, Convert::raw2sql($locale));
			$query->addWhere($qry);
		}
	}

	function augmentDataQueryCreation(SQLQuery &$sqlQuery, DataQuery &$dataQuery) {
		$enabled = self::locale_filter_enabled();
		$dataQuery->setQueryParam(self::QUERY_LOCALE_FILTER_ENABLED, $enabled);
	}

	 // FIXME - NO TEST COVERAGE BUT REQUIRED
	function augmentDatabase() {
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		if($this->owner->class != $baseDataClass) return;

		$fields = array(
			'OriginalID' => 'Int',
			'TranslationGroupID' => 'Int',
		);
		$indexes = array(
			'OriginalID' => true,
			'TranslationGroupID' => true
		);

		// Add new tables if required
		DB::requireTable("{$baseDataClass}_translationgroups", $fields, $indexes);

		// Remove 2.2 style tables
		DB::dontRequireTable("{$baseDataClass}_lang");
		if($this->owner->hasExtension('Versioned')) {
			DB::dontRequireTable("{$baseDataClass}_lang_Live");
			DB::dontRequireTable("{$baseDataClass}_lang_versions");
		}
	}

	/**
	 * @todo Find more appropriate place to hook into database building
	 */
	public function requireDefaultRecords() {
		// @todo This relies on the Locale attribute being on the base data class, and not any subclasses
		if($this->owner->class != ClassInfo::baseDataClass($this->owner->class)) return false;

		// Permissions: If a group doesn't have any specific TRANSLATE_<locale> edit rights,
		// but has CMS_ACCESS_CMSMain (general CMS access), then assign TRANSLATE_ALL permissions as a default.
		// Auto-setting permissions based on these intransparent criteria is a bit hacky,
		// but unavoidable until we can determine when a certain permission code was made available first
		// (see http://open.silverstripe.org/ticket/4940)
		$groups = Permission::get_groups_by_permission(array(
			'CMS_ACCESS_CMSMain',
			'CMS_ACCESS_LeftAndMain',
			'ADMIN'
		));
		if($groups) foreach($groups as $group) {
			$codes = $group->Permissions()->column('Code');
			$hasTranslationCode = false;
			foreach($codes as $code) {
				if(preg_match('/^TRANSLATE_/', $code)) $hasTranslationCode = true;
			}
			// Only add the code if no more restrictive code exists
			if(!$hasTranslationCode) Permission::grant($group->ID, 'TRANSLATE_ALL');
		}

		// If the Translatable extension was added after the first records were already
		// created in the database, make sure to update the Locale property if
		// if wasn't set before
		$idsWithoutLocale = DB::query(sprintf(
			'SELECT "ID" FROM "%s" WHERE "Locale" IS NULL OR "Locale" = \'\'',
			ClassInfo::baseDataClass($this->owner->class)
		))->column();
		if(!$idsWithoutLocale) return;



	}

	/**
	 * Add a record to a "translation group",
	 * so its relationship to other translations
	 * based off the same object can be determined later on.
	 * See class header for further comments.
	 *
	 * @param int $originalID Either the primary key of the record this new translation is based on,
	 *  or the primary key of this record, to create a new translation group
	 * @param boolean $overwrite
	 */
	public function addTranslationGroup($originalID, $overwrite = false) {
		if(!$this->owner->exists()) return false;

		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		$existingGroupID = $this->getTranslationGroup($originalID);

		// Remove any existing groups if overwrite flag is set
		if($existingGroupID && $overwrite) {
			$sql = sprintf(
				'DELETE FROM "%s_translationgroups" WHERE "TranslationGroupID" = %d AND "OriginalID" = %d',
				$baseDataClass,
				$existingGroupID,
				$this->owner->ID
			);
			DB::query($sql);
			$existingGroupID = null;
		}

		// Add to group (only if not in existing group or $overwrite flag is set)
		if(!$existingGroupID) {
			$sql = sprintf(
				'INSERT INTO "%s_translationgroups" ("TranslationGroupID","OriginalID") VALUES (%d,%d)',
				$baseDataClass,
				$originalID,
				$this->owner->ID
			);
			DB::query($sql);
		}
	}

	/**
	 * Gets the translation group for the current record.
	 * This ID might equal the record ID, but doesn't have to -
	 * it just points to one "original" record in the list.
	 *
	 * @return int Numeric ID of the translationgroup in the <classname>_translationgroup table
	 */
	public function getTranslationGroup() {
		if(!$this->owner->exists()) return false;

		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		return DB::query(
			sprintf(
				'SELECT "TranslationGroupID" FROM "%s_translationgroups" WHERE "OriginalID" = %d',
				$baseDataClass,
				$this->owner->ID
			)
		)->value();
	}

	/**
	 * Removes a record from the translation group lookup table.
	 * Makes no assumptions on other records in the group - meaning
	 * if this happens to be the last record assigned to the group,
	 * this group ceases to exist.
	 */
	public function removeTranslationGroup() {
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		DB::query(
			sprintf('DELETE FROM "%s_translationgroups" WHERE "OriginalID" = %d', $baseDataClass, $this->owner->ID)
		);
	}

	// FIXME - no coverage but is called
	function isVersionedTable($table) {
		return false;
	}


	/**
	 * Recursively creates translations for parent pages in this language
	 * if they aren't existing already. This is a necessity to make
	 * nested pages accessible in a translated CMS page tree.
	 * It would be more userfriendly to grey out untranslated pages,
	 * but this involves complicated special cases in AllChildrenIncludingDeleted().
	 *
	 * {@link SiteTree->onBeforeWrite()} will ensure that each translation will get
	 * a unique URL across languages, by means of {@link SiteTree::get_by_link()}
	 * and {@link Translatable->alternateGetByURL()}.
	 */
	function onBeforeWrite() {
		// If language is not set explicitly, set it to current_locale.
		// This might be a bit overzealous in assuming the language
		// of the content, as a "single language" website might be expanded
		// later on. See {@link requireDefaultRecords()} for batch setting
		// of empty Locale columns on each dev/build call.
		if(!$this->owner->Locale) {
			$this->owner->Locale = Translatable::get_current_locale();
		}

		// Specific logic for SiteTree subclasses.
		// If page has untranslated parents, create (unpublished) translations
		// of those as well to avoid having inaccessible children in the sitetree.
		// Caution: This logic is very sensitve to infinite loops when translation status isn't determined properly
		// If a parent for the newly written translation was existing before this
		// onBeforeWrite() call, it will already have been linked correctly through createTranslation()
		if(
			class_exists('SiteTree')
			&& $this->owner->hasField('ParentID')
			&& $this->owner instanceof SiteTree
		) {
			if(
				!$this->owner->ID
				&& $this->owner->ParentID
				&& !$this->owner->Parent()->hasTranslation($this->owner->Locale)
			) {
				$parentTranslation = $this->owner->Parent()->createTranslation($this->owner->Locale);
				$this->owner->ParentID = $parentTranslation->ID;
			}
		}

		// Has to be limited to the default locale, the assumption is that the "page type"
		// dropdown is readonly on all translations.
		if($this->owner->ID && $this->owner->Locale == Translatable::default_locale()) {
			$changedFields = $this->owner->getChangedFields();
			$changed = isset($changedFields['ClassName']);

			if ($changed && $this->owner->hasExtension('Versioned')) {
				// this is required because when publishing a node the before/after
				// values of $changedFields['ClassName'] will be the same because
				// the record was already written to the stage/draft table and thus
				// the record was updated, and then publish('Stage', 'Live') is
				// called, which uses forceChange, which will make all the fields
				// act as though they are changed, although the before/after values
				// will be the same
				// So, we load one from the current stage and test against it
				// This is to prevent the overhead of writing all translations when
				// the class didn't actually change.
				$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
				$currentStage = Versioned::current_stage();
				$fresh = Versioned::get_one_by_stage(
					$baseDataClass,
					Versioned::current_stage(),
					'"ID" = ' . $this->owner->ID,
					null
				);
				if ($fresh) {
					$changed = $changedFields['ClassName']['after'] != $fresh->ClassName;
				}
			}

			if($changed) {
				$this->owner->ClassName = $changedFields['ClassName']['before'];
				$translations = $this->owner->getTranslations();
				$this->owner->ClassName = $changedFields['ClassName']['after'];
				if($translations) foreach($translations as $translation) {
					$translation->setClassName($this->owner->ClassName);
					$translation = $translation->newClassInstance($translation->ClassName);
					$translation->populateDefaults();
					$translation->forceChange();
					$translation->write();
				}
			}
		}

		// see onAfterWrite()
		if(!$this->owner->ID) {
			$this->owner->_TranslatableIsNewRecord = true;
		}
	}

	function onAfterWrite() {
		// hacky way to determine if the record was created in the database,
		// or just updated
		if($this->owner->_TranslatableIsNewRecord) {
			// this would kick in for all new records which are NOT
			// created through createTranslation(), meaning they don't
			// have the translation group automatically set.
			$translationGroupID = $this->getTranslationGroup();
			if(!$translationGroupID) {
				$this->addTranslationGroup(
					$this->owner->_TranslationGroupID ? $this->owner->_TranslationGroupID : $this->owner->ID
				);
			}
			unset($this->owner->_TranslatableIsNewRecord);
			unset($this->owner->_TranslationGroupID);
		}

	}



	/**
	 * Attempt to get the page for a link in the default language that has been translated.
	 *
	 * @param string $URLSegment
	 * @param int|null $parentID
	 * @return SiteTree
	 */
	public function alternateGetByLink($URLSegment, $parentID) {
		// If the parentID value has come from a translated page,
		// then we need to find the corresponding parentID value
		// in the default Locale.
		if (
			is_int($parentID)
			&& $parentID > 0
			&& ($parent = DataObject::get_by_id('SiteTree', $parentID))
			&& ($parent->isTranslation())
		) {
			$parentID = $parent->getTranslationGroup();
		}

		// Find the locale language-independent of the page
		self::disable_locale_filter();
		$default = SiteTree::get()->where(sprintf (
			'"URLSegment" = \'%s\'%s',
			Convert::raw2sql($URLSegment),
			(is_int($parentID) ? " AND \"ParentID\" = $parentID" : null)
		))->First();
		self::enable_locale_filter();

		return $default;
	}

	//-----------------------------------------------------------------------------------------------//


	public function updateRelativeLink(&$base, &$action) {
		// Prevent home pages for non-default locales having their urlsegments
		// reduced to the site root.
		if($base === null && $this->owner->Locale != self::default_locale()){
			$base = $this->owner->URLSegment;
		}
	}



	function extendWithSuffix($table) {
		return $table;
	}

	/**
	 * Gets all related translations for the current object,
	 * excluding itself. See {@link getTranslation()} to retrieve
	 * a single translated object.
	 *
	 * Getter with $stage parameter is specific to {@link Versioned} extension,
	 * mostly used for {@link SiteTree} subclasses.
	 *
	 * @param string $locale
	 * @param string $stage
	 * @return DataObjectSet
	 */
	function getTranslations($locale = null, $stage = null) {
		if(!$this->owner->exists()) return new ArrayList();

		// HACK need to disable language filtering in augmentSQL(),
		// as we purposely want to get different language
		// also save state of locale-filter, revert to this state at the
		// end of this method
		$localeFilterEnabled = false;
		if(self::locale_filter_enabled()) {
			self::disable_locale_filter();
			$localeFilterEnabled = true;
		}

		$translationGroupID = $this->getTranslationGroup();

		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		$filter = sprintf('"%s_translationgroups"."TranslationGroupID" = %d', $baseDataClass, $translationGroupID);
		if($locale) {
			$filter .= sprintf(' AND "%s"."Locale" = \'%s\'', $baseDataClass, Convert::raw2sql($locale));
		} else {
			// exclude the language of the current owner
			$filter .= sprintf(' AND "%s"."Locale" != \'%s\'', $baseDataClass, $this->owner->Locale);
		}
		$currentStage = Versioned::current_stage();
		$joinOnClause = sprintf('"%s_translationgroups"."OriginalID" = "%s"."ID"', $baseDataClass, $baseDataClass);
		if($this->owner->hasExtension("Versioned")) {
			if($stage) Versioned::reading_stage($stage);
			$translations = Versioned::get_by_stage(
				$baseDataClass,
				Versioned::current_stage(),
				$filter,
				null
			)->leftJoin("{$baseDataClass}_translationgroups", $joinOnClause);
			if($stage) Versioned::reading_stage($currentStage);
		} else {
			$class = $this->owner->class;
			$translations = $baseDataClass::get()
				->where($filter)
				->leftJoin("{$baseDataClass}_translationgroups", $joinOnClause);
		}

		// only re-enable locale-filter if it was enabled at the beginning of this method
		if($localeFilterEnabled) {
			self::enable_locale_filter();
		}

		return $translations;
	}

	/**
	 * Gets an existing translation based on the language code.
	 * Use {@link hasTranslation()} as a quicker alternative to check
	 * for an existing translation without getting the actual object.
	 *
	 * @param String $locale
	 * @return DataObject Translated object
	 */
	function getTranslation($locale, $stage = null) {
		$translations = $this->getTranslations($locale, $stage);
		return ($translations) ? $translations->First() : null;
	}



	/**
	 * Enables automatic population of SiteConfig fields using createTranslation if
	 * created outside of the Translatable module
	 * @var boolean
	 */
	public static $enable_siteconfig_generation = true;




	/**
	 * Get a list of languages with at least one element translated in (including the default language)
	 *
	 * @param string $className Look for languages in elements of this class
	 * @param string $where Optional SQL WHERE statement
	 * @return array Map of languages in the form locale => langName
	 */
	static function get_existing_content_languages($className = 'SiteTree', $where = '') {
		$baseTable = ClassInfo::baseDataClass($className);
		$query = new SQLQuery("Distinct \"Locale\"","\"$baseTable\"",$where, '', "\"Locale\"");
		$dbLangs = $query->execute()->column();
		$langlist = array_merge((array)Translatable::default_locale(), (array)$dbLangs);
		$returnMap = array();
		$allCodes = array_merge(
			Config::inst()->get('i18n', 'all_locales'),
			Config::inst()->get('i18n', 'common_locales')
		);
		foreach ($langlist as $langCode) {
			if($langCode && isset($allCodes[$langCode])) {
				if(is_array($allCodes[$langCode])) {
					$returnMap[$langCode] = $allCodes[$langCode]['name'];
				} else {
					$returnMap[$langCode] = $allCodes[$langCode];
				}
			}
		}
		return $returnMap;
	}

	/**
	 * Get the RelativeLink value for a home page in another locale. This is found by searching for the default home
	 * page in the default language, then returning the link to the translated version (if one exists).
	 *
	 * @return string
	 */
	public static function get_homepage_link_by_locale($locale) {
		$originalLocale = self::get_current_locale();

		self::set_current_locale(self::default_locale());
		$original = SiteTree::get_by_link(RootURLController::config()->default_homepage_link);
		self::set_current_locale($originalLocale);

		if($original) {
			if($translation = $original->getTranslation($locale)) return trim($translation->RelativeLink(true), '/');
		}
	}


	/**
	 * @deprecated 2.4 Use {@link Translatable::get_homepage_link_by_locale()}
	 */
	static function get_homepage_urlsegment_by_locale($locale) {
		user_error (
			'Translatable::get_homepage_urlsegment_by_locale() is deprecated, please use get_homepage_link_by_locale()',
			E_USER_NOTICE
		);

		return self::get_homepage_link_by_locale($locale);
	}



	/**
	 * Return a piece of text to keep DataObject cache keys appropriately specific
	 */
	function cacheKeyComponent() {
		return 'locale-'.self::get_current_locale();
	}

	/**
	 * Extends the SiteTree::validURLSegment() method, to do checks appropriate
	 * to Translatable
	 *
	 * @return bool
     */
	public function augmentValidURLSegment() {
		$reEnableFilter = false;
		if(!Config::inst()->get('Translatable', 'enforce_global_unique_urls')) {
			self::enable_locale_filter();
		} elseif(self::locale_filter_enabled()) {
			self::disable_locale_filter();
			$reEnableFilter = true;
		}

		$IDFilter = ($this->owner->ID) ? "AND \"SiteTree\".\"ID\" <> {$this->owner->ID}" :  null;
		$parentFilter = null;

		if (Config::inst()->get('SiteTree', 'nested_urls')) {
			if($this->owner->ParentID) {
				$parentFilter = " AND \"SiteTree\".\"ParentID\" = {$this->owner->ParentID}";
			} else {
				$parentFilter = ' AND "SiteTree"."ParentID" = 0';
			}
		}

		$existingPage = SiteTree::get()
			// disable get_one cache, as this otherwise may pick up results from when locale_filter was on
			->where("\"URLSegment\" = '{$this->owner->URLSegment}' $IDFilter $parentFilter")->First();
		if($reEnableFilter) self::enable_locale_filter();

		// By returning TRUE or FALSE, we overrule the base SiteTree->validateURLSegment() logic
		return !$existingPage;
	}

}
