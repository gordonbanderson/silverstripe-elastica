<?php


/**
 */
class TermVectorTest extends ElasticsearchTestBase
{
    public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

    public function testGetVector()
    {
        $fp = $this->objFromFixture('FlickrPhotoTO', 'photo0023');
        $termVectors = $this->service->getTermVectors($fp);
        $terms = array_keys($termVectors);
        sort($terms);
        $expected = array('Description', 'Description.shingles', 'Description.standard',
            'ShutterSpeed',
            'TestMethod', 'TestMethod.shingles', 'TestMethod.standard',
            'TestMethodHTML', 'TestMethodHTML.shingles', 'TestMethodHTML.standard',
            'Title', 'Title.autocomplete', 'Title.shingles', 'Title.standard', );
        $this->assertEquals($expected, $terms);

        // Now check the title terms
        // Original is 'Image taken from page 386 of ''The Bab Ballads, with which are included
        // Songs of a Savoyard ... With 350 illustrations by the author'''

        $expected = array(350, 386, 'author', 'bab', 'ballad', 'from', 'illustr', 'image', 'includ',
            'page', 'savoyard', 'song', 'taken', 'which', );
        $this->assertEquals($expected, array_keys($termVectors['Title']['terms']));

        $expected = array(350, 386, 'author', 'bab', 'ballads', 'from', 'illustrations', 'image',
            'included', 'page', 'savoyard', 'songs', 'taken', 'which', );
        $this->assertEquals($expected, array_keys($termVectors['Title.standard']['terms']));
    }
}
