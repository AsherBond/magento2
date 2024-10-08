<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Profiler\Test\Unit\Driver\Standard;

use Magento\Framework\Profiler\Driver\Standard\Stat;
use PHPUnit\Framework\TestCase;

class StatTest extends TestCase
{
    /**
     * @var Stat
     */
    protected $_stat;

    protected function setUp(): void
    {
        $this->_stat = new Stat();
    }

    /**
     * Test start and stop methods of \Magento\Framework\Profiler\Driver\Standard\Stat
     *
     * @dataProvider actionsDataProvider
     * @param array $actions
     * @param array $expected
     */
    public function testActions(array $actions, array $expected)
    {
        foreach ($actions as $actionData) {
            list($action, $timerId, $time, $realMemory, $emallocMemory) = array_values($actionData);
            $this->_executeTimerAction($action, $timerId, $time, $realMemory, $emallocMemory);
        }

        if (empty($expected)) {
            $this->fail("\$expected mustn't be empty");
        }

        foreach ($expected as $timerId => $expectedTimer) {
            $actualTimer = $this->_stat->get($timerId);
            $this->assertIsArray($actualTimer, "Timer '{$timerId}' must be an array");
            $this->assertEquals($expectedTimer, $actualTimer, "Timer '{$timerId}' has unexpected value");
        }
    }

    /**
     * Data provider for testActions
     *
     * @return array
     */
    public static function actionsDataProvider()
    {
        return [
            'Start only once' => [
                'actions' => [
                    ['start', 'timer1', 'time' => 25, 'realMemory' => 1500, 'emallocMemory' => 10],
                ],
                'expected' => [
                    'timer1' => [
                        Stat::START => 25,
                        Stat::TIME => 0,
                        Stat::REALMEM => 0,
                        Stat::EMALLOC => 0,
                        Stat::REALMEM_START => 1500,
                        Stat::EMALLOC_START => 10,
                        Stat::COUNT => 1,
                    ],
                ],
            ],
            'Start only twice' => [
                'actions' => [
                    ['start', 'timer1', 'time' => 25, 'realMemory' => 1500, 'emallocMemory' => 10],
                    ['start', 'timer1', 'time' => 75, 'realMemory' => 2000, 'emallocMemory' => 20],
                ],
                'expected' => [
                    'timer1' => [
                        Stat::START => 75,
                        Stat::TIME => 0,
                        Stat::REALMEM => 0,
                        Stat::EMALLOC => 0,
                        Stat::REALMEM_START => 2000,
                        Stat::EMALLOC_START => 20,
                        Stat::COUNT => 2,
                    ],
                ],
            ],
            'Start and stop consequentially' => [
                'actions' => [
                    ['start', 'timer1', 'time' => 25, 'realMemory' => 1500, 'emallocMemory' => 10],
                    ['stop', 'timer1', 'time' => 75, 'realMemory' => 2000, 'emallocMemory' => 20],
                    ['start', 'timer1', 'time' => 200, 'realMemory' => 3000, 'emallocMemory' => 50],
                    ['stop', 'timer1', 'time' => 250, 'realMemory' => 4000, 'emallocMemory' => 80],
                ],
                'expected' => [
                    'timer1' => [
                        Stat::START => false,
                        Stat::TIME => 100,
                        Stat::REALMEM => 1500,
                        Stat::EMALLOC => 40,
                        Stat::REALMEM_START => 3000,
                        Stat::EMALLOC_START => 50,
                        Stat::COUNT => 2,
                    ],
                ],
            ],
            'Start and stop with inner timer' => [
                'actions' => [
                    ['start', 'timer1', 'time' => 25, 'realMemory' => 1500, 'emallocMemory' => 10],
                    ['start', 'timer2', 'time' => 50, 'realMemory' => 2000, 'emallocMemory' => 20],
                    ['stop', 'timer2', 'time' => 80, 'realMemory' => 2500, 'emallocMemory' => 25],
                    ['stop', 'timer1', 'time' => 100, 'realMemory' => 4200, 'emallocMemory' => 55],
                ],
                'expected' => [
                    'timer1' => [
                        Stat::START => false,
                        Stat::TIME => 75,
                        Stat::REALMEM => 2700,
                        Stat::EMALLOC => 45,
                        Stat::REALMEM_START => 1500,
                        Stat::EMALLOC_START => 10,
                        Stat::COUNT => 1,
                    ],
                    'timer2' => [
                        Stat::START => false,
                        Stat::TIME => 30,
                        Stat::REALMEM => 500,
                        Stat::EMALLOC => 5,
                        Stat::REALMEM_START => 2000,
                        Stat::EMALLOC_START => 20,
                        Stat::COUNT => 1,
                    ],
                ],
            ]
        ];
    }

    /**
     * Test get method with invalid timer id
     */
    public function testGetWithInvalidTimer()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Timer "unknown_timer" doesn\'t exist.');
        $this->_stat->get('unknown_timer');
    }

    /**
     * Test stop method with invalid timer id
     */
    public function testStopWithInvalidTimer()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Timer "unknown_timer" doesn\'t exist.');
        $this->_stat->stop('unknown_timer', 1, 2, 3);
    }

    /**
     * Test clear method
     */
    public function testClear()
    {
        $this->markTestSkipped('Skipped in #27500 due to testing protected/private methods and properties');

        $this->_stat->start('timer1', 1, 20, 10);
        $this->_stat->start('timer2', 2, 20, 10);
        $this->_stat->start('timer3', 3, 20, 10);
        $this->assertAttributeCount(3, '_timers', $this->_stat);

        $this->_stat->clear('timer1');
        $this->assertAttributeCount(2, '_timers', $this->_stat);

        $this->_stat->clear();
        $this->assertAttributeEmpty('_timers', $this->_stat);
    }

    /**
     * Test getFilteredTimerIds for sorting
     *
     * @dataProvider timersSortingDataProvider
     * @param array $timers
     * @param array $expectedTimerIds
     */
    public function testTimersSorting($timers, $expectedTimerIds)
    {
        foreach ($timers as $timerData) {
            list($action, $timerId) = $timerData;
            $this->_executeTimerAction($action, $timerId);
        }

        $this->assertEquals($expectedTimerIds, $this->_stat->getFilteredTimerIds());
    }

    /**
     * @return array
     */
    public static function timersSortingDataProvider()
    {
        return [
            'Without sorting' => [
                'timers' => [
                    ['start', 'root'],
                    ['start', 'root->init'],
                    ['stop', 'root->init'],
                    ['stop', 'root'],
                ],
                'expectedTimerIds' => ['root', 'root->init'],
            ],
            'Simple sorting' => [
                'timers' => [
                    ['start', 'root'],
                    ['start', 'root->di'],
                    ['stop', 'root->di'],
                    ['start', 'root->init'],
                    ['start', 'root->init->init_stores'],
                    ['start', 'root->init->init_stores->store_collection_load_after'],
                    ['stop', 'root->init->init_stores->store_collection_load_after'],
                    ['stop', 'root->init->init_stores'],
                    ['stop', 'root->init'],
                    ['start', 'root->dispatch'],
                    ['stop', 'root->dispatch'],
                    ['stop', 'root'],
                ],
                'expectedTimerIds' => [
                    'root',
                    'root->di',
                    'root->init',
                    'root->init->init_stores',
                    'root->init->init_stores->store_collection_load_after',
                    'root->dispatch',
                ],
            ],
            'Nested sorting' => [
                'timers' => [
                    ['start', 'root'],
                    ['start', 'root->init'],
                    ['start', 'root->system'],
                    ['start', 'root->system->init_config'],
                    ['stop', 'root->system->init_config'],
                    ['start', 'root->system->init_store'],
                    ['stop', 'root->system->init_store'],
                    ['stop', 'root->system'],
                    ['start', 'root->init->init_config'],
                    ['stop', 'root->init->init_config'],
                    ['stop', 'root->init'],
                    ['stop', 'root'],
                ],
                'expectedTimerIds' => [
                    'root',
                    'root->init',
                    'root->init->init_config',
                    'root->system',
                    'root->system->init_config',
                    'root->system->init_store',
                ],
            ]
        ];
    }

    /**
     * Test getFilteredTimerIds for filtering
     *
     * @dataProvider timersFilteringDataProvider
     * @param array $timers
     * @param array $thresholds
     * @param string $filterPattern
     * @param array $expectedTimerIds
     */
    public function testTimersFiltering($timers, $thresholds, $filterPattern, $expectedTimerIds)
    {
        foreach ($timers as $timerData) {
            list($action, $timerId, $time, $realMemory, $emallocMemory) = array_pad(array_values($timerData), 5, 0);
            $this->_executeTimerAction($action, $timerId, $time, $realMemory, $emallocMemory);
        }

        $this->assertEquals($expectedTimerIds, $this->_stat->getFilteredTimerIds($thresholds, $filterPattern));
    }

    /**
     * @return array
     */
    public static function timersFilteringDataProvider()
    {
        return [
            'Filtering by pattern' => [
                'timers' => [
                    ['start', 'root'],
                    ['start', 'root->init'],
                    ['stop', 'root->init'],
                    ['stop', 'root'],
                ],
                'thresholds' => [],
                'filterPattern' => '/^root$/',
                'expectedTimerIds' => ['root'],
            ],
            'Filtering by thresholds' => [
                'timers' => [
                    ['start', 'root', 'time' => 0, 'realMemory' => 0, 'emallocMemory' => 0],
                    ['start', 'root->init', 0],
                    ['start', 'root->init->init_cache', 'time' => 50, 'realMemory' => 1000],
                    ['stop', 'root->init->init_cache', 'time' => 100, 'realMemory' => 21000],
                    ['stop', 'root->init', 999],
                    ['stop', 'root', 'time' => 1000, 'realMemory' => 500, 'emallocMemory' => 0],
                ],
                'thresholds' => [
                    Stat::TIME => 1000,
                    Stat::REALMEM => 20000,
                ],
                'filterPattern' => null,
                // TIME >= 1000, REALMEM >= 20000
                'expectedTimerIds' => ['root', 'root->init->init_cache'],
            ]
        ];
    }

    /**
     * Test positive cases of fetch method
     *
     * @dataProvider fetchDataProvider
     * @param array $timers
     * @param array $expects
     */
    public function testFetch($timers, $expects)
    {
        foreach ($timers as $timerData) {
            list($action, $timerId, $time, $realMemory, $emallocMemory) = array_pad(array_values($timerData), 5, 0);
            $this->_executeTimerAction($action, $timerId, $time, $realMemory, $emallocMemory);
        }
        foreach ($expects as $expectedData) {
            /** @var bool|int|PHPUnit_Framework_Constraint $expectedValue */
            list($timerId, $key, $expectedValue) = array_values($expectedData);
            if (!is_scalar($expectedValue)) {
                $expectedValue->evaluate($this->_stat->fetch($timerId, $key));
            } else {
                $this->assertEquals($expectedValue, $this->_stat->fetch($timerId, $key));
            }
        }
    }

    /**
     * @return array
     */
    public static function fetchDataProvider()
    {
        return [
            [
                'timers' => [
                    ['start', 'root', 'time' => 0, 'realMemory' => 0, 'emallocMemory' => 0],
                    ['stop', 'root', 'time' => 1000, 'realMemory' => 500, 'emallocMemory' => 10],
                ],
                'expects' => [
                    [
                        'timerId' => 'root',
                        'key' => Stat::START,
                        'expectedValue' => false,
                    ],
                    [
                        'timerId' => 'root',
                        'key' => Stat::TIME,
                        'expectedValue' => 1000
                    ],
                    [
                        'timerId' => 'root',
                        'key' => Stat::REALMEM,
                        'expectedValue' => 500
                    ],
                    [
                        'timerId' => 'root',
                        'key' => Stat::EMALLOC,
                        'expectedValue' => 10
                    ],
                ],
            ],
            [
                'timers' => [
                    ['start', 'root', 'time' => 0],
                    ['stop', 'root', 'time' => 10],
                    ['start', 'root', 'time' => 20],
                    ['stop', 'root', 'time' => 30],
                ],
                'expects' => [
                    [
                        'timerId' => 'root',
                        'key' => Stat::AVG,
                        'expectedValue' => 10,
                    ],
                ]
            ],
            [
                'timers' => [['start', 'root', 'time' => 0]],
                'expects' => [
                    [
                        'timerId' => 'root',
                        'key' => Stat::TIME,
                        'expectedValue' => self::greaterThan(microtime(true)),
                    ],
                    [
                        'timerId' => 'root',
                        'key' => Stat::ID,
                        'expectedValue' => 'root'
                    ],
                ]
            ]
        ];
    }

    public function testFetchInvalidTimer()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Timer "foo" doesn\'t exist.');
        $this->_stat->fetch('foo', 'bar');
    }

    public function testFetchInvalidKey()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Timer "foo" doesn\'t have value for "bar".');
        $this->_stat->start('foo', 0, 0, 0);
        $this->_stat->fetch('foo', 'bar');
    }

    /**
     * Executes stop or start methods on $_stat object
     *
     * @param string $action
     * @param string $timerId
     * @param int $time
     * @param int $realMemory
     * @param int $emallocMemory
     */
    protected function _executeTimerAction($action, $timerId, $time = 0, $realMemory = 0, $emallocMemory = 0)
    {
        switch ($action) {
            case 'start':
                $this->_stat->start($timerId, $time, $realMemory, $emallocMemory);
                break;
            case 'stop':
                $this->_stat->stop($timerId, $time, $realMemory, $emallocMemory);
                break;
            default:
                $this->fail("Unexpected action '{$action}'");
                break;
        }
    }
}
