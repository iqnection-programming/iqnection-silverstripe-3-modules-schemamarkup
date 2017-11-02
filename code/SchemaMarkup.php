<?php


class SchemaMarkup extends DataObject
{
	private static $db = array();

	public function canCreate($member = null) { return true; }
	public function canDelete($member = null) { return true; }
	public function canEdit($member = null)   { return true; }
	public function canView($member = null)   { return true; }

	public function onAfterWrite()
	{
		parent::onAfterWrite();
		SiteConfig::current_site_config()->SchemaMarkupCache = '';
		SiteConfig::current_site_config()->write();
	}
}