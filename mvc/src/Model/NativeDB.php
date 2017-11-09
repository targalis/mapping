<?php
namespace Model;

use Silex\Application;

class NativeDB
{
    protected $dbh;
    protected $sth;
    private $q = null;
    private $p = array();
    private static $_instance = null;
    
    public function __construct(Application $app)
    {   
        // Load the database configuration
        $ds = array(
            'driver'        => $app['resources']['database']['driver'],
			'host'          => $app['resources']['database']['host'],
			'dbname'        => $app['resources']['database']['name'],
			'user'          => $app['resources']['database']['user'],
			'password'      => $app['resources']['database']['password'],
			'charset'       => 'utf8',
        );
        
        // print_r(\PDO::getAvailableDrivers());
		
        $connexionString = "{$ds['driver']}:host={$ds['host']};dbname={$ds['dbname']};charset={$ds['charset']}";
		
        $this->dbh = new \PDO($connexionString, $ds['user'], $ds['password']);
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    
    // Transaction
    public function begin(){
        $this->dbh->beginTransaction();
    }
    
    public function commit(){
        $this->dbh->commit();
    }
    
    public function rollback(){
        $this->dbh->rollBack();
    }
    
    public function query($sql, array $parameters = array())
    {
        try {
            $this->q = $sql;
            $this->p = $parameters;
            
            $this->sth = $this->dbh->prepare($sql);
            $this->sth->execute($parameters);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
          echo $e->getMessage();
        }
        
        return $this;
    }
    
    public function getInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    public function fetchAll()
    {
        return $this->sth->fetchAll();
    }
    
    public function fetchRow()
    {
        $rows = $this->fetchAll();
        
        if (empty($rows[0]))
            return false;
        return $rows[0];
    }
    
    public function getQueryString() 
    {
        $string = $this->q;
        $data = $this->p;
        
        $indexed=$data==array_values($data);
        
        foreach ($data as $k=>$v) {
            if (is_string($v)) 
                $v="'$v'";
                
            if ($indexed) 
                $string=preg_replace('/\?/',$v,$string,1);
            else 
                $string=str_replace(":$k",$v,$string);
        }
        return $string;
    }
    
    public function paginate()
    {
        $pos = strrpos(strtolower($this->q), 'limit');
        $exp = substr($this->q, $pos + 5);
        $exp = explode(',', $exp);
        
        $offset = trim($exp[0]);
        $range = trim($exp[1]);

        $rows = $this->fetchAll();
        
        $count_query = "SELECT FOUND_ROWS() AS totalRows";
        $count = $this->query($count_query)->fetchRow();
        
        $current = ($offset/$range) + 1;
        
        $pages = array();
        $total = $count['totalRows'];
        $last = ceil($total/$range);
        
        $next = $current >= $last ? $last : $current + 1;
        $previous = $current - 1;
        $page_number = 5;
        
        if ($page_number < $last) {
            for($i = 0; $i < $page_number; $i++) {
                if($current + $page_number/2 <= $last and $current - $page_number/2 >= 1) {
                    $pages[] = $current - 2 + $i;
                } elseif($current + $page_number/2 > $last) {
                    $pages[] = $last - $page_number + $i;
                } else {
                    $pages[] = 1 + $i;
                }
            }
        } else {
            for($i = 0; $i < $last; $i++) {
                $pages[] = 1 + $i;
            }
        }
        
        $paginator = array();
        $paginator['first'] = 1;
        $paginator['last'] = $last;
        $paginator['current'] = $current;
        $paginator['previous'] = $previous;
        $paginator['next'] = $next;
        $paginator['pages'] = $pages;
        $paginator['total'] = $total;
        
        return array('rows' => $rows, 'paginator' => $paginator);
    }
}