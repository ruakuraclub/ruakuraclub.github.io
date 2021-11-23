<?php
  
  class MYSQL_DB {
    public $_db_connection;

    function getDBConnection(){
      require_once( 'constants.php' );
      try {
        $this->_db_connection = new PDO( "mysql:host=$DB_SERVER;dbname=$DB_NAME", $DB_USER, $DB_PASS );
        // set the PDO error mode to exception
        $this->_db_connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        // echo 'connected to database';
        // $this->_db_connection = null;
        return $this->_db_connection;
      } catch( PDOException $e ) {
          if ( defined( 'DEVELOPMENT' ) ) die( 'Error connecting to database: '.$e->getMessage() );
          else die('Error connecting to the database: Connection failed');
      }
    }

    function endDBConnection( ){
      // To disconnect/close the connection
      $this->_db_connection = null;
    }

    //Strips non-UTF8 characters from input string
    function clean_utf8( $input) {
	    return iconv('UTF-8', 'UTF-8//IGNORE', $input);
    }

    function is_date( $input) {
      $date = new DateTime( $input);
      return checkdate( $date->format('m'), $date->format('d'), $date->format('Y'));
    }

    function is_time( $input) {
      return DateTime::createFromFormat('d.m.Y H:i:s', "10.10.2010 " . $input);
    }

    function is_datetime( $input) {
      if( is_date( $input) ) {
        $explosion = explode(' ',$input);
        //This chekcing of the time may be unnecessary
        return DateTime::createFromFormat('d.m.Y H:i:s', "10.10.2010 " . $explosion[1]);
      }
      return false;
    }
  }
// $test = new MYSQL_DB();
// return $test->getDBConnection( $DB_SERVER, $DB_NAME, $DB_USER, $DB_PASS );
