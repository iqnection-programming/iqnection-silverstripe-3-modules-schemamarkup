<?php

class SchemaMarkupOpeningHoursSpecification extends SchemaMarkup
{
	private static $singular_name = 'Business Hours';
	private static $plural_name = 'Business Hours';
	
	private static $db = array(
		'DayOfWeek' => "Enum('Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday')",
		'OpenTime' => 'Varchar(255)',
		'CloseTime' => 'Varchar(255)',
	);
	
	private static $has_one = array(
		'SchemaMarkupPostalAddress' => 'SchemaMarkupPostalAddress'
	);
	
	private static $summary_fields = array(
		'DayOfWeek' => 'Day',
		'OpenTime' => 'Open',
		'CloseTime' => 'Close'
	);
	
	public function canCreate($member = null) { return true; }
	public function canDelete($member = null) { return true; }
	public function canEdit($member = null)   { return true; }
	public function canView($member = null)   { return true; }
	
	public function getTitle()
	{
		return $this->DayOfWeek;
	}
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->dataFieldByName('OpenTime')->setDescription('Enter a complete time');
		$fields->dataFieldByName('CloseTime')->setDescription('Enter a complete time');
		return $fields;
	}
	
	public function buildSchemaMarkup()
	{
		if (!$this->OpenTime && !$this->CloseTime) { return false; }
		return array(
			'@type' => 'OpeningHoursSpecification',
			'dayOfWeek' => 'http://schema.org/'.$this->DayOfWeek,
			'opens' => date('H:i:s',strtotime('2000-01-01 '.$this->OpenTime)),
			'closes' => date('H:i:s',strtotime('2000-01-01 '.$this->CloseTime)),
		);
	}
}








