<?PHP
	class DBMongo{
		static private $host;
		static private $port;
		static private $username;
		static private $password;
		static private $db;
		
		static private $manager;
		static private $dbmongo;
		static private $collection;
		static private $statement;
		static private $result;
		
		static private $bulk;
		static private $cursor;
		static private $readpreference;
		static private $query;
		private $queryfilter = array();
		private $queryoption = array();
		static private $writeConcern;
		
		public function __construct(){
			
		}
		public function setConnectionName($connection_name=NULL){
			if(is_array($connection_name)){
				self::$host 	= $connection_name['host'];
				self::$port 	= $connection_name['port'];
				self::$username = $connection_name['username'];
				self::$password = $connection_name['password'];
				self::$db		= $connection_name['db'];
				
				return $this->getConnection();
			}
			else{
				self::$host 	= DB_CONFIG[$connection_name]['host'];
				self::$port 	= DB_CONFIG[$connection_name]['port'];
				self::$username = DB_CONFIG[$connection_name]['username'];
				self::$password = DB_CONFIG[$connection_name]['password'];
				self::$db		= DB_CONFIG[$connection_name]['db'];
				
				return $this->getConnection();
			}
		}
		
		public function getConnection($arr_option=NULL){
			try{
				if($arr_option==NULL){
					$stream_context = stream_context_create([ 'ssl' => ["allow_self_signed" => true]]);
					self::$manager = new \MongoDB\Driver\Manager('mongodb://'. self::$username .':'. self::$password .'@'.self::$host.':'.self::$port, ["ssl"=>true],["allow_self_signed" => true]);
					//var_dump(self::$manager);die;
					//self::$manager = new \MongoDB\Driver\Manager('mongodb://'. self::$username .':'. self::$password .'@'.self::$host.':'.self::$port);
					//return self::$manager;
					return $this;
				}
				else{
					$stream_context = stream_context_create([ 'ssl' => ["allow_self_signed" => true]]);
					self::$manager = new \MongoDB\Driver\Manager('mongodb://'. self::$username .':'. self::$password .'@'.self::$host.':'.self::$port, ["ssl"=>true],["allow_self_signed" => true]);
					//self::$manager = new \MongoDB\Driver\Manager('mongodb://'. self::$username .':'. self::$password .'@'.self::$host.':'.self::$port, $arr_option);
					//return self::$manager;
					return $this;
				}
			}
			catch(MongoConnectionException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/BDMongo.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [MongoException]#DBMongo::getConnection():CANNOT CONNECT Mongo Server: '.$e.' #END');
				exit;
			}
		}
		
		public function setConnOption($arr_option=NULL){
			return $this->getConnection($arr_option);
		}
		
		public function close(){
			return self::$manager->close();
		}
		
		public function preparequery(){
			try{
				self::$query = new MongoDB\Driver\Query($this->queryfilter, $this->queryoption);
			}
			catch(MongoConnectionException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/BDMongo.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [MongoException]#DBMongo::preparequery():Error: '.$e.' #END');
				exit;
			}
		}
		public function arrayReturn($cursor){
			$return = array();
			foreach($cursor as $row){
				unset($row->_id);
				array_push($return, (array)$row);
			}
			return $return;
		}
		
		public function prepareInsert($array_data){
			self::$bulk = new MongoDB\Driver\BulkWrite();
			if(is_array($array_data)){
				$array_data['createdDate']= $this->getDateTimeZ();
				$array_data['lastUpdate']= $this->getDateTimeZ();
			}
			else if(is_object($array_data)){
				$array_data->createdDate= $this->getDateTimeZ();
				$array_data->lastUpdate= $this->getDateTimeZ();
			}
			self::$bulk->insert($array_data);
		}
		
		public function prepareUpdate($array_data,$where_clause){
			self::$bulk = new MongoDB\Driver\BulkWrite();
			if(is_array($array_data)){
				$array_data['lastUpdate']= $this->getDateTimeZ();
			}
			else if(is_object($array_data)){
				$array_data->lastUpdate= $this->getDateTimeZ();
			}
			if(isset($where_clause['$method']) && $where_clause['$method']=='$push'){
				unset($where_clause['$method']);
				self::$bulk->update($where_clause, ['$push' => $array_data], ['multi' => false, 'upsert' => false]);
			}
			else{
				self::$bulk->update($where_clause, ['$set' => $array_data], ['multi' => false, 'upsert' => false]);
			}
		}
	
		
		public function prepareDelete($where_clause=NULL){
			if($where_clause==NULL){
				self::$bulk = new MongoDB\Driver\BulkWrite();
				self::$bulk->delete([]);
			}
			else{
				self::$bulk = new MongoDB\Driver\BulkWrite();
				self::$bulk->delete($where_clause);
			}
		}
		
		public function preparewriteConcern(){
			self::$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 10000);
		}
		public function executeQuery($collection, $clause=NULL,$order=NULL, $limit=NULL){
			$this->setClause($clause);
			$this->setLimit($limit);
			$this->setOrderBy($order);
			$this->preparequery();
			//var_dump(self::$query);die;
			//var_dump($this->queryoption);die;
			$cursor = self::$manager->executeQuery($collection, self::$query, self::$readpreference);

			return $this->arrayReturn($cursor);
		}
		
		public function executeInsert($collection, $array_data){
			
			$this->prepareInsert($array_data);
			$this->preparewriteConcern();
			
			try {
				$result = self::$manager->executeBulkWrite($collection, self::$bulk, self::$writeConcern);
			}
			catch (MongoDB\Driver\Exception\BulkWriteException $e) {
				
				$result = $e->getWriteResult();
				// Check if the write concern could not be fulfilled
				if ($writeConcernError = $result->getWriteConcernError()) {
					$error1 = sprintf("%s (%d): %s\n",
						$writeConcernError->getMessage(),
						$writeConcernError->getCode(),
						var_export($writeConcernError->getInfo(), true)
					);
				}
				
				// Check if any write operations did not complete at all
				foreach ($result->getWriteErrors() as $writeError) {
					$error2 .= sprintf("Operation#%d: %s (%d)",
						$writeError->getIndex(),
						$writeError->getMessage(),
						$writeError->getCode()
					);
				}
				
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/MongoDBException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [MongoDBException] #DBMongo::executeInsert():[1]'. $error1 .'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/MongoDBException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [MongoDBException] #DBMongo::executeInsert():[2]'. $error2 .'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/MongoDBException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [MongoDBException] #DBMongo::executeInsert():[DATA]'. json_encode($array_data) .'#END');
				
				return false;
			}
			catch (MongoDB\Driver\Exception\Exception $e) {
				$otherError = sprintf("Other error: %s\n", $e->getMessage());
				
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/MongoDBException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [MongoDBException] #DBMongo::executeInsert():[OTHER]'. $otherError .'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/MongoDBException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [MongoDBException] #DBMongo::executeInsert():[DATA]'. json_encode($array_data) .'#END');
				
				return false;
			}
			
			return $result->getInsertedCount();
		}
		
		public function executeUpdate($collection, $array_data, $array_clause){
			$this->prepareUpdate($array_data,$array_clause);
			$this->preparewriteConcern();
			
			try {
				$result = self::$manager->executeBulkWrite($collection, self::$bulk, self::$writeConcern);
			}
			catch (MongoDB\Driver\Exception\BulkWriteException $e) {
				$result = $e->getWriteResult();

				// Check if the write concern could not be fulfilled
				if ($writeConcernError = $result->getWriteConcernError()) {
					printf("%s (%d): %s\n",
						$writeConcernError->getMessage(),
						$writeConcernError->getCode(),
						var_export($writeConcernError->getInfo(), true)
					);
				}

				// Check if any write operations did not complete at all
				foreach ($result->getWriteErrors() as $writeError) {
					printf("Operation#%d: %s (%d)\n",
						$writeError->getIndex(),
						$writeError->getMessage(),
						$writeError->getCode()
					);
				}
			}
			catch (MongoDB\Driver\Exception\Exception $e) {
				printf("Other error: %s\n", $e->getMessage());
				exit;
			}
			
			return $result->getModifiedCount();
		}
		
		public function executeDelete($collection, $array_clause=NULL){
			$this->prepareDelete($array_clause);
			$this->preparewriteConcern();
			
			try {
				return self::$manager->executeBulkWrite($collection, self::$bulk, self::$writeConcern);
			}
			catch (MongoDB\Driver\Exception\BulkWriteException $e) {
				$result = $e->getWriteResult();

				// Check if the write concern could not be fulfilled
				if ($writeConcernError = $result->getWriteConcernError()) {
					printf("%s (%d): %s\n",
						$writeConcernError->getMessage(),
						$writeConcernError->getCode(),
						var_export($writeConcernError->getInfo(), true)
					);
				}

				// Check if any write operations did not complete at all
				foreach ($result->getWriteErrors() as $writeError) {
					printf("Operation#%d: %s (%d)\n",
						$writeError->getIndex(),
						$writeError->getMessage(),
						$writeError->getCode()
					);
				}
			}
			catch (MongoDB\Driver\Exception\Exception $e) {
				printf("Other error: %s\n", $e->getMessage());
				exit;
			}
			//var_dump($result);die;
			//return $result->getModifiedCount();
			return $result;
		}
		
		public function setClause($clause=NULL){
			$this->setqueryfilter($clause);
		}
		public function setLimit($limit=NULL){
			if(is_array($limit)){
				$this->queryoption = array_merge($this->queryoption,array("limit"=>$limit['Offset']));
				$this->queryoption = array_merge($this->queryoption,array("skip"=>$limit['Rows']));
			}
		}
		
		public function setOrderBy($order=NULL){
			
			if($order){
				foreach($order as $key=>$val){
					$order[$key] = $val=="ASC" ? 1 : -1;
				}
				$orderoption = [
					"sort"=>$order
				];
				
				$this->queryoption = array_merge($this->queryoption,$orderoption);
				
				//var_dump($this->queryoption);die;
			}
			
			
		}
		
		public function setqueryfilter($filter=NULL){
			if(is_array($filter)){
				$this->queryfilter = $filter;
			}
		}
		public function setqueryoption($option = NULL){
			$this->queryoption = $option;
		}
		
		public function setreadpreference(){
			try{
				//self::$readpreference = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY);
				self::$readpreference = new MongoDB\Driver\ReadPreference();
			}
			catch(MongoConnectionException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/BDMongo.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [MongoException]#DBMongo::setreadpreference():Error: '.$e.' #END');
			}
		}
		
		/* yyyy-mm-ddThh:ii:ss.ms~3Z */
		public function getDateTimeZ(){
			date_default_timezone_set('UTC');
			$datetime = date('Y-m-d\TH:i:s\Z');
			return $datetime;
		}
		
		public function __destruct(){
			//return $this->close();
		}
	}

?>