<?php

namespace vektah\protobuf\compiler;

use Exception;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Process\Process;

class JsonGenerationTest extends TestCase
{
    /** @var  Process the compile process that ran before this test. */
    private static $compile;
    private static $tmpdir;

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

                repeated int32 stuff = 4;
            }
        ');

        self::$compile = new Process(__DIR__ . "/../../../../../bin/pprotoc compile $dir/test.proto --out=$dir/out --namespace 'testns'");
        self::$compile->run();

        if (self::$compile->getExitCode()) {
            throw new Exception(self::$compile->getErrorOutput());
        }

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
        echo file_get_contents(self::$tmpdir . "/out/testns/SearchRequest.php");
        echo file_get_contents(self::$tmpdir . "/out/testns/SearchRequest.php");
        echo file_get_contents(self::$tmpdir . "/out/testns/SearchRequest.php");
        throw $e;
    }

    public function testSimpleOutput()
    {
        $request = new \testns\SearchRequest();
        $request->set_query("asdf");
        $request->set_stuff([1, 2, 3, 4]);
        $page_details = new \testns\searchrequest\PageDetails();
        $page_details->set_number(10);
        $page_details->set_result_per_page(50);
        $request->set_page_details($page_details);
        $request->set_corpus(\testns\searchrequest\Corpus::NEWS);

        $string = $request->toJson();

        $this->assertNotNull($string);
        $unpacked = \testns\SearchRequest::fromJson($string);

        $this->assertEquals($request->get_query(), $unpacked->get_query());
        $this->assertEquals($request->get_page_details()->get_number(), $unpacked->get_page_details()->get_number());
        $this->assertEquals($request->get_page_details()->get_result_per_page(), $unpacked->get_page_details()->get_result_per_page());
        $this->assertEquals($request->get_corpus(), $unpacked->get_corpus());
        $this->assertEquals([1, 2, 3, 4], $unpacked->get_stuff());
    }
}
