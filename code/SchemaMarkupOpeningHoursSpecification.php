<?php

class SchemaMarkupOpeningHoursSpecification extends SchemaMarkup
{
	private static $singular_name = 'Business Hours';
	private static $plural_name = 'Business Hours';
	
	private static $db = array(
		'DayOfWeek' => "Varchar(255)", //"Enum('Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday')",
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
		$daysOfWeek = [
			'Sunday' => 'Sunday',
			'Monday' => 'Monday',
			'Tuesday' => 'Tuesday',
			'Wednesday' => 'Wednesday',
			'Thursday' => 'Thursday',
			'Friday' => 'Friday',
			'Saturday' => 'Saturday'
		];
		$fields->replaceField('DayOfWeek', CheckboxSetField::create('DayOfWeek','Day(s) Of Week',$daysOfWeek) );
		$fields->dataFieldByName('OpenTime')->setDescription('Enter a complete time');
		$fields->dataFieldByName('CloseTime')->setDescription('Enter a complete time');
		return $fields;
	}
	
	public function validate()
	{
		$result = parent::validate();
		if (!$this->DayOfWeek) { $result->error('Please select at least one day of the week'); }
		return $result;
	}
	
	public function buildSchemaMarkup()
	{
		if (!$this->OpenTime && !$this->CloseTime) { return false; }
		$days = [];
		foreach(explode(',',$this->DayOfWeek) as $day)
		{
			$days[] = 'http://schema.org/'.$day;
		}
		return array(
			'@type' => 'OpeningHoursSpecification',
			'dayOfWeek' => $days,
			'opens' => date('H:i:s',strtotime('2000-01-01 '.$this->OpenTime)),
			'closes' => date('H:i:s',strtotime('2000-01-01 '.$this->CloseTime)),
		);
	}
}








