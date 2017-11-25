<<<<<<< HEAD
<?php
	require_once 'C:\Users\Malhar\vendor\autoload.php';
	use GraphAware\Neo4j\Client\ClientBuilder;

	$action = 0;
	function genQuery($input)
	{
		global $action;
		$input = strtolower($input);
		$actorlist = array("kit harington", "maisie williams", "andrew lincoln","bryan cranston","megan boone", 
							"james spader", "grant gustin","kevin spacey", "grace gummer", "noah schnapp", "aaron paul");
		$movielist = array('game of thrones','the walking dead','blacklist','mr robot','breaking bad','house of cards','stranger things','the flash');

		if(in_array($input, $actorlist))
		{
			$query = "MATCH (n:Actor{name:'" .$input. "'})-[r:ACTED_IN]->(s:Show)-[r1:AIRED_BY]->(c:Channel)-[r2:PROVIDED_BY]->(p:Provider) RETURN n,r,s,r1,c,r2,p"; 
			$action = 1;
		}
		else if (in_array($input, $movielist))
		{
			$query = "MATCH (s:Show{name:'" .$input. "'})-[r1:AIRED_BY]->(c:Channel)-[r2:PROVIDED_BY]->(p:Provider) RETURN s,r1,c,r2,p"; 
			$action = 2;
		}
		else 
		{
			echo "The actor/movie does not appear in our database.";
			$query = "MATCH (n)-[r]->(s) RETURN n,r,s";
			$action = 0;
		}
		return $query;
	}
	function processNode($node,&$arr)
	{
		$nodeId = $node->identity();
		$nodeLabels = $node->labels();
		$nodeProp = $node->properties();
		$nodeAll = array( "caption"=>$nodeProp['name'], "type"=>$nodeLabels[0], "id"=>$nodeId);
		$isPres = 0;
		foreach($arr as $exNode)
			if($exNode['id'] == $nodeId)
				$isPres = 1;
		if($isPres == 0)
			array_push($arr,$nodeAll);
	}	
	function processRelation($rel,&$arr)
	{
		$relId = $rel->identity();
		$relType = $rel->type();
		$startNode = $rel->startNodeIdentity();
		$endNode = $rel->endNodeIdentity();
		$relAll = array("source"=>$startNode, "target"=>$endNode, "caption"=>$relType);
		$isPres = 0;
		foreach($arr as $relation)
			if($relation['source'] == $startNode and $relation['target'] == $endNode)
				$isPres = 1;
		if($isPres == 0)
			array_push($arr,$relAll);
	}
	
	$query = "MATCH p=(n)-[r]->(s) RETURN n,r,s";
	if ($_SERVER['REQUEST_METHOD'] === 'POST') 
	{
		if (isset($_POST['btnSearch'])) 
		{
			if(isset($_POST['inp']) && !empty($_POST['inp']))
			{
				$input = $_POST['inp'];
				$query = genQuery($input);
			}
		} 
		else 
		{
			$query = "MATCH p=(n)-[r]->(s) RETURN n,r,s";
		}
	}	
		
	$client = ClientBuilder::create()->addConnection('default', 'http://neo4j:password@localhost:7474')->build();
	$records = $client->run($query);
	
	$nodes = array();
	$relations = array();
	$totalResult = array();
	$answertoprint = array();
	foreach($records->getRecords() as $record)
	{
		if($action == 0)
		{
			$ActorNode = $record->get('n');
			$ShowNode = $record->get('s');
			$RelNode = $record->get('r');
			processNode($ActorNode,$nodes);
			processNode($ShowNode,$nodes);
			processRelation($RelNode,$relations);
		}
		else if($action == 1)
		{
			$ActorNode = $record->get('n');
			$ShowNode = $record->get('s');
			$RelNode = $record->get('r');
			$RelNode1 = $record->get('r1');
			$ChannelNode = $record->get('c');
			$RelNode2 = $record->get('r2');
			$ProvNode = $record->get('p');			
			processNode($ActorNode,$nodes);
			processNode($ShowNode,$nodes);
			processRelation($RelNode,$relations);
			processRelation($RelNode1,$relations);
			processRelation($RelNode2,$relations);
			processNode($ChannelNode,$nodes);
			processNode($ProvNode,$nodes);
			array_push($answertoprint,array("actor"=>$ActorNode->properties()['name'],"show"=>$ShowNode->properties()['name'],
			"channel"=>$ChannelNode->properties()['name'],"provider"=>$ProvNode->properties()['name']));
		}
		else if($action == 2)
		{
			$ShowNode = $record->get('s');
			$ChannelNode = $record->get('c');
			$ProvNode = $record->get('p');
			$RelNode1 = $record->get('r1');
			$RelNode2 = $record->get('r2');
			processNode($ShowNode,$nodes);
			processNode($ChannelNode,$nodes);
			processNode($ProvNode,$nodes);
			processRelation($RelNode1,$relations);
			processRelation($RelNode2,$relations);
			array_push($answertoprint,array("show"=>$ShowNode->properties()['name'],
			"channel"=>$ChannelNode->properties()['name'],"provider"=>$ProvNode->properties()['name']));
		}
	}
	$totalResult['nodes'] = $nodes;
	$totalResult['edges'] = $relations;
	$fp = fopen('results.json', 'w');
	fwrite($fp, json_encode($totalResult));
	fclose($fp);
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title> Project </title>
		<link rel="stylesheet" href="./common/alchemy/alchemy.css"/>
		<link rel="stylesheet" href="index.css"/>
		<script src="./common/jquery-2.1.3.js"></script>
		<script src="./common/d3.js"></script>
		<script src="./common/lodash.js"></script>
	</head>
	<body>
		<div id="container">
			<div id="left">
				<form action="index.php" method="POST">
					<h3>Enter Any Actor/Show</h3>
					<input type="text" name="inp"/>
					</br>
					<input type="submit" name="btnSearch" value="Search"/>
					<input type="submit" name="btnClear" value="Clear"/>
				</form>
				
					<table class="invoice">
						<tbody>
							<?php
								if($action==1)
								{
									echo'<tr>'; 
									echo'<th style="width:120px">'. "Actor" .'</th>';
									echo'<th style="width:150px">'. "Show" .'</th>';
									echo'<th style="width:80px">'. "Channel" .'</th>';
									echo'<th style="width:80px">'. "Provider" .'</th>';
									echo'</tr>';
								}
								if($action==2)
								{
									echo'<tr>'; 
									echo'<th style="width:150px">'. "Show" .'</th>';
									echo'<th style="width:80px">'. "Channel" .'</th>';
									echo'<th style="width:80px">'. "Provider" .'</th>';
									echo'</tr>';
								}
								foreach($answertoprint as $answer)
								{
									echo'<tr>'; 
									foreach($answer as $key=>$value)
									{
										echo '<td>'.$value.'</td>';
									}
									echo'</tr>'; 
								}
							?> 
						</tbody>
					</table>
				
			</div>
			<div id="right">
				<div class="alchemy" id="alchemy"></div>
			</div>
		</div>
		<script src="./common/alchemy/alchemy.js"></script>
		<script type="text/javascript">
		var config = {
			dataSource: 'http://localhost/dbms/results.json',
			graphWidth: function() {return 800},
			graphHeight: function() {return 690},
			backgroundColor: "#FFE0C4",
			nodeCaptionsOnByDefault: true,
			edgeCaptionsOnByDefault: true,
			forceLocked: false,
			caption: function(node){
				return node.caption;
			},
			nodeTypes: {
				"type": ["Actor", "Show", "Channel", "Provider"]
			},
			edgeTypes: {
				"caption": ["ACTED_IN", "PROVIDED_BY", "AIRED_BY"]
			},
			nodeStyle: {
				"Actor": {
					color: "#FF4081",
					borderWidth: 0,
					radius: 24
				},
				"Show": {
					color: "#88cc88",
					borderWidth: 0,
					radius: 24
				},
				"Channel": {
					color: "#ffdd00",
					borderWidth: 0,
					radius: 24
				},
				"Provider": {
					color: "#01adee",
					borderWidth: 0,
					radius: 24
				}
			},
			edgeStyle: {
				"ACTED_IN": {
					color: "#888888",
					width: 7
				},
				"PROVIDED_BY": {
					color: "#888888",
					width: 7
				},
				"AIRED_BY": {
					color: "#888888",
					width: 7
				}
			}
		};
		alchemy = new Alchemy(config)
	</script>
	</body>
=======
<?php
	require_once 'C:\Users\Malhar\vendor\autoload.php';
	use GraphAware\Neo4j\Client\ClientBuilder;

	$action = 0;
	function genQuery($input)
	{
		global $action;
		$input = strtolower($input);
		$actorlist = array("kit harington", "maisie williams", "andrew lincoln","bryan cranston","megan boone", 
							"james spader", "grant gustin","kevin spacey", "grace gummer", "noah schnapp", "aaron paul");
		$movielist = array('game of thrones','the walking dead','blacklist','mr robot','breaking bad','house of cards','stranger things','the flash');

		if(in_array($input, $actorlist))
		{
			$query = "MATCH (n:Actor{name:'" .$input. "'})-[r:ACTED_IN]->(s:Show)-[r1:AIRED_BY]->(c:Channel)-[r2:PROVIDED_BY]->(p:Provider) RETURN n,r,s,r1,c,r2,p"; 
			$action = 1;
		}
		else if (in_array($input, $movielist))
		{
			$query = "MATCH (s:Show{name:'" .$input. "'})-[r1:AIRED_BY]->(c:Channel)-[r2:PROVIDED_BY]->(p:Provider) RETURN s,r1,c,r2,p"; 
			$action = 2;
		}
		else 
		{
			echo "The actor/movie does not appear in our database.";
			$query = "MATCH (n)-[r]->(s) RETURN n,r,s";
			$action = 0;
		}
		return $query;
	}
	function processNode($node,&$arr)
	{
		$nodeId = $node->identity();
		$nodeLabels = $node->labels();
		$nodeProp = $node->properties();
		$nodeAll = array( "caption"=>$nodeProp['name'], "type"=>$nodeLabels[0], "id"=>$nodeId);
		$isPres = 0;
		foreach($arr as $exNode)
			if($exNode['id'] == $nodeId)
				$isPres = 1;
		if($isPres == 0)
			array_push($arr,$nodeAll);
	}	
	function processRelation($rel,&$arr)
	{
		$relId = $rel->identity();
		$relType = $rel->type();
		$startNode = $rel->startNodeIdentity();
		$endNode = $rel->endNodeIdentity();
		$relAll = array("source"=>$startNode, "target"=>$endNode, "caption"=>$relType);
		$isPres = 0;
		foreach($arr as $relation)
			if($relation['source'] == $startNode and $relation['target'] == $endNode)
				$isPres = 1;
		if($isPres == 0)
			array_push($arr,$relAll);
	}
	
	$query = "MATCH p=(n)-[r]->(s) RETURN n,r,s";
	if ($_SERVER['REQUEST_METHOD'] === 'POST') 
	{
		if (isset($_POST['btnSearch'])) 
		{
			if(isset($_POST['inp']) && !empty($_POST['inp']))
			{
				$input = $_POST['inp'];
				$query = genQuery($input);
			}
		} 
		else 
		{
			$query = "MATCH p=(n)-[r]->(s) RETURN n,r,s";
		}
	}	
		
	$client = ClientBuilder::create()->addConnection('default', 'http://neo4j:password@localhost:7474')->build();
	$records = $client->run($query);
	
	$nodes = array();
	$relations = array();
	$totalResult = array();
	$answertoprint = array();
	foreach($records->getRecords() as $record)
	{
		if($action == 0)
		{
			$ActorNode = $record->get('n');
			$ShowNode = $record->get('s');
			$RelNode = $record->get('r');
			processNode($ActorNode,$nodes);
			processNode($ShowNode,$nodes);
			processRelation($RelNode,$relations);
		}
		else if($action == 1)
		{
			$ActorNode = $record->get('n');
			$ShowNode = $record->get('s');
			$RelNode = $record->get('r');
			$RelNode1 = $record->get('r1');
			$ChannelNode = $record->get('c');
			$RelNode2 = $record->get('r2');
			$ProvNode = $record->get('p');			
			processNode($ActorNode,$nodes);
			processNode($ShowNode,$nodes);
			processRelation($RelNode,$relations);
			processRelation($RelNode1,$relations);
			processRelation($RelNode2,$relations);
			processNode($ChannelNode,$nodes);
			processNode($ProvNode,$nodes);
			array_push($answertoprint,array("actor"=>$ActorNode->properties()['name'],"show"=>$ShowNode->properties()['name'],
			"channel"=>$ChannelNode->properties()['name'],"provider"=>$ProvNode->properties()['name']));
		}
		else if($action == 2)
		{
			$ShowNode = $record->get('s');
			$ChannelNode = $record->get('c');
			$ProvNode = $record->get('p');
			$RelNode1 = $record->get('r1');
			$RelNode2 = $record->get('r2');
			processNode($ShowNode,$nodes);
			processNode($ChannelNode,$nodes);
			processNode($ProvNode,$nodes);
			processRelation($RelNode1,$relations);
			processRelation($RelNode2,$relations);
			array_push($answertoprint,array("show"=>$ShowNode->properties()['name'],
			"channel"=>$ChannelNode->properties()['name'],"provider"=>$ProvNode->properties()['name']));
		}
	}
	$totalResult['nodes'] = $nodes;
	$totalResult['edges'] = $relations;
	$fp = fopen('results.json', 'w');
	fwrite($fp, json_encode($totalResult));
	fclose($fp);
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title> Project </title>
		<link rel="stylesheet" href="./common/alchemy/alchemy.css"/>
		<link rel="stylesheet" href="index.css"/>
		<script src="./common/jquery-2.1.3.js"></script>
		<script src="./common/d3.js"></script>
		<script src="./common/lodash.js"></script>
	</head>
	<body>
		<div id="container">
			<div id="left">
				<form action="index.php" method="POST">
					<h3>Enter Any Actor/Show</h3>
					<input type="text" name="inp"/>
					</br>
					<input type="submit" name="btnSearch" value="Search"/>
					<input type="submit" name="btnClear" value="Clear"/>
				</form>
				
					<table class="invoice">
						<tbody>
							<?php
								if($action==1)
								{
									echo'<tr>'; 
									echo'<th style="width:120px">'. "Actor" .'</th>';
									echo'<th style="width:150px">'. "Show" .'</th>';
									echo'<th style="width:80px">'. "Channel" .'</th>';
									echo'<th style="width:80px">'. "Provider" .'</th>';
									echo'</tr>';
								}
								if($action==2)
								{
									echo'<tr>'; 
									echo'<th style="width:150px">'. "Show" .'</th>';
									echo'<th style="width:80px">'. "Channel" .'</th>';
									echo'<th style="width:80px">'. "Provider" .'</th>';
									echo'</tr>';
								}
								foreach($answertoprint as $answer)
								{
									echo'<tr>'; 
									foreach($answer as $key=>$value)
									{
										echo '<td>'.$value.'</td>';
									}
									echo'</tr>'; 
								}
							?> 
						</tbody>
					</table>
				
			</div>
			<div id="right">
				<div class="alchemy" id="alchemy"></div>
			</div>
		</div>
		<script src="./common/alchemy/alchemy.js"></script>
		<script type="text/javascript">
		var config = {
			dataSource: 'http://localhost/dbms/results.json',
			graphWidth: function() {return 800},
			graphHeight: function() {return 690},
			backgroundColor: "#FFE0C4",
			nodeCaptionsOnByDefault: true,
			edgeCaptionsOnByDefault: true,
			forceLocked: false,
			caption: function(node){
				return node.caption;
			},
			nodeTypes: {
				"type": ["Actor", "Show", "Channel", "Provider"]
			},
			edgeTypes: {
				"caption": ["ACTED_IN", "PROVIDED_BY", "AIRED_BY"]
			},
			nodeStyle: {
				"Actor": {
					color: "#FF4081",
					borderWidth: 0,
					radius: 24
				},
				"Show": {
					color: "#88cc88",
					borderWidth: 0,
					radius: 24
				},
				"Channel": {
					color: "#ffdd00",
					borderWidth: 0,
					radius: 24
				},
				"Provider": {
					color: "#01adee",
					borderWidth: 0,
					radius: 24
				}
			},
			edgeStyle: {
				"ACTED_IN": {
					color: "#888888",
					width: 7
				},
				"PROVIDED_BY": {
					color: "#888888",
					width: 7
				},
				"AIRED_BY": {
					color: "#888888",
					width: 7
				}
			}
		};
		alchemy = new Alchemy(config)
	</script>
	</body>
>>>>>>> 941825b337f01d85daa2d7316e841d3433dd48a8
</html>