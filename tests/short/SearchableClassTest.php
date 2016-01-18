<?php

/**
 * Test the functionality SearchableClass.
 */
class SearchableClassTest extends ElasticsearchTestBase
{
    public function testCMSFields()
    {
        $sc = new SearchableClass();
        $sc->Name = 'TestField';
        $sc->InSiteTree = false;
        $sc->write();

        $fields = $sc->getCMSFields();

        $tab = $this->checkTabExists($fields, 'Main');

        //Check fields
        $nf = $this->checkFieldExists($tab, 'Name');
        $this->assertTrue($nf->isDisabled());

        //FIXME - why does this fail?
        $this->assertTrue($nf->isReadOnly());

        //Check for existence of grid field
        $nf = $this->checkFieldExists($tab, 'SearchableField');
    }

    public function testIsInSiteTreeHumanReadable()
    {
        $sc = new SearchableClass();
        $this->assertEquals('No', $sc->IsInSiteTreeHumanReadable());
        $sc->InSiteTree = true;
        $this->assertEquals('Yes', $sc->IsInSiteTreeHumanReadable());
    }
}
