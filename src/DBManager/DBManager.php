<?PHP
	// WITH SINGLE DATABASE :
	class DBManager{
		/*
			* method :
			** setDBName('dbname');
			** execute('sql','sort_field');
			** getTopData('tablename','field');
			** insert('tablename', data_array:$_POST, primary:array('FID','FIDBranch'), array('FConnection'=>'TTTTT'));
			** update('tablename', data_array:$_POST, clause:array('FID'=>$_POST['FID']));
		*/
		private $dbengine;
		private $host;
		private $username;
		private $password;
		private $dbname;

		private $dsn;
		private $instance;
		private $statement;
		private $row;
		public $rowcount;
		
		private $connection_name;
		
		public function __construct($connection=NULL){
			//new Logger($_SERVER['DOCUMENT_ROOT']."/log/DB_COUNT.log", $_SERVER['PHP_SELF'].":".__LINE__."1");
			if($connection!=NULL){
				if(is_array($connection)){
					$this->dbengine = $connection['engine'];
					$this->host		= $connection['host'];
					$this->username	= $connection['username'];
					$this->password = $connection['password'];
					$this->dbname	= $connection['db'];
					return $this->getConnection($connection);
				}
				else{
					$this->dbengine = DB_CONFIG[$connection]['engine'];
					$this->host		= DB_CONFIG[$connection]['host'];
					$this->username	= DB_CONFIG[$connection]['username'];
					$this->password = DB_CONFIG[$connection]['password'];
					$this->dbname	= DB_CONFIG[$connection]['db'];
					return $this->getConnection($connection);
				}
			}
		}
		
		public function setConnectionName($connection_name=NULL){
			if($connection_name==NULL || $connection_name==''){
				$connection_name = 'default';
			}
			if(is_array($connection_name)){
				$this->dbengine = $connection_name['engine'];
				$this->host		= $connection_name['host'];
				$this->username	= $connection_name['username'];
				$this->password = $connection_name['password'];
				$this->dbname	= $connection_name['db'];
				return $this->getConnection($connection_name);
			}
			else{
				$this->dbengine = DB_CONFIG[$connection_name]['engine'];
				$this->host		= DB_CONFIG[$connection_name]['host'];
				$this->username	= DB_CONFIG[$connection_name]['username'];
				$this->password = DB_CONFIG[$connection_name]['password'];
				$this->dbname	= DB_CONFIG[$connection_name]['db'];
				return $this->getConnection($connection_name);
			}
		}
		
		public function getConnection($connection_name=NULL){
			/*
			if(isset($_COOKIE['PHPSESSID']) && $_COOKIE['PHPSESSID']=="1ajgjdurlbc3peh42d1nesib47"){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/LOG_SESION_TUNING.log', $_SERVER['PHP_SELF'].':'.__LINE__.'DBManager::getConnection() LOADED!!! '.print_r(debug_backtrace(),true).'\n#END');
			}
			*/
			try{
				switch($this->dbengine){
					case 'sqlsrv':
						//$a = $this->host;
						//$b = $this->dbname;
						//$c = $this->username;
						$this->instance = new PDO('sqlsrv:server='.$this->host.';Database='.$this->dbname, $this->username, $this->password);
						$this->instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						return $this->instance;
					break;
					
					case 'mssql':
						$this->dsn = $this->dbengine.':host='.$this->host.';dbname='.$this->dbname;
						$this->instance = new PDO($this->dsn,$this->username,$this->password);
						$this->instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						return $this->instance;
					break;
					
					case 'mysql':
						$this->dsn = $this->dbengine.':host='.$this->host.';dbname='.$this->dbname;
						$this->instance = new PDO($this->dsn,$this->username,$this->password);
						$this->instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						return $this->instance;
					break;
					
					case 'mongodb':
						if($connection_name){
							$this->instance = new DBMongo();
							$this->instance->setConnectionName($connection_name);
							return $this->instance;
						}
						else{
							return $this->instance;
						}
						
					break;
					
					default:
						new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR_ERROR_CONNECT.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [PDOException]#DBManager::getConnection():GET CONECTION ENGINE NOT FOUND OR NULL FOR : '.$this->dbengine.'! #END');
						return false;
					break;
				}
			}
			catch(PDOException $e){
				//new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [PDOException]#DBManager::getConnection():'.$e->getMessage().'\nTRACE :\n'.print_r(debug_backtrace(),true).'\n #END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [PDOException]#DBManager::getConnection():'.$e->getMessage().'\n #END');
				//new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [PDOException-Trace]#DBManager::getConnection():'.print_r(debug_backtrace(),true).'\n #END');
				return false;
			}
		}
		
		public function setEngine($dbengine){
			$this->dbengine	= $dbengine;
			return $this->getConnection($this->dbengine);
		}
		
		public function config($db_config_name){
			
		}
		
		public function filterObjTable($str_object=NULL){
			try{
				switch($this->dbengine){
					case 'sqlsrv':
						if($str_object!=NULL){
							$result = preg_match('/(^[0-9_a-zA-Z]*)(\.dbo\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
							if($result==TRUE){
								$dbname = $matches[1];
								$tablename = $matches[3];
								return $str_object;
							}
							else{
								$result = preg_match('/(^[0-9_a-zA-Z]*)(\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
								if($result==TRUE){
									$dbname = $matches[1];
									$tablename = $matches[3];
									return $dbname.'.dbo.'.$tablename;
								}
								else{
									return $str_object;
								}
							}
						}
					break;
					
					case 'mssql':
						if($str_object!=NULL){
							$result = preg_match('/(^[0-9_a-zA-Z]*)(\.dbo\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
							if($result==TRUE){
								$dbname = $matches[1];
								$tablename = $matches[3];
								return $str_object;
							}
							else{
								$result = preg_match('/(^[0-9_a-zA-Z]*)(\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
								if($result==TRUE){
									$dbname = $matches[1];
									$tablename = $matches[3];
									return $dbname.'.dbo.'.$tablename;
								}
								else{
									return $str_object;
								}
							}
						}
					break;
					
					case 'mysql':
						if($str_object!=NULL){
							$result = preg_match('/(^[0-9_a-zA-Z]*)(\.dbo\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
							if($result==TRUE){
								$dbname = $matches[1];
								$tablename = $matches[3];
								return $dbname.'.'.$tablename;
							}
							else{
								$result = preg_match('/(^[0-9_a-zA-Z]*)(\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
								if($result==TRUE){
									$dbname = $matches[1];
									$tablename = $matches[3];
									return $dbname.'.'.$tablename;
								}
								else{
									return $str_object;
								}
							}
						}
					break;
					
					case 'mongodb':
						if($str_object!=NULL){
							$result = preg_match('/(^[0-9_a-zA-Z]*)(\.dbo\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
							if($result==TRUE){
								$dbname = $matches[1];
								$tablename = $matches[3];
								$str_object = $dbname.'.'.$tablename;
							}
							else{
								$result = preg_match('/(^[0-9_a-zA-Z]*)(\.)([0-9_a-zA-Z]+$)/',$str_object, $matches);
								if($result==TRUE){
									$dbname = $matches[1];
									$tablename = $matches[3];
									$str_object = $dbname.'.'.$tablename;
								}
							}
							if(strpos($str_object,".")===false){
								$str_object = $this->dbname .".".$str_object;
							}
							return $str_object;
						}
					break;
					
					default:
						new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [PDOException]#DBManager::filterObjTable():GET CONECTION ENGINE NOT FOUND OR NULL! #END');
						return false;
					break;
				}
			}
			catch (PDOException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.' [PDOException]#DBManager::filterObjTable():'.$e->getMessage().' #END');
				return false;
			}
		}
		
		public function setDBName($dbname){
			$this->dbname	= $dbname;
			//new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #DBManager::setDBName():'.$this->dbname.' #END');
			$this->getConnection($this->dbengine);
			return $this;
		}
		public function getRows($stmt,$sort=NULL){
			try{
			    $result = $stmt->fetchAll();
				/*
				while($this->row=$stmt->fetch()){
					$result[]=$this->row;
				}
				*/
				if($sort==NULL){
					return isset($result) ? $result : NULL;
				}
				else{
					return $this->msort($result,$sort);
				}
			}
			catch(PDOException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #[ERROR] DBManager::getRows() '. $e->getMessage());
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #[ERROR] DBManager::getRows():STMT:'. print_r($stmt,true));
			}
		}
		
		public function quote($str=NULL){
			if($str!=NULL){
				return $this->instance->quote($str);
			}
			else{
				return '';
			}
		}
		public function quoteArrayValue($array){
			$array_quote_value = array();
			if(is_array($array)){
				foreach($array as $key => $val){
					$array_quote_value[$key] = $this->quote($val);
				}
				return $array_quote_value;
			}
			else{
				return NULL;
			}
		}
		
		// execute('sql','sortfield');  return data_array  with foreach ;
		public function execute($query,$sort=NULL){
			try{
				if($this->instance==NULL){
					$this->setConnectionName();
				}
				
				if($this->instance!=NULL){
					$this->statement = $this->instance->prepare($query);
					$this->statement->execute();
					$this->statement->setFetchMode(PDO::FETCH_ASSOC);
					$this->rowcount = $this->statement->rowCount();
					if($sort==NULL){
						return $this->getRows($this->statement);
					}
					else{
						return $this->getRows($this->statement,$sort);
					}
				}
				else{
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::execute():NULL OF INSTANCE:' . print_r($this->instance,true) . ' #END');
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::execute():' . $query . '#END');
					return '0';
				}
			}
			catch(PDOException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::execute():'. $e->getMessage().'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::execute():'. $query .'#END');
				return false;
			}
		}
		
		// executes('sql;sql;sql','sortfield');  return false or true ;
		public function executes($query,$sort=NULL){
			try{
				if($this->instance==NULL){
					$this->setConnectionName();
				}
				
				if($this->instance!=NULL){
					$this->statement = $this->instance->prepare($query);
					$this->statement->execute();
					while($this->statement->nextRowset()){/* https://bugs.php.net/bug.php?id=61613 */}
					$this->rowcount = $this->statement->rowCount();
					return true;
				}
				else{
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executes():NULL OF INSTANCE:' . print_r($this->instance,true) . ' #END');
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executes():' . $query . '#END');
					return false;
				}
			}
			catch(PDOException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executes():'. $e->getMessage().'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executes():'. $query .'#END');
				return false;
			}
		}
		
		//must 'SELECT SQL_CALC_FOUND_ROWS * FROM table LIMIT 0,2'
		public function getFoundRows(){
			if($this->statement){
				$foundRows = $this->instance->query('SELECT FOUND_ROWS()')->fetchColumn();
				
				return $foundRows ? $foundRows : '0';
			}
			else{
				return '0';
			}
		}
		
		public function multiExecute($query,$sort=NULL){
			$sql = explode(';',$query);
			if(is_array($sql)){
				try{
					if($this->instance==NULL){
						$this->setConnectionName();
					}
					foreach($sql as $stm){
						if(!$this->instance){
							$this->setConnectionName();
						}
						$this->statement=$this->instance->query($stm);
					}
					$this->statement->setFetchMode(PDO::FETCH_ASSOC);
					if($sort==NULL){
						return $this->getRows($this->statement);
					}
					else{
						return $this->getRows($this->statement,$sort);
					}
				}
				catch(PDOException $e){
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::multiExecute():'. $e->getMessage().'#END');
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::multiExecute():'. $query .'#END');
					return false;
				}
			}
			else{
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [UN_ARRAY] #DBManager::multiExecute():'. $sql .'#END');
				return false;
			}
			
		}
		
		public function selectCustom($query,$array_clause=NULL,$array_like_caluse=NULL,$array_order=NULL,$array_limit=NULL){
			$sql = $query ;
			if($array_clause!=NULL && $array_like_caluse!=NULL){
				$sql = $sql . ' '.$this->getClause($array_clause).' AND ('.$this->getLikeClauseWithoutWhere($array_like_caluse).') ';
			}
			else if($array_clause!=NULL && $array_like_caluse==NULL){
				$sql = $sql . ' '.$this->getClause($array_clause).' ';
			}
			else if($array_like_caluse!=NULL && $array_clause==NULL){
				$sql = $sql . ' ' . $this->getLikeClause($array_like_caluse) . ' ';
			}
			else{
				$sql = $sql . ' ';
			}
			
			if($array_order!=NULL){
				$sql = $sql . ' '.$this->getOrderBy($array_order).' ';
			}
			
			if($array_limit!=NULL){
				$sql = $sql . ' '.$this->getLimit($array_limit,$this->dbengine).' ';
			}
			return $this->execute($sql);
			
		}
		
		public function queryCustom($query,$array_clause=NULL,$array_like_caluse=NULL,$array_order=NULL,$array_limit=NULL){
			$sql = $query ;
			if($array_clause!=NULL && $array_like_caluse!=NULL){
				$sql = $sql . ' '.$this->getClause($array_clause).' AND ('.$this->getLikeClauseWithoutWhere($array_like_caluse).') ';
			}
			else if($array_clause!=NULL && $array_like_caluse==NULL){
				$sql = $sql . ' '.$this->getClause($array_clause).' ';
			}
			else if($array_like_caluse!=NULL && $array_clause==NULL){
				$sql = $sql . ' ' . $this->getLikeClause($array_like_caluse) . ' ';
			}
			else{
				$sql = $sql . ' ';
			}
			
			if($array_order!=NULL){
				$sql = $sql . ' '.$this->getOrderBy($array_order).' ';
			}
			
			if($array_limit!=NULL){
				$sql = $sql . ' '.$this->getLimit($array_limit,$this->dbengine).' ';
			}
			return $this->multiExecute($sql);
		}
		
		public function updateCustom($sql){
			return $this->executeUpdate($sql);
		}
		
		// executeUpdate('sql');  return true;
		public function executeUpdate($query){
			try{
				if($this->instance==NULL){
					$this->setConnectionName();
				}
				
				if($this->instance!=NULL){
					$this->statement=$this->instance->prepare($query);
					$this->statement->execute();
					return $this->statement->rowCount();
				}
				else{
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executeUpdate():NULL OF INSTANCE:' . print_r($this->instance,true) . ' #END');
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executeUpdate():' . $query . '#END');
					return '0';
				}
			}
			catch(PDOException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executeUpdate():'. $e->getMessage().'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executeUpdate():'. $query .'#END');
				return false;
			}
		}
		public function executeDelete($query){
			try{
				if($this->instance==NULL){
					$this->setConnectionName();
				}
				$this->statement=$this->instance->prepare($query);
				$this->statement->execute();
				
				return $this->statement->rowCount();
			}
			catch(PDOException $e){
				if(
					$e->errorInfo[0]==40001 /* (ISO/ANSI) Serialization failure, e.g. timeout or deadlock */
					&& $this->instance->getAttribute(PDO::ATTR_DRIVER_NAME)=="mysql"
					&& $e->errorInfo[1]==1213  /* (MySQL SQLSTATE) ER_LOCK_DEADLOCK */
				){
					try{
						$this->statement=$this->instance->prepare($query);
						$this->statement->execute();
						new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException_retry.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executeDelete():'. $e->getMessage().'#END');
						new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException_retry.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executeDelete():'. $query .'#END');
						
						return $this->statement->rowCount();
					}
					catch(PDOException $e){
						new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException_still.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executeDelete():'. $e->getMessage().'#END');
						new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException_still.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executeDelete():'. $query .'#END');
						
						return false;
					}
					
				}
				else{
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::executeDelete():'. $e->getMessage().'#END');
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException-Query] #DBManager::executeDelete():'. $query .'#END');
					
					return false;
				}
			}
		}
		
		// GET one TOP/MAX data FROM FIELD :
		public function getTopData($table,$field){
			$table = $this->filterObjTable($table);
			$sql = 'SELECT MAX('.$field.') AS $field FROM '.$table;
			return $this->execute($sql);
		}
		
		// GET ALL ROW with clause :
		public function getAllRow($table,$array_clause=NULL,$array_order=NULL,$array_limit=NULL,$logger=FALSE){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mssql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mysql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mongodb' :
					$x = $this->instance->executeQuery($table,$array_clause,$array_order,$array_limit);
					return $x;
				break;
				
				default:
					new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'[ERROR] DBManager:getAllRow():\''.$this->dbengine.'\',Error db engine not available!');
					$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #SQL-GETALLROW:'. $sql .'#END SQL-GETALLROW');
			}
			return $this->execute($sql);
		}
		
		/**
			* Select distinct data from database
			* @access public
			* @param string $table The table name
			* @param array $array_selected_field field are selected, example array('column1','column2')
			* @param array $array_clause sql clause, example array('column1'=>'A','column2'=>'B')
			* @param array $array_order sql clause, example array('column1'=>'ASC','column2'=>'DESC')
			* @param boolean $loger The generate loger or not
			* @return array
		*/
		public function selectDistinct($table,$array_selected_field=NULL,$array_clause=NULL, $array_order=NULL, $logger=FALSE){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
				$sql = 'SELECT DISTINCT '. $this->getSelectField($array_selected_field) .' FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mssql' :
				$sql = 'SELECT DISTINCT '. $this->getSelectField($array_selected_field) .' FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mysql' :
				$sql = 'SELECT DISTINCT '. $this->getSelectField($array_selected_field) .' FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				default:
				new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'#DBManager:selectDistinct()['.$this->dbengine.'],Error db engine not available!');
				$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #DBManager::selectDistinct():'. $sql .'#END');
			}
			return $this->execute($sql);
		}
		
		/**
			* convert array filed to inline string with ',' sparate
			* @access public
			* @param array $array_selected_field The field are selected, example array('column1','column2')
			* @return string
		*/
		public function getSelectField($array_selected_field=NULL){
			if($array_selected_field==NULL){
				return ' * ';
			}
			else{
				$inline_field = implode(', ', $array_selected_field);
				return ' ' . $inline_field . ' ';
			}
		}
		
		// GET one TOP data FROM FIELD with clause :
		public function getSingleRow($table,$array_clause=NULL,$array_order=NULL,$logger=FALSE){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$sql = 'SELECT TOP 1 * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mssql' :
					$sql = 'SELECT TOP 1 * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mysql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order).' LIMIT 1';
				break;
				
				case 'mongodb' :
					$x = $this->instance->executeQuery($table,$array_clause,$array_order,["Rows"=>0,"Offset"=>1]);
					return $x;
				break;
				
				default:
					new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
					$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #SQL-GETSINGLEROW:'. $sql .'#END SQL-GETSINGLEROW');
			}
			return $this->execute($sql);
		}
		
		public function getCellValue($table, $field, $array_clause=NULL, $array_order=NULL, $logger=NULL){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
				$sql = 'SELECT TOP 1 '.$field.' FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mssql' :
				$sql = 'SELECT TOP 1 '.$field.' FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order);
				break;
				
				case 'mysql' :
				$sql = 'SELECT '.$field.' FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order).' LIMIT 1';
				break;
				
				default:
				new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
				$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #DBManager::getCell():SQL:'. $sql .'#END');
			}
			$data = $this->execute($sql);
			if($data){
				$row = $data[0];
				return $row[$field];
			}
			else{
				return 'undefined';
			}
		}
		
		public function getRowLimit($table,$array_clause=NULL,$array_order=NULL,$array_limit=NULL,$logger=FALSE){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
				$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit);
				break;
				
				case 'mssql' :
				$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit);
				break;
				
				case 'mysql' :
				$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit,'mysql');
				break;
				default:
				new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
				$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #SQL-getRowLimit():'. $sql .'#END SQL-getRowLimit()');
			}
			return $this->execute($sql);
		}
		
		public function getRowLimitLike($table, $array_like_clause=NULL,$array_order=NULL,$array_limit=NULL,$logger=FALSE){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getLikeClause($array_like_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit);
				break;
				
				case 'mssql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getLikeClause($array_like_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit);
				break;
				
				case 'mysql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getLikeClause($array_like_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit,'mysql');
				break;
				default:
					new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
					$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #SQL-getRowLimitLike():'. $sql .'#END SQL-getRowLimitLike()');
			}
			return $this->execute($sql);
		}
		
		public function getRowLimitLikeAnd($table, $array_like_clause=NULL,$array_order=NULL,$array_limit=NULL,$logger=FALSE){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getLikeClauseAnd($array_like_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit);
				break;
				
				case 'mssql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getLikeClauseAnd($array_like_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit);
				break;
				
				case 'mysql' :
					$sql = 'SELECT * FROM '.$table.' '.$this->getLikeClauseAnd($array_like_clause).' '.$this->getOrderBy($array_order).' '.$this->getLimit($array_limit,'mysql');
				break;
				default:
					new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
					$sql = '';
				break;
			}
			if($logger==TRUE){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  #SQL-getRowLimitLike():'. $sql .'#END SQL-getRowLimitLike()');
			}
			return $this->execute($sql);
		}
		
		// Return Number of Row Count (not array ) :
		public function getRowCount($table,$array_clause=NULL){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
				$sql = 'SELECT COUNT(*) AS FCount FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mssql' :
				$sql = 'SELECT COUNT(*) AS FCount FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mysql' :
				$sql = 'SELECT COUNT(*) AS FCount FROM '.$table.' '.$this->getClause($array_clause);
				break;
				default:
				new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
				$sql = '';
				break;
			}
			$result = $this->execute($sql);
			if(count($result)>0){
				foreach($result as $row){
					return $row['FCount'];
				}
			}
			else{
				return '0';
			}
		}
		
		
		// method check :
		public function isExistRecord($table,$array_clause=NULL){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
				$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mssql' :
				$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mysql' :
				$sql = 'SELECT * FROM '.$table.' '.$this->getClause($array_clause);
				break;
				default:
				new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
				$sql = '';
				break;
			}
			$result = $this->execute($sql);
			if($result){
				return true;
			}
			else{
				return false;
			}
		}
		// end method check
		
		public function msort($p_array, $key) {
			$sort_flags = SORT_REGULAR;
			if (is_array($p_array) && count($p_array) > 0) {
				if (!empty($key)) {
					$mapping = array();
					foreach ($p_array as $k => $v) {
						$sort_key = '';
						if (!is_array($key)) {
							$sort_key = @$v[$key] or die('Field '.$key.' is not exist !');
							} else {
							// @TODO This should be fixed, now it will be sorted as string
							foreach ($key as $key_key) {
								$sort_key .= $v[$key_key];
							}
							$sort_flags = SORT_STRING;
						}
						$mapping[$k] = $sort_key;
					}
					asort($mapping, $sort_flags);
					$sorted = array();
					foreach ($mapping as $k => $v) {
						$sorted[] = $p_array[$k];
					}
					return $sorted;
				}
			}
			return $p_array;
		}
		public function _insert($query){
			try{
				if($this->instance==NULL){
					$this->setConnectionName();
				}
				
				$this->statement=$this->instance->prepare($query);
				$this->statement->execute();
				return $this->statement->rowCount();
			}
			catch(PDOException $e){
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException] #DBManager::_insert():'. $e->getMessage().'#END');
				new Logger($_SERVER['DOCUMENT_ROOT'].'/log/PDOException.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  [PDOException][SQL] #DBManager::_insert():'. $query .'#END');
				return false;
			}
			
		}
		
		public function getinsertvalue($array_data=NULL){
			if(is_array($array_data)){
				$key = array_keys($array_data);
				$val = array_values($array_data);
				for($id=0;$id<count($array_data);$id++){
					$field[$id] = $key[$id];
					$value[$id] = is_bool($val[$id]) ? ( $val[$id] ? '1' : '0' ) : addslashes($val[$id]);
				}
				
				return ' (`'.implode('`, `',$field).'`) VALUES (\''.implode('\', \'',$value).'\') ';
			}
		}
		
		public function getinsertmanyvalue($array_data=NULL){
			
			if(is_array($array_data) && isset($array_data[0])){
				$stringColumn = '';
				$arrayValues = [];
				foreach($array_data as $rowIndex => $row){
					if(is_array($row)){
						$key = array_keys($row);
						$val = array_values($row);
						for($id=0;$id<count($row);$id++){
							$field[$id] = $key[$id];
							$value[$id] = is_bool($val[$id]) ? ( $val[$id] ? '1' : '0' ) : addslashes($val[$id]);
						}
						if($rowIndex=='0'){
							$stringColumn = '(`'.implode('`, `',$field).'`)';
						}
						$arrayValues[] = '(\''.implode('\', \'',$value).'\')';
					}
				}
				
				return ' '. $stringColumn .' VALUES '. implode(', ',$arrayValues) .' ';
			}
		}
		
		// $conn->insert('tablename', data_array:$_POST, primary:array('FID','FIDBranch'), array('FConnection'=>'TTTTT'));
		public function insert($table,$array_data){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$this->sql= 'INSERT INTO ' . $table . $this->getinsertvalue($array_data);
				break;
				
				case 'mssql' :
					$this->sql= 'INSERT INTO ' . $table . $this->getinsertvalue($array_data);
				break;
				
				case 'mysql' :
					$this->sql= 'INSERT INTO ' . $table . $this->getinsertvalue($array_data);
				break;
				
				case 'mongodb' :
					return $this->instance->executeInsert($table,$array_data);
				break;
				
				default:
					new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager::insert():Error, dbengine not found for:'.$this->dbengine);
					exit;
				break;
			}
			
			return $this->_insert($this->sql);
		}
		
		// $conn->insert('tablename', data_array:$_POST, primary:array('FID','FIDBranch'), array('FConnection'=>'TTTTT'));
		public function insertMany($table,$array_data){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$this->sql= 'INSERT INTO ' . $table . $this->getinsertmanyvalue($array_data);
				break;
				
				case 'mssql' :
					$this->sql= 'INSERT INTO ' . $table . $this->getinsertmanyvalue($array_data);
				break;
				
				case 'mysql' :
					$this->sql= 'INSERT INTO ' . $table . $this->getinsertmanyvalue($array_data);
				break;
				
				case 'mongodb' :
					return $this->instance->executeInsertMany($table,$array_data);
				break;
				
				default:
					new Logger('default', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager::insertMany():Error, dbengine not found for: '.$this->dbengine);
					exit;
				break;
			}
			
			return $this->_insert($this->sql);
		}
		
		public function getUpdateSet($data){
			$key = array_keys($data);
			$val = array_values($data);
			for($i=0;$i<count($data);$i++){
				$set[$i] = $key[$i].'=\''.str_replace("'","",$val[$i]).'\'';
			}
			return ' SET '.implode(',',$set).' ';
		}
		
		
		// $conn->update('tablename', data_array:$_POST, clause:array('FID'=>$_POST['FID']),$looger);
		public function update($table,$data,$where=NULL){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$this->sql = 'UPDATE '.$table.$this->getUpdateSet($data).$this->getClause($where);
				break;
				
				case 'mssql' :
					$this->sql = 'UPDATE '.$table.$this->getUpdateSet($data).$this->getClause($where);
				break;
				
				case 'mysql' :
					$this->sql = 'UPDATE '.$table.$this->getUpdateSet($data).$this->getClause($where);
				break;
				
				case 'mongodb' :
					$x = $this->instance->executeUpdate($table,$data,$where);
					return $x;
				break;
				
				default:
					new Logger('default',$_SERVER['PHP_SELF'].':'.__LINE__.' '.'DBManager::insert():Error, dbengine not found for:'.$this->dbengine);
					exit;
				break;
			}
			return $this->executeUpdate($this->sql);
		}
		
		// return covert array to SQL Clause :
		public function getClause($array_clause=NULL){
			if($array_clause!=NULL){
				$c_key = array_keys($array_clause);
				$c_val = array_values($array_clause);
				for($i=0;$i<count($array_clause);$i++){
					if(is_array($c_val[$i])){
						$clause[$i] = ' '.$c_key[$i].' IN (\''.implode('\',\'',$c_val[$i]).'\') ';
					}
					else{
						$clause[$i] = ' '.$c_key[$i].'=\''.htmlentities($c_val[$i]).'\' ';
					}
				}
				return ' WHERE '. implode(' AND ',$clause);
			}
			else{
				return '';
			}
		}
		
		public function getLikeClause($array_like_clause=NULL){
			if($array_like_clause!=NULL){
				$c_key = array_keys($array_like_clause);
				$c_val = array_values($array_like_clause);
				for($i=0;$i<count($array_like_clause);$i++){
					$clause[$i] = ' '.$c_key[$i].' LIKE \'%'.htmlentities($c_val[$i]).'%\' ';
				}
				return ' WHERE '. implode(' OR ',$clause);
			}
			else{
				return '';
			}
		}
		
		public function getLikeClauseAnd($array_like_clause=NULL){
			if($array_like_clause!=NULL){
				$c_key = array_keys($array_like_clause);
				$c_val = array_values($array_like_clause);
				for($i=0;$i<count($array_like_clause);$i++){
					$clause[$i] = ' '.$c_key[$i].' LIKE \'%'.htmlentities($c_val[$i]).'%\' ';
				}
				return ' WHERE '. implode(' AND ',$clause);
			}
			else{
				return '';
			}
		}
		
		public function getLikeClauseWithoutWhere($array_clause){
			if($array_clause!=NULL){
				$c_key = array_keys($array_clause);
				$c_val = array_values($array_clause);
				for($i=0;$i<count($array_clause);$i++){
					$clause[$i] = ' '.$c_key[$i].' LIKE \'%'.htmlentities($c_val[$i]).'%\' ';
				}
				return ' ('. implode(' OR ',$clause) . ') ';
			}
			else{
				return '';
			}
		}
		
		// return covert array to SQL order by :
		public function getOrderBy($array_orderby=NULL){
			if($array_orderby!=NULL){
				$c_key = array_keys($array_orderby);
				$c_val = array_values($array_orderby);
				for($i=0;$i<count($array_orderby);$i++){
					$orderby[$i] = ' '.$c_key[$i].' '.htmlentities($c_val[$i]).' ';
				}
				return ' ORDER BY '. implode(' , ',$orderby);
			}
			else{
				return '';
			}
		}
		// $array_limit = array('Offset'=>0,'Rows'=>25);
		public function getLimit($array_limit=NULL,$dbengine=NULL){
			if($array_limit!=NULL){
				if($dbengine==NULL){
					return ' OFFSET '.$array_limit['Offset'].' ROWS FETCH NEXT '.($array_limit['Rows']>0 ? $array_limit['Rows'] : '1').' ROWS ONLY ';
				}
				else if($dbengine=='sqlsrv'){
					return ' OFFSET '.$array_limit['Offset'].' ROWS FETCH NEXT '.($array_limit['Rows']>0 ? $array_limit['Rows'] : '1').' ROWS ONLY ';
				}
				else if($dbengine=='mysql'){
					return ' LIMIT '.($array_limit['Offset']).','.($array_limit['Rows']>0 ? $array_limit['Rows'] : '1');
				}
				else{
					return '';
				}
			}
			else{
				return '';
			}
		}
		
		// DELETE RECORD :
		public function deleteRow($table,$array_clause=NULL){
			$table = $this->filterObjTable($table);
			switch($this->dbengine){
				case 'sqlsrv' :
					$sql = 'DELETE FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mssql' :
					$sql = 'DELETE FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mysql' :
					$sql = 'DELETE FROM '.$table.' '.$this->getClause($array_clause);
				break;
				case 'mongodb' :
					return $this->instance->executeDelete($table,$array_clause)->getDeletedCount();
				break;
				default:
					new Logger($_SERVER['DOCUMENT_ROOT'].'/log/DBMGR', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.'DBManager:['.$this->dbengine.'],Error db engine not available!');
					$sql = '';
				break;
			}
			return $this->executeDelete($sql);
			
		}
		
		public function getUTCDateTime(){
			switch($this->dbengine){
				case "mongodb" :
					return new \MongoDB\BSON\UTCDateTime();
				break;
				case "mysql" :
					list($usec, $sec) = explode(" ", microtime());
					return gmdate('Y-m-d H:i:s\Z');
				break;
				default:
				break;
				
			}
		}
		
		public function close(){
			$this->statement = null;
			$this->instance = null;
		}
		
		public function __destruct(){
			$this->statement = null;
		}
	}
	
	
?>