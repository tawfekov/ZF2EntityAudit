<?php
namespace ZF2EntityAuditTest ;

use ZF2EntityAudit\Utils\SimpleDiff;
use ZF2EntityAudit\Utils\ArrayDiff;

class UtilsTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @dataProvider dataDiff
     * @param $old
     * @param $new
     * @param $output
     * @return void
     */
    public function testSimpleDiff($old, $new, $output)
    {
        $diff = new SimpleDiff();
        $d = $diff->htmlDiff($old, $new);

        $this->assertEquals($output, $d);
    }

    public function testArrayDiff()
    {
        $old = array(
            "0" => "hello",
            "1" => "testing",
            "2" => "diffarray"
        );
        $new = array(
            "0" => "hello" ,
            "1" => "TESTing",
            "2" => "diff_array"
        );
        
        $output = array(
            "0" => array("old" => "" , "new" => "" , "same" => "hello"),
            "1" => array("old" => "testing" , "new" => "TESTing" , "same" => ""),
            "2" => array("old" => "diffarray" , "new" => "diff_array" , "same" => "")
        );
        $diff = new ArrayDiff();
        $d = $diff->diff($old, $new);
        $this->assertEquals($output,$d);

    }

    static public function dataDiff()
    {
        return array(
            array('Foo', 'foo', '<del>Foo</del> <ins>foo</ins> '),
            array('Foo Foo', 'Foo', 'Foo <del>Foo</del> '),
            array('Foo', 'Foo Foo', 'Foo <ins>Foo</ins> '),
            array('Foo Bar Baz', 'Foo Foo Foo', 'Foo <del>Bar Baz</del> <ins>Foo Foo</ins> '),
            array('Foo Bar Baz', 'Foo Baz', 'Foo <del>Bar</del> Baz '),
        );
    }
}
