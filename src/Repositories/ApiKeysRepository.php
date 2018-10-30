<?php

declare(strict_types=1);

/**
 * This file is part of Scout Extended.
 *
 * (c) Algolia Team <contact@algolia.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Algolia\ScoutExtended\Repositories;

use function is_string;
use Algolia\AlgoliaSearch\SearchClient;
use Illuminate\Contracts\Cache\Repository;

/**
 * @internal
 */
final class ApiKeysRepository
{
    /**
     * Holds the search key.
     */
    private const SEARCH_KEY = 'scout-extended.user-data.search-key';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * @var \Algolia\AlgoliaSearch\SearchClient
     */
    private $client;

    /**
     * ApiKeysRepository constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Algolia\AlgoliaSearch\SearchClient $client
     *
     * @return void
     */
    public function __construct(Repository $cache, SearchClient $client)
    {
        $this->cache = $cache;
        $this->client = $client;
    }

    /**
     * @param  string|object $searchable
     *
     * @return string
     */
    public function getSearchKey($searchable): string
    {
        $searchable = is_string($searchable) ? new $searchable : $searchable;

        $searchableAs = $searchable->searchableAs();

        $searchKey = $this->cache->get(self::SEARCH_KEY);

        if ($searchKey === null) {
            $id = config('app.name').'::searchKey';

            $keys = $this->client->listApiKeys()['keys'];
            $searchKey = null;

            foreach ($keys as $key) {
                if ($key['description'] === $id) {
                    $searchKey = $key['value'];
                }
            }

            $searchKey = $searchKey ?? $this->client->addApiKey([
                    'acl' => ['search'],
                    'description' => config('app.name').'::searchKey',
                ])->getBody()['key'];

            $this->cache->put(self::SEARCH_KEY, $searchKey, 1440);
        }

        return $this->client::generateSecuredApiKey($searchKey, [
            'restrictIndices' => $searchableAs,
        ]);
    }
}
