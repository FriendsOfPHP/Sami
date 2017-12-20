<?php

namespace Sami\Tests;

use Blackfire\Bridge\PhpUnit\TestCaseTrait as BlackfireTestCaseTrait;
use Blackfire\Profile;
use PHPUnit\Framework\TestCase;
use Sami\Sami;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationTest extends TestCase
{
    use BlackfireTestCaseTrait;

    private $bf;
    private $sami;

    protected function setUp()
    {
        $dir = sys_get_temp_dir().'/sami_integ';
        $this->clearCache($dir);

        $this->sami = new Sami(dirname(__DIR__).'/Console', array(
            'build_dir' => $dir.'/build',
            'cache_dir' => $dir.'/cache',
            'insert_todos' => true,
        ));
    }

    protected function tearDown()
    {
        $this->clearCache(sys_get_temp_dir().'/sami_integ');
    }

    /**
     * @group blackfire
     * @requires extension blackfire
     * @dataProvider getStorageData
     */
    public function testStorage($primedCache, $writeCalls, $readCalls)
    {
        $sami = $this->sami;

        if ($primedCache) {
            // prime the cache
            $sami['project']->parse();
        }

        $config = new Profile\Configuration();
        $config
            ->defineMetric(new Profile\Metric('sami.storage.write_calls', array('=Sami\Store\JsonStore::writeClass')))
            ->defineMetric(new Profile\Metric('sami.storage.read_calls', array('=Sami\Reflection\ClassReflection::fromArray')))
            ->assert('metrics.sami.storage.write_calls.count == '.$writeCalls, $writeCalls.' write calls')
            // depending on the order in which classes are loaded, we might have an extra call
            ->assert('metrics.sami.storage.read_calls.count <= '.$readCalls, $readCalls.' read calls')
        ;

        $profile = $this->assertBlackfire($config, function () use ($sami) {
            $sami['project']->parse();
        });
    }

    public function getStorageData()
    {
        return array(
            array(true, 0, 6),
            array(false, 5, 0),
        );
    }

    private function clearCache()
    {
        $dir = sys_get_temp_dir().'/sami_integ';
        $fs = new Filesystem();
        $fs->remove($dir);
    }
}
