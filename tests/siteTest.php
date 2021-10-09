<?php

namespace torrentupload\tests;

use datagutten\tools\files\files;
use PHPUnit\Framework\TestCase;

class siteTest extends TestCase
{
    public function testTemplates()
    {
        $site_folder = files::path_join(__DIR__, '..', 'templates', 'test');
        @mkdir($site_folder, 0777, true);
        $site_folder = realpath($site_folder);
        $site = new TestSite();
        $this->assertEquals($site_folder, $site->getSiteFolder());
    }
}
