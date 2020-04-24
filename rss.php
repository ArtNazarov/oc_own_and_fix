<?php
define('HOST_TITLE', 'Site title');
define('HOST_URL', 'https://some_site.com');
define('HOST_DESC', 'Site description');
// Config
class Config {
 private $pass = 'PASS';
 private $host = 'localhost';
 private $db = 'DBNAME';
 private $user = 'USERNAME';
function getPass(){
    return $this->pass;
}
function getHost(){
    return $this->host;
}
function getDb(){
    return $this->db;
}
function getUser(){
    return $this->user;
}
}
// Loader
class ModelRss 
{
  var $mysqli = null;
  var $records = [];
  function __construct()
   {
    $config = new Config();
    $this->mysqli = new mysqli($config->getHost(),$config->getUser(),$config->getPass(),$config->getDb());

		if ($this->mysqli->connect_errno) 
            {
  				throw new Exception("Failed to connect to MySQL: " . $this->mysqli->connect_error);	
	    }
    }
  function __destruct(){
    $this->mysqli->close();
  }
  function prepare($value){
       $value = strip_tags(html_entity_decode($value));
       $value = mb_ereg_replace("&nbsp;", " ", $value);
	   $value = mb_ereg_replace("&", '&amp;', $value);
       $value = mb_ereg_replace('>', '&gt;', $value);
       $value = mb_ereg_replace('<', "&lt;", $value);
       $value = mb_ereg_replace('/\\/gi', "&apos;", $value);
       $value = preg_replace('!<[^>]*?>!', ' ', $value);
 	   $value = preg_replace('#[\x00-\x08\x0B-\x0C\x0E-\x1F]+#is', ' ', $value); 
       return trim($value);
  }
  function loadArticles(){
        $this->mysqli->set_charset("utf8");
		$sql = "SELECT * FROM oc_article_description WHERE 1=1";
		$result =  $this->mysqli->query($sql);
		//$result -> fetch_all(MYSQLI_ASSOC);
        $this->records = [];
    	while ($row = $result->fetch_assoc()) {
        	array_push($this->records, 
                       	[
                        'title' => $row["name"],
                        'date' => $row['date_modified'],
                        'description'=> '<![CDATA[' . $this->prepare($row['description']) .']]>',
                        'url' => HOST_URL . '/index.php?route=blog/article&amp;article_id=' . $row['article_id']
                        	]);
    	};
		$result->free_result();  
    	//var_dump($this->records);
  }
  function getArticles(){
    	return $this->records;
  }

}

class ViewRSS {
  
  	function buildHeader(){
     $head = '<?xml version="1.0"?><rss version="2.0">';
	 $head .= '<channel>';
     $head .='<title>'. HOST_TITLE . '</title>';
	 $head .= '<link>'. HOST_URL .'</link>';
	 $head .= '<description>'. HOST_DESC .'</description>';
	 $head .= '<language>ru</language>';
	 return $head;
    }
  
    function buildFooter(){
             return '
    </channel>
  </rss>';
    }
  
    function buildItems($articles)
  	{
    $items = '';
    //var_dump($articles);
    foreach ($articles as $article)
     {
        $items .= '
        <item>
          <title>'. $article['title'] .'</title>
          <link>' . $article['url'] .'</link>
          <description>'. $article['description'] .'</description>
          <pubDate>'. date('r', strtotime($article['modified'])) .'</pubDate>
          <guid>'. $article['url'] .'</guid>
        </item>';
   	 };
    //var_dump($items);
    return $items;
   }
  function renderXML($articles){
    header("Content-Type: application/rss+xml;charset=utf-8");
    $xml = $this->buildHeader() . $this->buildItems($articles) . $this->buildFooter();
    echo $xml;
  }
}


function main(){
	try {
		$rss = new ModelRss();
		$rss->loadArticles();
		$view = new ViewRSS();
        $view->renderXML($rss->getArticles());
		} 
		catch (Exception $e){
  			echo $e->message;
		}
}

main();
