<?php

class PageControllerTemplateOverrideExtension extends DataExtension implements TestOnly {
	/* Counter for number of times the template override method  has been called */
	private static $ctr = 0;

	public function useTemplateOverride($data) {
		self::$ctr++;
	}

	public static function getTemplateOverrideCounter() {
		return self::$ctr;
	}
}
