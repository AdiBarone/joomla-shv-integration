<?php
# -----------------------------------------------------------------
# MySQL DB Class
# -----------------------------------------------------------------
# autor:   adrian.barone@systemfive.com
# date:    26.02.2020
# version: 2.00
#

class mysql {

  private $link;
  private $_db;

  function __construct($host,$db,$user,$password) {

    $this->_db['host'] = $host;
    $this->_db['user'] = $user;
    $this->_db['password'] = $password;

    if( ! $this->link = mysqli_connect($host,$user,$password,$db) ) {
      echo "Could not connect to database!";
      print_r($this->_db);
      exit;
    }

  }

  function __destruct() {
    @mysqli_close( $this->link );
  }

  public function query($q) {
    if( ! $query = mysqli_query($this->link, $q) ) {
      echo '
<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Error:</span> Error while executing MySQL Query
  <pre>'.$q.'</pre>
  <pre>'.mysqli_error().'</pre>
</div>';
    }
    return $query;
  }

  public function getRows($q) {
    if( is_string( $q ) ) $q = $this->query($q);
    $rows = array();
    while( $row = @mysqli_fetch_assoc($q) ){
      $rows[] = $row;
    }
    return $rows;
  }

  public function getRow($q) {
    return array_shift( $this->getRows($q) );
  }

  public function getResult($q) {
    return @array_shift( $this->getRow($q) );
  }

  public function getInsertId() {
    return mysqli_insert_id($this->link);
  }

  public function getResults($q) {
    $rows = $this->getRows($q);
    $return = array();
    foreach( $rows as $row ) {
      $return[] = array_shift( $row );
    }
    return $return;
  }

  public function quote($var,$quote=1) {
    if( trim($var) == "" ) {
      if( $quote == 2 ) return '""';
      return "NULL";
    } else {
      $escaped = mysqli_real_escape_string($this->link, $var);
      if( $quote > 0 ) {
        return '"' . $escaped . '"';
      } else {
        return $escaped;
      }
    }
  }

  public function affectedrows() {
    return mysqli_affected_rows($this->link);
  }

}

?>
