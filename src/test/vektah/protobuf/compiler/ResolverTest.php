<?php


use vektah\protobuf\compiler\Resolver;
use PHPUnit_Framework_TestCase as TestCase;

class ResolverTest extends TestCase {
    public function testResolverRelativePath()
    {
        $resolver = new Resolver();
        $thing = new StdClass();

        $resolver->define(['foo', 'bar'], 'baz', $thing);

        $this->assertEquals($thing, $resolver->fetch(['foo', 'bar'], 'baz')->definition);
    }
}
