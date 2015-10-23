<?php

use Keboola\Juicer\Config\JobConfig;

class ResponseScrollerTestCase extends ExtractorTestCase
{
    protected function getConfig()
    {
        return new JobConfig('test', [
            'endpoint' => 'test',
            'params' => [
                'a' => 1,
                'b' => 2
            ]
        ]);
    }
}