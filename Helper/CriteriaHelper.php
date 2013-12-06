<?php

namespace BrunoChirez\FetchBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;

/**
 * Helper class for building criteria easily.
 */
class CriteriaHelper
{

	private $repository;
	private $defaultLimit;
	
    public function __construct(Repository $repository, $defaultLimit)
    {
        $this->repository = $repository;
        $this->defaultLimit = $defaultLimit;
    }

    /**
     * Returns published content that is located under $pathString and matching $contentTypeIdentifier.
     * The whole subtree will be passed through to find content.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $rootLocation Root location we want to start content search from.
     * @param string[] $includeContentTypeIdentifiers Array of ContentType identifiers we want content to match.
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion Additional criterion for filtering.
     * @param int|null $limit Max number of items to retrieve. If not provided, default limit will be used.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
	function getContentList(Location $rootLocation, array $includeContentTypeIdentifiers = array(), Criterion $criterion = null, $limit = null)
	{
		$criteria = array(
            new Criterion\Subtree($rootLocation->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE)
        );
		
		if($includeContentTypeIdentifiers)
            $criteria[] = new Criterion\ContentTypeIdentifier($includeContentTypeIdentifiers);
		
		if(!empty( $criterion))
            $criteria[] = $criterion;
			
		$query = new Query(
			array(
                'criterion' => new Criterion\LogicalAnd( $criteria ),
                'sortClauses' => array( new SortClause\DatePublished(Query::SORT_DESC))
            )
        );
        $query->limit = $limit ?: $this->defaultLimit;
		
		return $this->buildContentListFromSearchResult($this->repository->getSearchService()->findContent($query));
	}
	
    /**
     * Builds a Content list from $searchResult.
     * Returned array consists of a hash of Content objects, indexed by their ID.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Search\SearchResult $searchResult
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    private function buildContentListFromSearchResult(SearchResult $searchResult)
    {
        $contentList = array();
        foreach($searchResult->searchHits as $searchHit)
        {
            $contentList[$searchHit->valueObject->contentInfo->id] = $searchHit->valueObject;
        }

        return $contentList;
    }
}