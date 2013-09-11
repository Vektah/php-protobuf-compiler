<?php

use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Process\Process;

class GenerationTest extends TestCase {
    /** @var  Process the compile process that ran before this test. */
    static $compile;
    static $tmpdir;

    public static function setUpBeforeClass()
    {
        self::$tmpdir = $dir = '/tmp/pbtest' . md5(time());
        mkdir($dir, 0775, true);

        file_put_contents("$dir/test.proto", '
            message SearchRequest {
                required string query = 1;

                message PageDetails {
                    optional int32 number = 1;
                    optional int32 result_per_page = 2 [default = 10];
                }

                optional PageDetails page_details = 2;

                enum Corpus {
                    UNIVERSAL = 0;
                    WEB = 1;
                    IMAGES = 2;
                    LOCAL = 3;
                    NEWS = 4;
                    PRODUCTS = 5;
                    VIDEO = 6;
                }

                optional Corpus corpus = 3 [default = UNIVERSAL];
            }
        ');

        self::$compile = new Process(__DIR__ . "/../../../../../bin/pprotoc compile $dir/test.proto --out=$dir/out --namespace 'testns'");
        self::$compile->run();

        require_once("$dir/out/testns/SearchRequest.php");
        require_once("$dir/out/testns/searchrequest/PageDetails.php");
        require_once("$dir/out/testns/searchrequest/Corpus.php");
    }

    public static function tearDownAfterClass()
    {
        unlink(self::$tmpdir . "/out/testns/SearchRequest.php");
        unlink(self::$tmpdir . "/out/testns/searchrequest/Corpus.php");
        unlink(self::$tmpdir . "/out/testns/searchrequest/PageDetails.php");
        unlink(self::$tmpdir . '/test.proto');
        rmdir(self::$tmpdir . '/out/testns/searchrequest');
        rmdir(self::$tmpdir . '/out/testns');
        rmdir(self::$tmpdir . '/out');
        rmdir(self::$tmpdir);
    }


    /**
     * If something breaks make sure the compilers output is displayed.
     */
    protected function onNotSuccessfulTest(Exception $e)
    {
        echo self::$compile->getOutput();
        echo self::$compile->getErrorOutput();

        throw $e;
    }

    public function testSimpleOutput()
    {
        $request = new \testns\SearchRequest();
        $request->query = "asdf";
        $request->page_details = new \testns\searchrequest\PageDetails();
        $request->page_details->number = 10;
        $request->page_details->result_per_page = 50;
        $request->corpus = \testns\searchrequest\Corpus::NEWS;

        $string = $request->serializeToString();

        $this->assertNotNull($string);
        $unpacked = new \testns\SearchRequest();
        $unpacked->parseFromString($string);

        $this->assertEquals($request, $unpacked);
    }
}
