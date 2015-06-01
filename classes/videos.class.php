<?php

Class Videos
{
	private $connection;
	private static $instance;
	public $condition;
	public $videosArray;
	public $size;

    private function __construct()
    {
        require_once(__DIR__.'/../includes/configuration.php');

        try
        {
            $this->connection = new PDO("mysql:host={$db_host};dbname={$db_schema}", $db_user, $db_password);
            $this->connection->exec('SET CHARACTER SET utf8');
        }  
        catch(PDOException $exception)
        {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
    }

    public static function singleton()
    {
        if(!isset(self::$instance))
        {
            $class = __CLASS__;
            self::$instance = new $class;
        }

        return self::$instance;
    }

    public function getNodes()
    {
    	global $index, $data;
    	try
    	{
	    	$query = $this->connection->prepare("SELECT nodeId, nodeName, parentId FROM treestructure WHERE nodeId > 0");
	    	$query->execute();
	    	$array = $query->fetchAll();
	    	foreach($array as $row)
	    	{
	    		$id = $row["nodeId"];
				$parent_id = $row['parentId'] == "0" ? "NULL" : $row['parentId'];
				$data[$id] = $row;
				$index[$parent_id][] = $id;
	    	}
	    	$this->drawTree();
	    	$this->connection = null;

    	}
    	catch(PDOException $error)
    	{
    		echo "Error" . $error;
    	}
    }

    public function drawTree()
    {
		echo "<ul><li id='categories'><span id='0'><i class='glyphicon glyphicon-folder-open'></i>&nbsp&nbsp Video Collection</span>";
		      $this->drawNodes(NULL, 0);
		echo "</li></ul>";
    }

    public function drawNodes($parent_id, $level)
	{
		global $data, $index;
	    $parent_id = $parent_id === NULL ? "NULL" : $parent_id;
	    if (isset($index[$parent_id])) {
	        echo "<ul>";
	        foreach ($index[$parent_id] as $id)
	        	{
		            if($level != 3)
		            {
		              echo "<li><span id='". $data[$id]['nodeId'] . "'> <i class='glyphicon glyphicon-plus-sign'></i> " . $data[$id]['nodeName']. "</span>";
	            	} 
	            	else 
	            	{
	              	  echo "<li class='movie'><a href='#'><span id='" . $data[$id]['nodeId'] . "'> <i class='glyphicon glyphicon-film'></i> " . $data[$id]['nodeName']. "</span></a>";
	            	}
	            	$this->drawNodes($id, $level + 1);
	            echo "</li>";
	        	}
	        echo "</ul>";
	    }
	}

	public function getArray($nodeId)
	{
		$sql = "SELECT t1.nodeId AS lev1, t2.nodeId AS lev2, t3.nodeId AS lev3, t4.nodeId AS lev4, t5.nodeId AS lev5
		FROM treestructure AS t1
		LEFT JOIN treestructure AS t2 ON t2.parentId = t1.nodeId
		LEFT JOIN treestructure AS t3 ON t3.parentId = t2.nodeId
		LEFT JOIN treestructure AS t4 ON t4.parentId = t3.nodeId
		LEFT JOIN treestructure AS t5 ON t5.parentId = t4.nodeId
		WHERE t1.nodeId = $nodeId";

		try 
		{
	    	$query = $this->connection->prepare($sql);
	    	$query->execute();
	    	$nodesArray = $query->fetchAll();
			$this->getHierarchy($nodesArray);
		}
    	catch(PDOException $error)
    	{
    		echo "Error" . $error;
    	}		
	}

	public function getHierarchy($array)
	{
		$iDx = 0;
		foreach ($array as $row) 
		{
			$hierarchy[0][$iDx] = $row['lev1'];
			$hierarchy[1][$iDx] = $row['lev2'];
			$hierarchy[2][$iDx] = $row['lev3'];
			$hierarchy[3][$iDx] = $row['lev4'];
			$hierarchy[4][$iDx] = $row['lev5'];
			$iDx++;
		}

		$this->getDescendants($hierarchy);
	}

	public function getDescendants($hierarchy)
	{
		for($i = 1; $i <= 4; $i++)
	    {
	      if(empty($hierarchy[$i][0]))
	      {
	        $descendants = array_unique($hierarchy[$i-1]);
	        break;
	      }
	      if($i==4)
	      {
	        $descendants = array_unique($hierarchy[$i]);
	      }
	    }

	    $this->makeSQlCondition($descendants);
	}

	public function makeSQlCondition($descendants)
	{
		$this->condition = "";

		for($i = 0; $i <= sizeof($descendants) - 1; $i ++) 
		{
			$this->condition.= "nodeId=$descendants[$i] OR ";
		}

		$this->condition .= "nodeId=-1";
	}

	public function getVideos($condition, $page)
	{
		$results = 42;

		try 
		{
	    	$query = $this->connection->prepare("SELECT videoId, title, location FROM videos WHERE $condition LIMIT " .(( $page - 1) * $results) . ", $results");
	    	$query->execute();
	    	$array = $query->fetchAll();
	    	$this->videosArray = array("videos" => array("video" => $array));
	    	$this->connection = null;	    	
		}
    	catch(PDOException $error)
    	{
    		echo "Error" . $error;
    	}	
	}

	public function getTotalVideos($condition)
	{
		try 
		{
	    	$query = $this->connection->prepare("SELECT COUNT(*) AS size FROM videos WHERE $condition");
	    	$query->execute();
	    	$this->size = $query->fetch(PDO::FETCH_ASSOC);
		}
    	catch(PDOException $error)
    	{
    		echo "Error" . $error;
    	}			
	}

	public function getNumberofVideosperCategory()
	{
		try
		{
			$query = $this->connection->prepare("SELECT t1.nodeName AS lev1, t2.nodeName AS lev2, t3.nodeName AS lev3, t4.nodeName AS lev4, t5.nodeName AS lev5, COUNT(t6.nodeId) AS size
												FROM treestructure AS t1
												LEFT JOIN treestructure AS t2 ON t2.parentId = t1.nodeId
												LEFT JOIN treestructure AS t3 ON t3.parentId = t2.nodeId
												LEFT JOIN treestructure AS t4 ON t4.parentId = t3.nodeId
												LEFT JOIN treestructure AS t5 ON t5.parentId = t4.nodeId
												LEFT JOIN videos AS t6 ON t6.nodeId = t5.nodeId 
												GROUP BY t6.nodeId
												ORDER BY t1.nodeId");
			$query->execute();
			return $query->fetchAll();
			$this->connection = null;
		}
    	catch(PDOException $error)
    	{
    		echo "Error" . $error;
    	}		
	}

	public function drawbars($array)
	{
		$temp = 0;
		foreach ($array as $number) {
			$temp = $temp + $number;
		}

		foreach ($array as $number) {
			$percent = $number / $temp * 100;
		?>

		<div class="progress" id="progress">
		  <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $percent; ?>%;">
		    <span class="sr-only"></span>
		  </div>
		</div>

		<?php

		}		
	}

}
?>