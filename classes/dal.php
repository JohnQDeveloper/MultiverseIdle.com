<?php
    class DAL {
        public $dbh;
        private $statement;

        /*
        function __construct($username, $password, $hostname, $defaultDatabase) {
            try {
                $this->dbh = new PDO("mysql:host=$hostname;dbname=$defaultDatabase", $username, $password);
            }
            catch(PDOException $e) {
                LogEvent("EMERGENCY", $e); #DB is down is bad obviously
                $this->dbh = false;
            }
        }*/

        function __construct($db) {
            $this->dbh = $db;
        }

        function w($query, $preparedArray = null) {
            return $this->write($query, $preparedArray);
        }

        function r($query, $preparedArray = null, $fetchMode=PDO::FETCH_ASSOC) {
            return $this->read($query, $preparedArray, $fetchMode);
        }

        function rows_affected() {
            return $this->statement->rowCount();
        }

        function write($query, $preparedArray = null) {
           if($this->dbh === false) {
                return false;
            }

            try {
                $this->statement = $this->dbh->prepare($query);

                if($preparedArray == null)
                    $this->statement->execute();
                else {
                    $this->statement->execute($preparedArray);
                    #echo "<h1>E: ";
                    #print_r($this->dbh->errorInfo());
                    #echo "</h1>";
                }

                return true;
            }
            catch(PDOException $e) {
                if(DEBUG)
                    echo $e."<BR />";
                return false;
            }

        }

        // This eventually needs cache, read/write splitting logic
        function read($query, $preparedArray = null, $fetchMode=PDO::FETCH_ASSOC) {
            if($this->dbh === false) {
                return false;
            }

            try {
                $this->statement = $this->dbh->prepare($query);

                if($preparedArray == null)
                    $this->statement->execute();
                else
                    $this->statement->execute($preparedArray);

                return $this->statement->fetchAll($fetchMode);
            }
            catch(PDOException $e) {
                if(DEBUG)
                    echo $e."<BR />";
                return false;
            }
        }
    }
