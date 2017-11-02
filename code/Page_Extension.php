<?php

class SchemaMarkup_Page_Extension extends DataExtension
{
	
}

class SchemaMarkup_Page_Controller_Extension extends Extension
{
	public function onAfterInit()
	{
		if ($schemaMarkup = SiteConfig::current_site_config()->SchemaMarkup())
		{
			Requirements::insertHeadTags('<script type="application/ld+json">'.$schemaMarkup.'</script>','SchemaMarkup');
		}
	}
}