<?php 

	require_once '/web/measureit/measureit_system_files/measureit.cfg.php';
	define("DB_HOST", $database_host);
	define("DB_USER", $database_user);
	define("DB_PASS", $database_passwd);
	define("DB_NAME", $database_name);

	class db{
		

		private $database_host = DB_HOST;
		private $database_user = DB_USER;
		private $database_passwd = DB_PASS;
		private $database_name = DB_NAME;
		private $dbc;
		private $err;
		private $dbq;
		private $q;
		private $db;
		
		
		
		public function __construct( ){
			
			# define db connection settings
			$opts = array(
					PDO::ATTR_PERSISTENT => true,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
			
			# define db settings
			$dbc = 'mysql:host='.$this->database_host .';dbname='.$this->database_name;
			
			# connect to db
			try{
				$this->db = new PDO( $dbc, $this->database_user, $this->database_passwd, $opts );
			}
			# ups. error
			catch( PDOException $e ){
				$this->err = $e->getMessage( );
			}
			
		}
		
		# prepare the query
		public function query( $q ){
			$this->dbq = $this->db->prepare( $q );
		}
		
		# check the data type
		public function data($name, $data, $type = null){
		    if ( $this->input_check( $data ) ) {
		        switch ( true ) {
		            case is_int( $data) :
		                $type = PDO::PARAM_INT;
		                break;
		            case is_bool( $data ):
		                $type = PDO::PARAM_BOOL;
		                break;
		            case is_null( $data ):
		                $type = PDO::PARAM_NULL;
		                break;
		            default:
		                $type = PDO::PARAM_STR;
		        }
		    }
		    $this->dbq->bindValue( $name, $data, $type );
		}
		
		# execute the query
		public function execute( ){
			return $this->dbq->execute();
		}
		
		# debuging
		public function debug( ){
			return $this->dbq->debugDumpParams();
		}
		
		#get single dataset
		public function result( ){
			$this->execute( );
			return $this->dbq->fetch(PDO::FETCH_ASSOC);
		}
		
		# get multiple datasets
		public function results( ){
			$this->execute( );
			return $this->dbq->fetchAll(PDO::FETCH_ASSOC);
		}
		
		# we want to check the given input
		private function input_check( $params ){
			$r = true;$val = '';
			$match = '![\;\'\"\/\\%<>=#\(\)\*]+!';
			if( is_array( $params ) ){
				foreach ( $params as $k => $v ){
					if( is_array( $v ) ){
						input_check( $v );
						continue;
					}
					$val = $k.$v;
					if( preg_match( $match, $val ) ){
						$r = false;
					}
				}
			}else{
				if( preg_match( $match, $val ) ){
					$r = false;
				}
				$val = $params;
			}
			
			if(
					strstr( $val, 'union' ) || 
					strstr( $val, '0x' ) || 
					strstr( $val, 'load_file' ) || 
					strstr( $val, 'uotfile' ) || 
					strstr( $val, 'database' ) || 
					strstr( $val, 'benchmark' ) || 
					strstr( $val, 'script' ) ||  
					strstr( $val, 'eval' ) ||   
					strstr( $val, 'http' ) ||   
					strstr( $val, 'ftp' ) ||  
					strstr( $val, 'document' ) || 
					strstr( $val, 'hex' )
			){
				$r = false;
			}
		
			if( $r === false ){
				error( 'The are not allowed signs in the data.'. var_export( $params ) );
			}
			
			return $r;
		}
		
		public function backup(){
			set_time_limit(0);
			system( 'mysqldump --opt -h'.$this->database_host.' -u'.$this->database_user.' -p'.$this->database_passwd.' '.$this->database_name.' | gzip > ../backup/measureit_backup_'.@date('Ymd-His').'.gz &' );
			return true;
		}
		
	}
	
?>
