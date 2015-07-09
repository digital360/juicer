<?php

namespace Keboola\ExtractorBundle\Config;

use	Keboola\Utils\Utils,
	Keboola\Utils\Exception\JsonDecodeException;
use	Keboola\ExtractorBundle\Exception\UserException;

/**
 * Carries a job configuration
 */
class JobConfig {
	/** @var string */
	protected $jobId;

	/** @var JobConfig[] */
	protected $childJobs = [];

	/** @var array */
	protected $config;

	/**
	 * Create an instance of config from config assoc. array
	 * @param array $config
	 * 	example:
	 * 		[
	 *			'id' => 'id',
	 * 			'recursive' => [{"id" => 2, "params": {"accountId": "account_id"}}],
	 *			'endpoint' => ...
	 *		]
	 * where accountId is a placeholder used as {accountId} in child job's endpoint
	 * and account_id points to a key in a single response object (within an array)
	 * @param array $configs Array of all configs to create recursion
	 * @return JobConfig
	 */
	public static function create(array $config)
	{
		if (empty($config['id'])) {
			// This'll change if the job settings change FIXME
			$config['id'] = md5(serialize($config));
		}

		$job = new self($config['id'], $config);
		if (!empty($config['children'])) {
			foreach($config['children'] as $child) {
				$job->addChildJob(self::create($child));
			}
		}

		return $job;
	}

	/**
	 * @param string $jobId
	 * @param array $config
	 */
	public function __construct($jobId, array $config)
	{
		$this->jobId = $jobId;
		$this->config = $config;
	}

	/**
	 * @param JobConfig $job
	 */
	public function addChildJob(self $job)
	{
		$this->childJobs[$job->getJobId()] = $job;
	}

	/**
	 * @return JobConfig[]
	 */
	public function getChildJobs()
	{
		return $this->childJobs;
	}

	/**
	 * @return string
	 */
	public function getJobId()
	{
		return $this->jobId;
	}

	/**
	 * @return array
	 */
	public function getConfig()
	{
		return $this->config;
	}
}
