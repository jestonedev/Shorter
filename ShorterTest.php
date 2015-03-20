<?php
/**
 * Created by PhpStorm.
 * User: Ignatov
 * Date: 20.03.2015
 * Time: 12:43
 */

namespace ShorterNS;
include_once 'Shorter.php';

class ShorterTest extends \PHPUnit_Framework_TestCase {

    public function testDictionaryPath()
    {
        $shorter = new Shorter();
        $this->assertNotEquals($shorter->Dictionary(),null);
    }

    public function testShorting1()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Начальник', 4),'Нач.');
    }

    public function testShorting2()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Заместитель начальника отдела', 16),'Зам. нач. отдела');
    }

    public function testShorting3()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Заместитель начальника отдела', 14),'Зам. нач. отд.');
    }

    public function testShorting4()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Заместитель начальника отдела', 10),'Зам. нач.');
    }

    public function testShorting5()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Заместитель начальника отдела по администрированию и обеспечению безопасности системных, телекоммуникационных и программно-технических комплексова', 64),
            'Зам. нач. отд. по адм. и обесп. без. сист. тел. и прогр. компл.');
    }

    public function testShorting6()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Главный специалист, ответственный секретарь комиссии по делам несовершеннолетних и защите их прав', 64),
            'Главн. спец. отв. секр. ком. по дел. несовершен. и защ. их прав');
    }

    public function testShorting7()
    {
        $shorter = new Shorter();
        $this->assertEquals($shorter->Translate('Главный специалист, ответственный секретарь комиссии по делам несовершеннолетних и защите их прав', 200),
            'Главный специалист, ответственный секретарь комиссии по делам несовершеннолетних и защите их прав');
    }
}
 