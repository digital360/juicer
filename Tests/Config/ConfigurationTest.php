<?php

use Keboola\Juicer\Config\Configuration,
    Keboola\Juicer\Config\JobConfig;
use Keboola\Temp\Temp;
use Keboola\CsvTable\Table;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends ExtractorTestCase
{
    public function testStoreResults()
    {
        $resultsPath = './data/storeResultsTest' . uniqid();

        $this->storeResults($resultsPath, 'full', false);
    }

    public function testIncrementalResults()
    {
        $resultsPath = './data/storeResultsTest' . uniqid();

        $this->storeResults($resultsPath, 'incremental', true);
    }

    public function testDefaultBucketResults()
    {
        $resultsPath = './data/storeResultsDefaultBucket' . uniqid();

        $configuration = new Configuration($resultsPath, 'defaultBucket', new Temp('test'));

        $files = [
            Table::create('first', ['col1', 'col2']),
            Table::create('second', ['col11', 'col12'])
        ];

        $files[0]->writeRow(['a', 'b']);
        $files[1]->writeRow(['c', 'd']);

        $configuration->storeResults($files);

        foreach(new \DirectoryIterator('./Tests/data/storeResultsDefaultBucket/out/tables/') as $file) {
            $this->assertFileEquals($file->getPathname(), $resultsPath . '/out/tables/' . $file->getFilename());
        }

        $this->rmDir($resultsPath);
    }

    protected function storeResults($resultsPath, $name, $incremental)
    {
        $configuration = new Configuration($resultsPath, $name, new Temp('test'));

        $files = [
            Table::create('first', ['col1', 'col2']),
            Table::create('second', ['col11', 'col12'])
        ];

        $files[0]->writeRow(['a', 'b']);
        $files[1]->writeRow(['c', 'd']);

        $configuration->storeResults($files, $name, true, $incremental);

        foreach(new \DirectoryIterator('./Tests/data/storeResultsTest/out/tables/' . $name) as $file) {
            $this->assertFileEquals($file->getPathname(), $resultsPath . '/out/tables/' . $name . '/' . $file->getFilename());
        }

        $this->rmDir($resultsPath);
    }

    public function testGetConfigMetadata()
    {
        $path = __DIR__ . '/../data/metadataTest';

        $configuration = new Configuration($path, 'test', new Temp('test'));
        $yml = $configuration->getConfigMetadata();

        $this->assertEquals(Yaml::parse("some: data\nmore:\n    woah: 'such recursive'"), $yml);

        $noConfiguration = new Configuration('asdf', 'test', new Temp('test'));
        $this->assertEquals(null, $noConfiguration->getConfigMetadata());
    }

    public function testSaveConfigMetadata()
    {
        $resultsPath = './data/metadataTest' . uniqid();

        $configuration = new Configuration($resultsPath, 'test', new Temp('test'));

        $configuration->saveConfigMetadata([
            'some' => 'data',
            'more' => [
                'woah' => 'such recursive'
            ]
        ]);

        $this->assertFileEquals('./Tests/data/metadataTest/out/state.yml', $resultsPath . '/out/state.yml');

        $this->rmDir($resultsPath);
    }

    public function testGetConfig()
    {
        $configuration = new Configuration('./Tests/data/recursive', 'test', new Temp('test'));

        $config = $configuration->getConfig();

        $yml = Yaml::parse(file_get_contents('./Tests/data/recursive/config.yml'));

        $jobs = $config->getJobs();
        $this->assertEquals(JobConfig::create($yml['parameters']['config']['jobs'][0]), reset($jobs));

        $this->assertEquals($yml['parameters']['config']['outputBucket'], $config->getAttribute('outputBucket'));
    }

    public function testGetMultipleConfigs()
    {
        $configuration = new Configuration('./Tests/data/iterations', 'test', new Temp('test'));

        $configs = $configuration->getMultipleConfigs();

        $yml = Yaml::parse(file_get_contents('./Tests/data/iterations/config.yml'));

        foreach($yml['parameters']['iterations'] as $i => $params) {
            $this->assertEquals(array_replace(['id' => $yml['parameters']['config']['id']], $params), $configs[$i]->getAttributes());
        }
        $this->assertEquals($configs[0]->getJobs(), $configs[1]->getJobs());
        $this->assertContainsOnlyInstancesOf('\Keboola\Juicer\Config\Config', $configs);
        $this->assertCount(count($yml['parameters']['iterations']), $configs);
    }

    public function testGetMultipleConfigsSingle()
    {
        $configuration = new Configuration('./Tests/data/iteration', 'test', new Temp('test'));

        $configs = $configuration->getMultipleConfigs();

        $yml = Yaml::parse(file_get_contents('./Tests/data/iteration/config.yml'));


        $this->assertContainsOnlyInstancesOf('\Keboola\Juicer\Config\Config', $configs);
        $this->assertCount(1, $configs);

        $this->assertEquals($configuration->getConfig(), $configs[0]);
    }

    public function testGetYaml()
    {
        $configuration = new Configuration('./Tests/data/iteration', 'test', new Temp('test'));

        $result = self::callMethod($configuration, 'getYaml', ['/config.yml', 'parameters', 'config', 'id']);

        self::assertEquals('multiCfg', $result);
    }

    protected function rmDir($dirPath)
    {
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        return rmdir($dirPath);
    }
}
