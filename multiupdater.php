<?php

//https://habrahabr.ru/sandbox/76140/
require_once 'lib/Threads.php';

//for($i = 1; $i <= 100; $i++){
//	$Thread->Create(function() use ($i){
//		echo $i++;
//		echo '<br />';
//	});
//}
//
//$response = $Thread->Run();
//echo '<hr /> OK ';
//echo count($response);
//echo ' from 100';
//exit;


define('SITE', 'https://www.emtika.ru');

// Version
define('VERSION', '2.0.1.1');
// Configuration
require_once "../config.php";
// Startup
require_once(DIR_SYSTEM . 'startup.php');
// Registry
$registry = new Registry();
// Config
$config = new Config();
$registry->set('config', $config);

// Session
$session = new Session();
$registry->set('session', $session);
// Log
$log = new Log('parser2.log');
$registry->set('log', $log);
// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

$log->write("Обновление товаров с сайта emtika.ru");

$start = microtime(true);
$all_counter = 0;
$add_product_counter = 0;
$edit_product_counter = 0;
$error_counter = 0;

$session->data['add_product_counter'] = 0;
$session->data['edit_product_counter'] = 0;

//var_dump($session->data); exit;



require_once 'lib/parser.class.php';

$data = array(
	'db' => $db,
	'site' => SITE,
	'oc_image_path' => DIR_IMAGE . 'catalog/emtika/',
	'log' => $log
);

$parser = new parser($data);

//$session->data['parser'] = $parser;

//$parser->addLinksToOCFromParsingFile(DIR_UPLOAD . 'emtika.csv', SITE);

//var_dump(1); exit;

$product_urls = $parser->getProductLinksFromOC(SITE);

//$product_urls = array($product_urls[0]);

//var_dump($product_urls); exit;
foreach ($product_urls as $product_url){
	$all_counter++;

	if (isset($product_url['product_id']) AND $product_url['product_id']){
		//обновление товара
		//if ($parser->updateProductFromUrl($product_url['product_id'],$product_url)){
			//$edit_product_counter++;
		//};
//		$_SESSION['url'] = $product_url['url'];
//		$session->data['url'] = $product_url['url'];
//		$data_x = array(
//			'product_url' => $product_url,
//			'parser' => $parser
//		);
//		$Thread->Create(function() use ($data_x){
//			$data_x['parser']->updateProductFromUrl($data_x['product_url']['product_id'],$data_x['product_url']);
//			//echo $data_x['product_url']['url'];
//		});


	}else{
		//добавление товара
		$session->data['add_product_counter']++;
		//var_dump($product_url); exit;
//		if ($add_product_counter >= 10){
//			continue;
//			//var_dump($time, $add_product_counter, $all_counter); exit;
//		}
//
//		if ($parser->addProductFromUrl($product_url['url'])){
			//$add_product_counter++;
		//};

//			$url = $product_url['url'];
//			$_SESSION['url'] = $product_url['url'];
//			$Thread->Create(function() use ($url){
//				echo($_SESSION['url']);
//			});

		$data_x = array(
			'product_url' => $product_url,
			'data' => $data
		);
		$Thread->Create(function() use ($data_x){
			//$parser = new parser($data_x['data']);
			echo 5;
			//$parser->addProductFromUrl($data_x['product_url']['url']);
		});

	}
}
$log->write("Задачи добавлены, начинаем выполение");
$Thread->Run();

$add_product_counter = $session->data['add_product_counter'];
$edit_product_counter = $session->data['edit_product_counter'];

$time = microtime(true) - $start;
$log->write("Всего обработано $all_counter товаров. Добавлено: $add_product_counter. Обновлено: $edit_product_counter. Время выполения скрипта: $time");

exit;

$product_links_data = array(
	'start_link' => '/catalog/',
	'search_link_without' => array('goods_','/#','/tel:'),
	'search_link_with' => array('catalog/otdelochnye-materialy/','catalog/stroitelnye-materialy/','/sadovyj-inventar/'),
	'product_link_without' => array(),
	'product_link_with' => array('goods_')
);

//$product_links = $parser->getProductsLinks($product_links_data);
$first_links = $parser->getFirstLinks($product_links_data);

var_dump($first_links); exit;

//$log->write('Начало работы парсера...');



var_dump(111); exit;
