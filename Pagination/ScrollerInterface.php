<?php
namespace Keboola\Juicer\Pagination;

use Keboola\Juicer\Client\ClientInterface,
    Keboola\Juicer\Client\RequestInterface,
    Keboola\Juicer\Config\JobConfig;

interface ScrollerInterface
{
    /**
     * @param ClientInterface $client
     * @param $jobConfig $jobConfig
     * @return RequestInterface|false
     */
    public function getFirstRequest(ClientInterface $client, JobConfig $jobConfig);

    /**
     * @param ClientInterface $client
     * @param $jobConfig $jobConfig
     * @param mixed $response
     * @param array $data
     * @return RequestInterface|false
     */
    public function getNextRequest(ClientInterface $client, JobConfig $jobConfig, $response, $data);

    /**
     * Reset the pagination pointer
     */
    public function reset();

    public static function create(array $config);
}
