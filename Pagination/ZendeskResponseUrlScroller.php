<?php

namespace Keboola\Juicer\Pagination;

use GuzzleHttp\Url;
use Keboola\Juicer\Client\RestClient;
use Keboola\Juicer\Config\JobConfig;
use GuzzleHttp\Query;

class ZendeskResponseUrlScroller extends AbstractResponseScroller implements ScrollerInterface
{
    const NEXT_PAGE_FILTER_MINUTES = 6;

    /**
     * @var string
     */
    protected $urlParam = 'next_page';

    /**
     * @var bool
     */
    protected $includeParams = false;

    /**
     * @var bool
     */
    protected $paramIsQuery = false;

    /**
     * ZendeskResponseUrlScroller constructor.
     * @param array $config
     *      [
     *          'urlKey' => string // Key in the JSON response containing the URL
     *          'includeParams' => bool // Whether to include params from config
     *          'paramIsQuery' => bool // Pick parameters from the scroll URL and use them with job configuration
     *      ]
     */
    public function __construct($config)
    {
        if (!empty($config['urlKey'])) {
            $this->urlParam = $config['urlKey'];
        }
        if (isset($config['includeParams'])) {
            $this->includeParams = (bool)$config['includeParams'];
        }
        if (isset($config['paramIsQuery'])) {
            $this->paramIsQuery = (bool)$config['paramIsQuery'];
        }
    }

    /**
     * @inheritdoc
     */
    public function getNextRequest(RestClient $client, JobConfig $jobConfig, $response, $data)
    {
        $nextUrl = \Keboola\Utils\getDataFromPath($this->urlParam, $response, '.');

        if (empty($nextUrl)) {
            return false;
        }

        // start_time validation
        // https://developer.zendesk.com/rest_api/docs/core/incremental_export#incremental-ticket-export
        $now = new \DateTime();
        $startDateTime = \DateTime::createFromFormat(
            'U',
            Url::fromString($nextUrl)->getQuery()->get('start_time')
        );

        if ($startDateTime && $startDateTime > $now->modify(sprintf("-%d minutes", self::NEXT_PAGE_FILTER_MINUTES))) {
            return false;
        }

        $config = $jobConfig->getConfig();

        if (!$this->includeParams) {
            $config['params'] = [];
        }

        if (!$this->paramIsQuery) {
            $config['endpoint'] = $nextUrl;
        } else {
            // Create an array from the query string
            $responseQuery = Query::fromString(ltrim($nextUrl, '?'))->toArray();
            $config['params'] = array_replace($config['params'], $responseQuery);
        }

        return $client->createRequest($config);
    }
}
