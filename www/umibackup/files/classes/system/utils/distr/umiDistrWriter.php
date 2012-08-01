<?php
	class umiDistrWriter {
		protected	$configFilePath,
				$force,
				$skipMySql,
				$paths = Array(),
				$totalSize = 0,
				$fh;

		protected	$version = "1.0.0",
				$signature = "ucp";

		public		$author = "";
		public		$comment = "";

		public function __construct($configFilePath, $force = false, $skipMySql = false) {
			if(!is_file($configFilePath)) {
				trigger_error("Config file \"{$configFilePath}\" doesn't exists", E_USER_ERROR);
			}
			$this->configFilePath = $configFilePath;
			$this->force = $force;
			$this->skipMySql = $skipMySql;
			$this->readConfigFile();
		}


		public function __destruct() {
			if(is_resource($this->fh)) {
				trigger_error("Resource \"{$this->fh}\" is not closed.", E_USER_NOTICE);
				fclose($this->fh);
			}
		}


		protected function readConfigFile() {
			if(!is_readable($this->configFilePath)) {
				trigger_error("Config file \"{$this->configFilePath}\" is not readable", E_USER_ERROR);
			}

			$f = $this->fh = fopen($this->configFilePath, "r");

			for($i = 1; !feof($f); ++$i) {
				$path = trim(stream_get_line($f, 512, "\r\n"));

				if(!$path || substr($path, 0, 1) == "#") continue;

				if(!file_exists($path)) {
					if($this->force) {
						trigger_error("Error parsing {$this->configFilePath} on line {$i}. File not found: \"{$path}\".", E_USER_WARNING);
						continue;
					} else {
						trigger_error("Error parsing {$this->configFilePath} on line {$i}. File not found: \"{$path}\".", E_USER_ERROR);
						return false;
					}
				}
				$path = realpath($path);
				if(!is_readable($path)) {
					if($this->force) {
						trigger_error("Error parsing {$this->configFilePath} on line {$i}. File is not readable: \"{$path}\".", E_USER_WARNING);
						continue;
					} else {
						trigger_error("Error parsing {$this->configFilePath} on line {$i}. File is not readable: \"{$path}\".", E_USER_ERROR);
						return false;
					}
				}

				if(!in_array($path, $this->paths)) {
					$rpath = realpath(dirname(__FILE__) . '/../..') . "/";
					$path = substr($path, strlen($rpath), strlen($path) - strlen($rpath));

					$this->paths[] = $path;
					$this->totalSize += filesize($path);
				}
			}

			fclose($f);
		}


		public function generatePackage($outputFilePath = "./output/umicmsproinfo.ucp") {
			$this->fh = $f = fopen($outputFilePath, "wb");

			$this->generatePackageHeader();
			if(!$this->skipMySql) {
				$this->generateMySqlDump();
			}
			$this->generateDump();

			fclose($f);

			chmod($outputFilePath, 0777);
		}

		protected function generatePackageHeader() {
			$f = $this->fh;
			fseek($f, 0);
			fwrite($f, $this->signature);

			fseek($f, 5);
			fwrite($f, $this->version);

			fseek($f, 10);
			fwrite($f, time());

			fseek($f, 25);
			fwrite($f, $this->totalSize);

			fseek($f, 50);
			$author = substr($this->author, 0, 25);
			fwrite($f, $author);

			fseek($f, 75);
			$comment = substr($this->comment, 0, 255);
			fwrite($f, $comment);

			fseek($f, 330);
			fwrite($f, "\n");
		}


		protected function generateDump() {
			$f = $this->fh;
			$sz = sizeof($this->paths);

			for($i = 0; $i < $sz; $i++) {
				$path = $this->paths[$i];
				
				if(file_exists($path)) {
					$filetype = filetype($path);
				} else {
					$filetype = "sql";
				}
				
				switch($filetype) {
					case "file": {
						$obj = new umiDistrFile($path);
						break;
					}

					case "dir": {
						$obj = new umiDistrFolder($path);
						break;
					}
					
					case "sql": {
						if(!$this->skipMySql) {
							$obj = new umiDistrMySql($path);
						}
						break;						
					}

					default: {
						continue;
					}
				}

				if(!$obj) continue;

				$data = $obj->pack();

				$p = ftell($f);
				fwrite($f, strlen($data));
				fseek($f, $p + 25);
				fwrite($f, $data);
				unset($obj);
				unset($data);
			}
		}


		protected function generateMySqlDump() {
			include "./mysql.php";

			$sql = "SHOW TABLES";
			$result = l_mysql_query($sql);
			while(list($table_name) = mysql_fetch_row($result)) {
				$this->paths[] = $table_name;
			}
		}
	};
?>