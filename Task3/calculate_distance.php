<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require(__DIR__.'/config.php');

define('ID', 'id');
define('TITLE', 'title');
define('YEAR', 'year');
define('AVG_RATING', 'rating');
define('COUNTRY', 'country');
define('DIRECTOR', 'director');
define('GENRES', 'genres');
define('GENRE', 'genre');

define('DEBUG', true);

class DistanceCalculator {
  private $genresOverall = array(
                                  'Adventure',
                                  'Animation',
                                  'Children',
                                  'Comedy',
                                  'Fantasy',
                                  'Romance',
                                  'Drama',
                                  'Action',
                                  'Crime',
                                  'Thriller',
                                  'Horror',
                                  'Mystery',
                                  'Sci-Fi',
                                  'IMAX',
                                  'Documentary',
                                  'War',
                                  'Musical',
                                  'Film-Noir',
                                  'Western',
                                  'Short'
                                );
  private $lowBoundary;
  private $highBoundary;
  private $movies;

  public function __construct($low, $high) {
    if (!is_int($low) || !is_int($high)) {
      throw new Exception('Boundary parameters must be integers!');
    }
    $this->movies = array();
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
    $stmt = $mysqli->prepare('SELECT * FROM movies m INNER JOIN movie_genres g ON m.id = g.movieID WHERE m.id > ? AND m.id <= ? ORDER BY m.id ASC');
    $stmt->bind_param('ii', $low, $high);
    $stmt->execute();
    $result = $stmt->get_result();
    while ( $row = $result->fetch_assoc() ) {
      if (empty($this->movies[$row[ID]])) {
        $this->movies[$row[ID]][TITLE] = $row[TITLE];
        $this->movies[$row[ID]][YEAR] = $row[YEAR];
        $this->movies[$row[ID]][AVG_RATING] = $row[AVG_RATING];
        $this->movies[$row[ID]][COUNTRY] = $row[COUNTRY];
        $this->movies[$row[ID]][DIRECTOR] = $row[DIRECTOR];
      }
      $this->movies[$row[ID]][GENRES][$row[GENRE]] = 100;
    }
    $result->close();
    $stmt->close();
    $mysqli->close();
    bcscale(10);
  }

  private function fillGenres($genres) {
    foreach ($this->genresOverall as $genre) {
      if(!array_key_exists($genre, $genres)) {
        $genres[$genre] = 0;
      }
    }
    return $genres;
  }

  private function euclidDistance(array $a, array $b) {
    if (count($a) != count($b)) {
      return false;
    }
    $sum = 0;
    foreach ($this->genresOverall as $genre) {
      $sum = bcadd( $sum, bcpow( bcsub($a[$genre], $b[$genre]), 2) );
    }
    return bcsqrt($sum);
  }

  public function calculateDistanceCustom(array $a, array $b) {
    if ( empty($a) || empty($b)
            || !array_key_exists(AVG_RATING, $a) || !array_key_exists(AVG_RATING, $b)
            || !array_key_exists(GENRES, $a) || !array_key_exists(GENRES, $b)
        ) {
      return false;
    }
    $euclid = $this->euclidDistance($this->fillGenres($a[GENRES]), $this->fillGenres($b[GENRES]));
    $numerator = bcmul(100, bcadd(1, bcdiv($euclid, 100)));
    $denominator = 1;

    $ratingFactor = bcadd(1, bcdiv( abs( bcsub($a[AVG_RATING], $b[AVG_RATING]) ), 5 ) );

    $yearFactor = bcadd( 1, bcdiv( abs(bcsub($a[YEAR], $b[YEAR])), 200 ) );

    // Calculate distance according to formula.
    // euclid_distance * (1 + year_difference * 0.5) /
    // (1 + 0.75 (if director matches) + 0.25 (if country matches) ) * 1/rating_factor
    $numerator = bcmul($numerator, $yearFactor);

    if ( $a[DIRECTOR] == $b[DIRECTOR]) {
      $denominator = bcadd($denominator, 0.75);
    }
    if ($a[COUNTRY] == $b[COUNTRY]) {
      $denominator = bcadd($denominator, 0.25);
    }
    $denominator = bcmul($denominator,  bcdiv(1, $ratingFactor)  );
    return bcdiv($numerator, $denominator);
  }

  public function calculateDistanceRating($a, $b) {
    $euclid = $this->euclidDistance($this->fillGenres($a[GENRES]), $this->fillGenres($b[GENRES]));
    $distance = bcmul(100, bcadd(1, bcdiv($euclid, 100)));

    $ratingFactor = bcadd(1, bcdiv( abs( bcsub($a[AVG_RATING], $b[AVG_RATING]) ), 5 ) );
    return bcmul($distance, $ratingFactor);
  }

    public function process() {
    $handle = fopen('dump.sql', 'w+');
    $ids = array_keys($this->movies);
    $counter = 1;
    $movieCount = count($ids);
    $last = $ids[count($ids) - 1];
    unset($ids);
    foreach ($this->movies as $i => $movieOuter) {
      if($i == $last) break;
      fwrite($handle, "BEGIN;\n");
      fwrite($handle, "INSERT INTO `movie_lens`.`distances` (node1, node2, distance) VALUES ");
      foreach ($this->movies as $j => $movieInner) {
        if($j <= $i) continue;
//        $distance = $this->euclidDistance($this->fillGenres($movieOuter[GENRES]), $this->fillGenres($movieInner[GENRES]));
//          $distance = $this->calculateDistanceRating($movieOuter, $movieInner);
        $distance = $this->calculateDistanceCustom($movieOuter, $movieInner);
        fwrite($handle, "($i, $j, $distance)");
        if ($j == $last){
          fwrite($handle, ";\nCOMMIT;\n");
        } else {
          fwrite($handle, ', ');
        }
      }
      echo "Processed\t$counter\tof\t$movieCount\n";
      $counter++;
    }

    fclose($handle);
  }

}

$distanceCalculator = new DistanceCalculator(0, 1092);
$distanceCalculator->process();