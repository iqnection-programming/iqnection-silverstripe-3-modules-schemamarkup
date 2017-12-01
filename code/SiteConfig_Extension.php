<?php

class SchemaMarkup_SiteConfig_Extension extends DataExtension
{
	private static $db = array(
		'SchemaEnableMarkup' => 'Boolean',
		'SchemaBusinessType' => 'Varchar(255)',
		'SchemaName' => 'Varchar(255)',
		'SchemaDescription' => 'Text',
		'SchemaExtra' => 'Text',
		'SchemaPriceRange' => 'Varchar(5)',
		'SchemaExtraSameAs' => 'Text',
		'SchemaMarkupCache' => 'Text'
	);
	
	private static $has_one = array(
		'SchemaMainAddress' => 'SchemaMarkupPostalAddress',
		'SchemaBusinessImage' => 'Image',
		'SchemaLogo' => 'Image'
	);
	
	private static $has_many = array(
		'SchemaMarkupPostalAddresses' => 'SchemaMarkupPostalAddress'
	);
	
	public function updateCMSFields(FieldList &$fields)
	{
		$ctab = $fields->findOrMakeTab('Root.Developer.SchemaMarkup.Company');
		$ctab->push( CheckboxField::create('SchemaEnableMarkup','Enable Schema Markup') );
		$ctab->push( TextField::create('SchemaBusinessType','Business Type')
			->setDescription('Defaults to Organization. See <a href="http://schema.org/Organization" target="_blank">http://schema.org/Organization</a> for more types')
			->setAttribute('placeholder','Organization') );
		$ctab->push( TextField::create('SchemaName','Company Name')
			->setDescription('Defaults to site Title if blank')
			->setAttribute('placeholder',$this->owner->Title) );
		$ctab->push( TextareaField::create('SchemaDescription','Company Description')
			->setDescription('Defaults to Home page meta description if blank')
			->setAttribute('placeholder',SiteTree::get()->filter('ClassName','HomePage')->First()->MetaDescription) );
		$priceRanges = array();
		for($pri=1;$pri<=4;$pri++) { $priceRanges[str_repeat('$',$pri)] = str_repeat('$',$pri); }
		$ctab->push( DropdownField::create('SchemaPriceRange','Price Range')
			->setSource($priceRanges)
			->setEmptyString('-- Select --')
			->setDescription('BE HONEST!!') );
		$ctab->push( UploadField::create('SchemaLogo','Logo')
			->setAllowedExtensions(array('jpg','jpeg','png','gif'))
			->setFolderName('schema')
			->setDescription('Will default to theme logo.png') );
		$ctab->push( UploadField::create('SchemaBusinessImage','Business Image')
			->setAllowedExtensions(array('jpg','jpeg','png','gif'))
			->setFolderName('schema')
			->setDescription('Will default to uploaded logo, or theme logo.png') );
			
		$atab = $fields->findOrMakeTab('Root.Developer.SchemaMarkup.Address');
		$fields->removeByName('SchemaMainAddressID');
		if ($this->owner->SchemaMarkupPostalAddresses()->count() > 1)
		{
			$atab->push( DropdownField::create('SchemaMainAddressID','Main Address')
				->setSource($this->owner->SchemaMarkupPostalAddresses()->map('ID','getTitle'))
				->setEmptyString('-- Select --') );
		}
		$atab->push( GridField::create(
			'SchemaMarkupPostalAddresss',
			'Addresses',
			$this->owner->SchemaMarkupPostalAddresses(),
			GridFieldConfig_RecordEditor::create()
		));
		
		$fields->addFieldToTab('Root.Developer.SchemaMarkup.Extra', TextareaField::create('SchemaExtraSameAs','Additional SameAs Items')
			->setDescription('Should be full URLs, One per line') );
		$fields->addFieldToTab('Root.Developer.SchemaMarkup.Extra', CodeeditorField::create('SchemaExtra','Additional JSON Markup to add to Root Object')
			->setDescription('Must use Double Quotes in JSON objects')
			->addExtraClass('stacked')
			->setRows(20) );
		
		if ($markup = $this->SchemaMarkup())
		{
			$ptab = $fields->findOrMakeTab('Root.Developer.SchemaMarkup.Preview');
			$ptab->push( LiteralField::create('SchemaMarkup','<div><h2>JSON Data:</h2><pre>'.print_r($markup,1).'</pre></div>') );
		}
		return $fields;
	}
	
	public function buildSchemaMarkup()
	{
		$markup = array(
			'@context' => 'http://schema.org',
			'@type' => ($this->owner->SchemaBusinessType) ? $this->owner->SchemaBusinessType : 'Organization',
			'url' => Director::absoluteBaseURL()
		);
		if ($this->owner->SchemaName) { $markup['name'] = $this->owner->SchemaName; }
			elseif ($this->owner->Title) { $markup['name'] = $this->owner->Title; }
		if ($this->owner->SchemaDescription) { $markup['description'] = $this->owner->SchemaDescription; }
			elseif ($homeDescription = SiteTree::get()->filter('ClassName','HomePage')->First()->MetaDescription) { $markup['description'] = $homeDescription; }
		if ($this->owner->SchemaLogo()->Exists()) { $markup['logo'] = $this->owner->SchemaLogo()->AbsoluteLink(); }
			elseif (Director::fileExists($this->owner->ThemeDir().'/images/logo.png')) { $markup['logo'] = Director::absoluteURL($this->owner->ThemeDir().'/images/logo.png'); }
		if ($this->owner->SchemaBusinessImage()->Exists()) { $markup['image'] = $this->owner->SchemaBusinessImage()->AbsoluteLink(); }
			elseif ($this->owner->SchemaLogo()->Exists()) { $markup['image'] = $this->owner->SchemaLogo()->AbsoluteLink(); }
			elseif (Director::fileExists($this->owner->ThemeDir().'/images/logo.png')) { $markup['image'] = Director::absoluteURL($this->owner->ThemeDir().'/images/logo.png'); }
		if ($this->owner->SchemaPriceRange) { $markup['priceRange'] = $this->owner->SchemaPriceRange; }	
			
		if ($this->owner->SchemaMarkupPostalAddresses()->Count())
		{
			$addresses = array();
			foreach($this->owner->SchemaMarkupPostalAddresses() as $address)
			{
				if ($address = $address->buildSchemaMarkup())
				{
					$addresses[] = $address;
				}
			}
			if (count($addresses))
			{
				if (count($addresses) > 1)
				{
					if ($this->owner->SchemaMainAddress()->Exists())
					{
						$markup['address'] = $this->owner->SchemaMainAddress()->buildSchemaMarkup();
					}
					$markup['location'] = $addresses;
				}
				else
				{
					$markup['address'] = $addresses;
				}
			}
			
		}
		
		// sameAs
		if ($sameAs = $this->owner->getSameAsSchemaMarkup())
		{
			$markup['sameAs'] = $sameAs;
		}
		
		if ($extra = $this->owner->SchemaExtra)
		{
			// make the text a valid JSON object
			if (!preg_match('/^{/',$extra)) { $extra = '{'.$extra; }
			if (!preg_match('/}$/',$extra)) { $extra .= '}'; }
			if ($extra = json_decode(trim($extra),1))
			{
				foreach($extra as $extraName => $extraValue)
				{
					$markup[$extraName] = $extraValue;
				}
			}
		}
		
		if (!count($markup))
		{
			return false;
		}
		$markup = array_reverse($markup,true);
		$markup['url'] = Director::absoluteBaseURL();
		$markup['@type'] = ($this->owner->SchemaBusinessType) ? $this->owner->SchemaBusinessType : 'Organization';
		$markup['@context'] = 'http://schema.org';
		$markup = array_reverse($markup,true);
		return $markup;			
	}
	
	public function getSameAsSchemaMarkup()
	{
		$markup = array();
		$socialSites = array(
			'Facebook',
			'Twitter',
			'LinkedIn',
			'Pinterest',
			'Instagram',
			'GooglePlus',
			'Blog',
			'Flickr',
			'YouTube',
			'Vimeo',
			'Yelp',
			'Tumblr',
			'Houzz',
		);
		foreach($socialSites as $socialSite)
		{
			if ($socialLink = $this->getSocialLink($socialSite)) { $markup[] = $socialLink; }
		}
		if ($HubSpotBlogPage = SiteTree::get()->Filter('ClassName','HubSpotBlogPage')->First())
		{
			$markup[] = $HubSpotBlogPage->BlogURL;
		}
		if ($BlogPage = SiteTree::get()->Filter('ClassName','BlogPage')->First())
		{
			$markup[] = $BlogPage->BlogURL;
		}
		
		if ($SchemaExtraSameAs = $this->owner->SchemaExtraSameAs)
		{
			foreach(explode("\n",$SchemaExtraSameAs) as $extraSameAs)
			{
				if ($extraSameAs = trim($extraSameAs))
				{
					$markup[] = $extraSameAs;
				}
			}
		}
		$this->owner->extend('updateSameAsSchemaMarkup',$markup);
		return (count($markup)) ? $markup : false;
	}
	
	public function getSocialLink($name)
	{
		return ($this->owner->{'Use'.$name} && $this->owner->{$name.'URL'}) ? Director::absoluteURL($this->owner->{$name.'URL'}) : false;
	}
	
	public function onBeforeWrite()
	{
		$this->owner->SchemaMarkupCache = '';
		if ($this->owner->SchemaEnableMarkup)
		{
			$this->owner->SchemaMarkupCache = $this->generateSchemaMarkup();
		}
	}
	
	public function SchemaMarkup()
	{
		if ($this->owner->SchemaEnableMarkup)
		{
			if (!$this->owner->SchemaMarkupCache)
			{
				$this->owner->SchemaMarkupCache = $this->generateSchemaMarkup();
				$this->owner->write();
			}
			return $this->owner->SchemaMarkupCache;
		}
		return false;
	}
	
	public function generateSchemaMarkup()
	{
		if ($markup = $this->owner->buildSchemaMarkup())
		{
			if (defined('JSON_PRETTY_PRINT'))
			{
				$markup = json_encode($markup,JSON_PRETTY_PRINT);
			}
			else
			{
				$markup = json_encode($markup);
			}
		}
		$this->owner->extend('updateSchemaMarkup',$markup);
		return $markup;
	}
}






