<?php

class ElasticaPaginatedList extends PaginatedList {
	public function addSimiilarSearchLink($searchPageLink) {
		$replacementList = new ArrayList();
		foreach ($this->list as $item) {
			$item->SimilarSearchLink = $searchPageLink.'similar/'.$item->ClassName.'/'.$item->ID;
			$replacementList->push($item);
		}
		$this->list = $replacementList;
	}
}
