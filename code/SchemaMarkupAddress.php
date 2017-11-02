<?php

class SchemaMarkupPostalAddress extends SchemaMarkup
{
	private static $singular_name = 'Address';
	private static $plural_name = 'Addresses';
	
	private static $db = array(
		'Address' => 'Varchar(255)',
		'PostOfficeBox' => 'Varchar(255)',
		'City' => 'Varchar(255)',
		'State' => 'Varchar(255)',
		'PostalCode' => 'Varchar(255)',
		'Country' => 'Varchar(255)',
		'PhoneNumber' => 'Varchar(255)',
		'FaxNumber' => 'Varchar(255)',
		'AddressExtra' => 'Text'
	);
	
	private static $has_one = array(
		'SiteConfig' => 'SiteConfig'
	);
		
	private static $has_many = array(
		'SchemaMarkupOpeningHoursSpecifications' => 'SchemaMarkupOpeningHoursSpecification'
	);
	
	private static $defaults = array(
		'Country' => 'US'
	);
	
	private static $summary_fields = array(
		'Address' => 'Address',
		'PostOfficeBox' => 'Po Box',
		'City' => 'City',
		'State' => 'State',
		'PostalCode' => 'Zip',
		'Country' => 'Country',
		'PhoneNumber' => 'Phone',
		'FaxNumber' => 'Fax'
	);
	
	public function canCreate($member = null) { return true; }
	public function canDelete($member = null) { return true; }
	public function canEdit($member = null)   { return true; }
	public function canView($member = null)   { return true; }
	
	public function getTitle()
	{
		return $this->Address.', '.$this->City;
	}
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->removeByName('SchemaMarkupOpeningHoursSpecifications');
		$fields->replaceField('AddressExtra', CodeEditorField::create('AddressExtra','Extra Schema Markup (added to address data)')
			->addExtraClass('stacked') );
		if ($this->ID)
		{
			$fields->addFieldToTab('Root.Hours', GridField::create(
				'SchemaMarkupOpeningHoursSpecifications',
				'Hours',
				$this->SchemaMarkupOpeningHoursSpecifications(),
				GridFieldConfig_RecordEditor::create()
			));
		}
		return $fields;
	}
	
	public function buildSchemaMarkup()
	{
		$markup = array(
			'@type' => 'PostalAddress'
		);
		if ($this->Address) { $markup['streetAddress'] = $this->Address; }
		if ($this->PostOfficeBox) { $markup['postOfficeBoxNumber'] = $this->PostOfficeBox; }
		if ($this->City) { $markup['addressLocality'] = $this->City; }
		if ($this->State) { $markup['addressRegion'] = $this->State; }
		if ($this->PostalCode) { $markup['postalCode'] = $this->PostalCode; }
		if ($this->PhoneNumber) { $markup['telephone'] = $this->PhoneNumber; }
		if ($this->FaxNumber) { $markup['faxNumber'] = $this->FaxNumber; }
		
		if ($this->SchemaMarkupOpeningHoursSpecifications()->Count())
		{
			$hoursMarkup = array();
			foreach($this->SchemaMarkupOpeningHoursSpecifications() as $hours)
			{
				if ($hours->buildSchemaMarkup())
				{
					$hoursMarkup[] = $hours->buildSchemaMarkup();
				}
			}
			$markup['hoursAvailable'] = $hoursMarkup;
		}
		return $markup;
	}
}













