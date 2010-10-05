<?

 class db_accountdata {
  var $host                 = "localhost";
  var $user                 = "measureit";
  var $pass                 = "measureitpasswd";
  var $datenbank            = "measure_it";
}


class mydb extends db_accountdata
{

   function mydb()
   {
      $this->connect($this->host,$this->user,$this->pass,$this->datenbank);
   }

   function connect($host,$user,$pass,$datenbank)
   {
      $this->link = @mysql_connect($host,$user,$pass) or die ("Datenbankverbindung nicht moeglich!");
      $this->choosedb($datenbank);
   }

   function choosedb($datenbank)
   {
      @mysql_select_db($datenbank) or die ("Datenbank konnte nicht ausgewaehlt werden!");
   }

   function query($query)
   {
      $res = @mysql_query($query, $this->link) or die ("SQL Abfrage ist ungueltig. ".mysql_error());
      return $res;
   }

   function fetch_array($res)
   {
     return mysql_fetch_array($res);
   }

   function fetch_row($res)
   {
     return mysql_fetch_row($res);
   }

   function num_rows($res)
   {
     return mysql_num_rows($res);
   }
   function insert_id()
   {
     return mysql_insert_id();
   }

}

$db = new mydb;

?>
