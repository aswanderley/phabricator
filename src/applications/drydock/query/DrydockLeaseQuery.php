<?php

final class DrydockLeaseQuery extends DrydockQuery {

  private $ids;
  private $phids;
  private $resourcePHIDs;
  private $statuses;
  private $datasourceQuery;
  private $needCommands;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withResourcePHIDs(array $phids) {
    $this->resourcePHIDs = $phids;
    return $this;
  }

  public function withStatuses(array $statuses) {
    $this->statuses = $statuses;
    return $this;
  }

  public function withDatasourceQuery($query) {
    $this->datasourceQuery = $query;
    return $this;
  }

  public function newResultObject() {
    return new DrydockLease();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function willFilterPage(array $leases) {
    $resource_phids = array_filter(mpull($leases, 'getResourcePHID'));
    if ($resource_phids) {
      $resources = id(new DrydockResourceQuery())
        ->setParentQuery($this)
        ->setViewer($this->getViewer())
        ->withPHIDs(array_unique($resource_phids))
        ->execute();
      $resources = mpull($resources, null, 'getPHID');
    } else {
      $resources = array();
    }

    foreach ($leases as $key => $lease) {
      $resource = null;
      if ($lease->getResourcePHID()) {
        $resource = idx($resources, $lease->getResourcePHID());
        if (!$resource) {
          $this->didRejectResult($lease);
          unset($leases[$key]);
          continue;
        }
      }
      $lease->attachResource($resource);
    }

    return $leases;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->resourcePHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'resourcePHID IN (%Ls)',
        $this->resourcePHIDs);
    }

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'phid IN (%Ls)',
        $this->phids);
    }

    if ($this->statuses !== null) {
      $where[] = qsprintf(
        $conn,
        'status IN (%Ls)',
        $this->statuses);
    }

    if ($this->datasourceQuery !== null) {
      $where[] = qsprintf(
        $conn,
        'id = %d',
        (int)$this->datasourceQuery);
    }

    return $where;
  }

}
