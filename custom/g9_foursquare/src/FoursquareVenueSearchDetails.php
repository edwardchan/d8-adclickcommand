<?php

namespace Drupal\g9_foursquare;

/**
 * The class that performs the Foursquare venue search.
 */
class FoursquareVenueSearchDetails extends FoursquarePublicQuery {

  protected $venueId;

  /**
   * Returns the venue details from the Foursquare API.
   *
   * @param string $venue_id
   *   The venue id to fetch details for.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with the venue details.
   */
  public function searchFor($venue_id) {
    $parameters = [];

    $this->venueId = $venue_id;

    return $this->execute($parameters);
  }

  /**
   * The query url arguments.
   */
  public function arguments() {
    return [
      'venues',
      $this->venueId,
    ];
  }

}
