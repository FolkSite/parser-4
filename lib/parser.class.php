<?php

class parser
{
	var $db;
	var $site;
	var $api_image_path;
	var $oc_image_path;
	var $language_id = 1;
	var $log;
	var $attr_group_id = 7;
	var $stock_status_id = 5;

	public function __construct($data) {
		$this->db = $data['db'];
		$this->oc_image_path = $data['oc_image_path'];
		$this->log = $data['log'];
		$this->site = $data['site'];
		require_once "simple_html_dom.php";
	}

	//https://www.koz1024.net/curl-site-parser/




	//https://hello-site.ru/web-notes/parsing-saitov-php/
	//http://www.cyberforum.ru/php-regex/thread761589.html

	public function getFirstLinks($search_data, $result = array()){
		if (file_exists($this->site . $search_data['start_link'])==true){
			$contents = file_get_contents($this->site . $search_data['start_link']);
		}else{
			return $result;
		}



		preg_match_all('/href="([^"]+)"/', $contents, $site_pages_data);

		if(count($site_pages_data[1])){
			$site_pages_data[1] = array_unique($site_pages_data[1]);
			$results = array_diff($site_pages_data[1],$result);
			if ($results){
				foreach($results as $site_page_link){
					//$site_page_link = trim($site_page_link);
					//if (!in_array($site_page_link,$result) AND $site_page_link AND $this->_checkHref($site_page_link, $search_data['search_link_with'], $search_data['search_link_without'])){
					//$this->log->write('Страница с ссылками на товары: ' . $this->site . $site_page_link);
					$result[] = $site_page_link;
					$search_data['start_link'] = $site_page_link;
					unset($site_pages_data);
					$this->getFirstLinks($search_data,$result);
					//}
				}
			}

		}
		return $result;
	}


	public function getProductsLinks($search_data){
		$result = array();
		$site_page_links = array();
		$contents = file_get_contents($search_data['start_link']);
		preg_match_all('/href="([^"]+)"/', $contents, $site_pages_data);

		if(count($site_pages_data[1])){
			foreach($site_pages_data[1] as $site_page_link){
				$site_page_link = trim($site_page_link);
				//если с ссылки уже собирали - дальше
				if (in_array($site_page_link,$site_page_links)){
					continue;
				}
				$site_page_links[] = $site_page_link;
				if ($site_page_link AND $this->_checkHref($site_page_link, $search_data['search_link_with'], $search_data['search_link_without'])){
					//$this->log->write('Страница с ссылками на товары: ' . $this->site . $site_page_link);
					$contents = file_get_contents($this->site . $site_page_link);
					preg_match_all('/href="([^"]+)"/', $contents, $product_pages_data);
					if(count($product_pages_data[1])){
						foreach($product_pages_data[1] as $product_page_link){
							$full_link = trim($this->site . $product_page_link);
							if (!in_array($full_link,$result)){
								if ($product_page_link  AND $this->_checkHref($product_page_link, $search_data['product_link_with'], $search_data['product_link_without'])){
									//$this->log->write('Ссылка на товар: ' . $this->site . $product_page_link);
									$result[] = $this->site . $product_page_link;

								}
							}
						}
					}
				}
			}
		}
		return $result;
	}

	//https://xdan.ru/uchimsya-parsit-saity-s-bibliotekoi-php-simple-html-dom-parser.html
	public function getProductsLinksWithLib($search_data){
		$result = array();
		$site_pages_data = file_get_html($search_data['start_link']);
		if($site_pages_data->innertext!='' and count($site_pages_data->find('a'))){
			foreach($site_pages_data->find('a') as $site_page_link){
				if ($site_page_link->href AND $this->_checkHref($site_page_link->href, $search_data['search_link_with'], $search_data['search_link_without'])){
					$this->log->write('Страница с ссылками на товары: ' . $this->site . $site_page_link->href);
					$product_pages_data = file_get_html($this->site . $site_page_link->href);
					if($product_pages_data->innertext!='' and count($product_pages_data->find('a'))){
						foreach($product_pages_data->find('a') as $product_page_link){
							//var_dump($product_page_link->href, $search_data['product_link_with'], $search_data['product_link_without']); exit;
							if ($product_page_link->href  AND $this->_checkHref($product_page_link->href, $search_data['product_link_with'], $search_data['product_link_without'])){
								if (!in_array($this->site . $product_page_link->href, $result)){
									$this->log->write('Ссылка на товар: ' . $this->site . $product_page_link->href);
									$result[] = $this->site . $product_page_link->href;
								}
							}
						}
					}
					$product_pages_data->clear();// подчищаем за собой
					unset($product_pages_data);
				}
			}
		}
		return $result;
	}

	//в урле должно быть обязательное слово и не должно быть стоп слова
	private function _checkHref($link, $with, $without){
		//todo если нет обязательных слов
		foreach ($with as $w){
			//если в урле есть обязательное слово
			if (strpos($link,$w) !== false){
				//если стоп слов нет - тру
				if (!$without){
					return true;
				}else{
					foreach ($without as $wo) {
						//и если в урле нет стопслова
						if (strpos($link, $wo) === false) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	public function addLinksToOCFromParsingFile($filename,$donor_url){
//		$this->db->query("DROP TABLE IF EXISTS
//		`" . DB_PREFIX . "product_url`");
//
//		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_url` (
//		`sku` VARCHAR(255),
//		`site` VARCHAR(255),
//		`url` text DEFAULT '',
//		PRIMARY KEY (`sku`,`site`)) DEFAULT CHARSET=utf8");

		//$this->db->query("CREATE INDEX sku ON " . DB_PREFIX ."product (sku)");

		//settings
		$start = 2;
		$iconv = 0;
		$must_to_be_field = false;

		$sku_field_num = 1;
		$url_field_num = 0;

		if (($file = fopen($filename, "r")) !== FALSE) {
			for ($x = 1; $x < $start; $x++) {
				$data = fgetcsv($file, 1, ";");
				continue;
			}

			while (($data = fgetcsv($file, 0, ';', '"')) !== FALSE)
			{
				$insertValues = array();

				foreach ($data as $v) {
					if ($iconv == 1) {
						$v = iconv('windows-1251', 'utf-8', $v);
					}
					$insertValues[] = addslashes(trim($v));
				}
				if (isset($must_to_be_field) AND $must_to_be_field) {
					if ($insertValues[$must_to_be_field] == "") {
						continue;
					}
				}
				//var_dump($insertValues[$sku_field_num], $insertValues[$url_field_num]); exit;
				if (isset($insertValues[$sku_field_num]) AND $insertValues[$sku_field_num] AND isset($insertValues[$url_field_num]) AND $insertValues[$url_field_num]){
					$this->db->query("
					INSERT IGNORE INTO " . DB_PREFIX . "product_url SET
					 sku = '" . $this->db->escape($insertValues[$sku_field_num]) . "',
                     site = '" . $this->db->escape($donor_url) . "',
                     url='" . $this->db->escape($insertValues[$url_field_num]) . "'
					");
				}

				//var_dump($insertValues); exit;
			}

		}


	}

	public function getProductLinksFromOC($site){

		//$this->db->query("CREATE INDEX sku ON " . DB_PREFIX ."product_url (sku)");

		$query = $this->db->query("SELECT p.product_id, p.price, pu.url FROM `" . DB_PREFIX . "product_url` pu
		LEFT JOIN " . DB_PREFIX . "product p ON(pu.sku = p.sku)
		WHERE site = '" . $this->db->escape($site) . "'
		");
		return $query->rows;
	}


	//---------------------------------------------------------


	public function addCategory($data){
		//var_dump($data); exit;
		$category_id = $data->id;
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category` WHERE
		category_id = '" . (int)$data->id . "' AND parent_id = '" . (int)$data->parent . "'
		");
		if (!$query->num_rows){
			//$data["name"] = iconv('windows-1251', 'utf-8', $data["name"]);
			$this->db->query("
				INSERT IGNORE INTO " . DB_PREFIX . "category SET
					 category_id = '" . (int)$data->id . "',
					 parent_id = '" . (int)$data->parent . "',
                     top = 1,
                     status = 1
					");
			$this->db->query("
					INSERT IGNORE INTO " . DB_PREFIX . "category_description SET
					 category_id = '" . (int)$category_id . "',
                     language_id = '" . (int)$this->language_id . "',
                     name='" . $this->db->escape($data->name) . "'
					");
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "'");
			$keyword = $this->createSeoUrl((string)$data->name);
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($keyword) . "'");
		}else{
//			$seo_url = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$query->row['product_id'] . "'");
//			if (isset($query->row['name']) AND (!isset($seo_url->row['keyword']) OR !$seo_url->row['keyword'])) {
//				$keyword = $this->createSeoUrl($query->row['name']);
//				$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$query->row['category_id'] . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
//				$this->log->write('Товар установлен ЧПУ. ID: '. $query->row['product_id']);
//			}
		}
		return $category_id;
	}
	public function copyImage($from_url, $sku = ''){
		$ext = end(explode('.', basename($from_url)));
		$to_url = $this->oc_image_path . (string)$sku . '.' . $ext;
		$result = 'catalog/emtika/' . (string)$sku. '.' . $ext;
		//var_dump($from_url, $to_url, $result); exit;
		if (!file_exists($to_url))
		{
			if (!copy($from_url, $to_url))
			{
				$this->log->write('Не удалось скопировать изображение.');
				return '';
			}
		}
		return $result;
	}

	public function addProductFromUrl($url){
		//var_dump($url); exit;
		$text_product = $this->_curl($url);
		//$text_product = $this->_multicurl($url);
		//var_dump($text_product); exit;

		$data = $this->parsingDataFromHtml($text_product, array(
			'price' => array(
				'<p class="price">',
				'</span>'
			),
			'name' => array(
				'<h1 class="page-title">',
				'</h1>'
			),
			'sku' => array(
				'<span class="sku" itemprop="sku">',
				'</span>'
			),
			'manufacturer' => array(
				'Бренд:</strong>',
				'</a>'
			),
			'description' => array(
				'id="tab-description">',
				'</div>'
			)
		));

		$data['price'] = str_replace(' ','',$data['price']);
		$data['price'] = str_replace(',','.',$data['price']);





		$image = $this->_getImageUrlFromHtml($text_product);

		if ($image){

			//var_dump($image, $data, $url); exit;

			$data['image'] = $this->copyImage($image, $data['sku']);
		}else{
			$data['image'] = '';
		}

		$data['model'] = 'em-'.$data['sku'];

		$data['quantity'] = 1000;

		$data['categories'] = $this->_getCategoriesFromHtml($text_product);
		array_shift($data['categories']);
		array_shift($data['categories']);
		array_unshift($data['categories'],'Источники света и электрооборудование');

		$manufacturer_id = 0;
		if ($data['manufacturer']){
			$query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name='" . $this->db->escape($data['manufacturer']) . "'");
			if (isset($query->row['manufacturer_id'])){
				$manufacturer_id = $query->row['manufacturer_id'];
			}else{
				$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET
						name = '" . $this->db->escape($data['manufacturer']) . "'
					");
				$manufacturer_id = $this->db->getLastId();
				$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET
						manufacturer_id  = '" . (int)$manufacturer_id . "'
					");
			}
		}

		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET
			model = '" . $this->db->escape($data['model']). "',
			sku = '" . $this->db->escape($data['sku']) . "',
			image = '" . $this->db->escape($data['image']) . "',
			quantity = '" . (int)$data['quantity'] . "',
			stock_status_id = '" . (int)$this->stock_status_id . "',
			manufacturer_id = '" . (int)$manufacturer_id  . "',
			price = '" . (float)$data['price'] . "',
			status = '1'
			");
		$product_id = $this->db->getLastId();

		$parent_id = 0;
		foreach ($data['categories'] as $key => $v){
			$query = $this->db->query("
					SELECT c.category_id FROM " . DB_PREFIX . "category_description cd
					LEFT JOIN " . DB_PREFIX . "category c USING (category_id)
					WHERE cd.name='" . $this->db->escape($v) . "'
					AND c.parent_id='" . (int)$parent_id . "'
					");
			//var_dump($query->row['category_id']); exit;
			if (isset($query->row['category_id'])){
				$category_id = $query->row['category_id'];
			}else{
				//если это первая категория, сделать ее топ
				if ($key == 0){
					$category_top = 1;
				}else{
					$category_top = 0;
				}
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "category SET
						 parent_id = '" . (int)$parent_id . "',
	                     top = '" . (int)$category_top . "',
	                     status = 1
						");

				$category_id = $this->db->getLastId();

				$this->db->query("
						INSERT INTO " . DB_PREFIX . "category_description SET
						 category_id = '" . (int)$category_id . "',
	                     language_id = '" . (int)$this->language_id . "',
	                     name='" . $this->db->escape($v) . "'
						");

				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "'");
			}
			$parent_id = $category_id;
		}

		$this->db->query("
					INSERT INTO " . DB_PREFIX . "product_to_category SET
					category_id = '" . (int)$category_id . "',
					product_id = '" . (int)$product_id . "'
					");

		$this->db->query("
				INSERT INTO " . DB_PREFIX . "product_description SET
				product_id = '" . (int)$product_id . "',
				language_id = '" . (int)$this->language_id . "',
				name= '" . $this->db->escape($data['name']) . "',
				description= '" . $this->db->escape($data['description']) . "',
				meta_title = 'Купить " . $this->db->escape($data['name']) . "'
				");

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "'");

		//var_dump($url, $data); exit;
		//var_dump($url, $new_data); exit;

		return true;
		//var_dump($url); exit;
	}


	public function addProductOption($product_id, $options_array){

		foreach ($options_array as $option_array){
			$option_name = $option_array['name'];
			$option = $this->db->query("SELECT option_id FROM `" . DB_PREFIX . "option_description`
				WHERE name = '" . $this->db->escape($option_name) . "'
			");

			//var_dump($option->row["option_id"]); break;
			if (!isset($option->row["option_id"])){
				$this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET
				type = 'select',
				sort_order = '1'
				");

				$option_id = $this->db->getLastId();

				$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET
				option_id = '" . (int)$option_id . "',
				language_id = '2',
				name = '" . $this->db->escape($option_name) . "'
				");
			}else{
				//Иначе получаем id найденной опции
				$option_id = $option->row["option_id"];
			}

			$product_option_sql = $this->db->query("
				SELECT product_option_id FROM " . DB_PREFIX . "product_option
					WHERE product_id = '" . (int)$product_id . "'
					AND option_id = '" . (int)$option_id . "'
				");

			if (!isset($product_option_sql->row["product_option_id"])){
				$this->db->query("
				INSERT INTO " . DB_PREFIX . "product_option SET
				product_id = '" . (int)$product_id . "',
				option_id = '" . (int)$option_id . "',
				required = '" . 0 . "'");
				$product_option_id = $this->db->getLastId();
			}else{
				$product_option_id = $product_option_sql->row["product_option_id"];
			}

			foreach ($option_array['values'] as $option_value) {
				$option_value_sql = $this->db->query("SELECT option_value_id FROM `" . DB_PREFIX . "option_value_description`
				WHERE name = '" . $this->db->escape($option_value) . "'
				");

				if (!isset($option_value_sql->row["option_value_id"])){
					$this->db->query("
					INSERT INTO " . DB_PREFIX . "option_value SET
					option_id = '" . (int)$option_id . "',
					image = '',
					sort_order = '" . 1 . "'");
					$option_value_id = $this->db->getLastId();
				}else{
					$option_value_id = 	$option_value_sql->row["option_value_id"];
				}

				$this->db->query("
				INSERT IGNORE INTO " . DB_PREFIX . "option_value_description SET
				option_value_id = '" . (int)$option_value_id . "',
				language_id = '" . 2 . "',
				option_id = '" . (int)$option_id . "',
				name = '" . $this->db->escape($option_value) . "'");


				$product_option_value_sql = $this->db->query("
					SELECT 	product_option_value_id FROM " . DB_PREFIX . "product_option_value
						WHERE product_option_id = '" . (int)$product_option_id . "'
						AND product_id = '" . (int)$product_id . "'
					    AND option_id = '" . (int)$option_id . "'
						AND option_value_id = '" . (int)$option_value_id . "'
					");

				if (!isset($product_option_value_sql->row['product_option_value_id'])){
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET
					product_option_id = '" . (int)$product_option_id . "',
					product_id = '" . (int)$product_id . "',
					option_id = '" . (int)$option_id . "',
					option_value_id = '" . (int)$option_value_id . "',
					quantity = '1',
					subtract = '1',
					price = '0',
					price_prefix = '+',
					points = '0',
					points_prefix = '+',
					weight = '0',
					weight_prefix = '+'
					");
				}

			}
		}
		return true;
	}



	function addProduct($db,$data){
		//задать класс налогов
		$tax_class_id = 11;
		//задать локацию
		$location = "FK";

		$query = $db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name='" . $db->escape($data['manufacturer']) . "'");
		if (isset($query->row['manufacturer_id'])){
			$manufacturer_id = $query->row['manufacturer_id'];
		}else{
			$db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET
						name = '" . $db->escape($data['manufacturer']) . "'
					");
			$manufacturer_id = $db->getLastId();
			$db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET
						manufacturer_id  = '" . (int)$manufacturer_id . "'
					");
		}

		$db->query("INSERT INTO " . DB_PREFIX . "product SET
				model = '" . $db->escape($data['sku']) . "',
				sku = '" . $db->escape($data['sku']) . "',
				image = '" . $db->escape($data['image']) . "',
				location = '" . $db->escape($location) . "',
				quantity = '" . (int)$data['quantity'] . "',
				stock_status_id = '1',
				jan = '1',
				date_added = NOW(),
				tax_class_id = '" . (int)$tax_class_id . "',
				manufacturer_id = '" . (int)$manufacturer_id . "',
				price = '" . (float)$data['price'] . "',
				status = '1'
				");
		$product_id = $db->getLastId();

		//получение категории или создание новой
		$parent_id = 0;
		//	$categories = explode($data['explode_cat'],$data['categories']);
		//	$category = explode($data['explode_child_cat'],$categories[0]);
		//var_dump($data['categories']); exit;

		if(!is_array($data['categories'])){
			var_dump('Старый вариант категорий'); exit;
		}

		foreach ($data['categories'] as $key => $v){
			$query = $db->query("
					SELECT c.category_id FROM " . DB_PREFIX . "category_description cd
					LEFT JOIN " . DB_PREFIX . "category c USING (category_id)
					WHERE cd.name='" . $db->escape($v) . "'
					AND c.parent_id='" . (int)$parent_id . "'
					");
			//var_dump($query->row['category_id']); exit;
			if (isset($query->row['category_id'])){
				$category_id = $query->row['category_id'];
			}else{
				//если это первая категория, сделать ее топ
				if ($key == 0){
					$category_top = 1;
				}else{
					$category_top = 0;
				}
				$db->query("
					INSERT INTO " . DB_PREFIX . "category SET
						 parent_id = '" . (int)$parent_id . "',
	                     top = '" . (int)$category_top . "',
	                     status = 1
						");

				$category_id = $db->getLastId();

				$db->query("
						INSERT INTO " . DB_PREFIX . "category_description SET
						 category_id = '" . (int)$category_id . "',
	                     language_id = '" . (int)$this->language_id . "',
	                     name='" . $db->escape($v) . "'
						");

				$db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "'");
			}
			$parent_id = $category_id;
		}

		$db->query("
					INSERT INTO " . DB_PREFIX . "product_to_category SET
					category_id = '" . (int)$category_id . "',
					product_id = '" . (int)$product_id . "'
					");

		$db->query("
					INSERT INTO " . DB_PREFIX . "product_description SET
					product_id = '" . (int)$product_id . "',
					language_id = '" . (int)(int)$this->language_id . "',
					name= '" . $db->escape($data['name']) . "',
					description= '" . $db->escape($data['description']) . "'
					");

		$db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "'");


		if(count($data['images'])){
			foreach($data['images'] as $image){
				$db->query("INSERT INTO " . DB_PREFIX . "product_image SET
					product_id = '" . (int)$product_id . "',
					image = '" . $db->escape($image) . "'
				");
			}
		}

		//var_dump($data['attributes']); exit;
		//	//атрибуты
		//	//TODO создать группу атрибутов и задать сюда их id
		$attr_group_id = 9;
		if (count($data['attributes'])){
			foreach ($data['attributes'] as $key => $v){
				$attr_name = $v[0];
				$attr_val = $v[1];
				$query = $db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name='" . $db->escape($attr_name) . "'");
				if (isset($query->row['attribute_id'])){
					$attr_id = $query->row['attribute_id'];
				}else{
					$db->query("INSERT INTO " . DB_PREFIX . "attribute SET
						attribute_group_id = '" . (int)$attr_group_id . "'
					");
					$attr_id = $db->getLastId();
					$db->query("INSERT INTO " . DB_PREFIX . "attribute_description SET
					attribute_id  = '" . (int)$attr_id . "',
					name = '" . $db->escape($attr_name) . "',
					language_id = '" . (int)(int)$this->language_id . "'
					");
				}

				$db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET
					product_id  = '" . (int)$product_id . "',
					attribute_id  = '" . (int)$attr_id . "',
					text = '" . $db->escape($attr_val) . "',
					language_id = '" . (int)(int)$this->language_id . "'
					");
			}
		}
	}

	public function getPath($category_id) {
		$query = $this->db->query("SELECT c.category_id, name, parent_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY c.sort_order, cd.name ASC");

		if (isset($query->row['parent_id']) AND $query->row['parent_id']) {
			return $this->getPath($query->row['parent_id']) . '//' . $query->row['category_id'];
		} elseif (isset($query->row['category_id'])) {
			return $query->row['category_id'];
		}
	}
	public function updateProductFromUrl($product_id, $data){
		$text_product = $this->_curl($data['url']);
		$new_data = $this->parsingDataFromHtml($text_product, array(
			'price' => array(
				'<p class="price">',
				'</span>'
			)
		));

		$new_data['price'] = str_replace(' ','',$new_data['price']);
		$new_data['price'] = str_replace(',','.',$new_data['price']);

		//var_dump((int)$new_data['price'], (int)$data['price'], $data['url'], $product_id); exit;

		//без учета копеек
		if (isset($new_data['price']) AND (int)$new_data['price'] != (int)$data['price']){
			$this->db->query("UPDATE " . DB_PREFIX . "product SET
				price = '" . (int)$new_data['price'] . "'
				WHERE product_id = '" . (int)$product_id . "'
				");
			//$this->log->write($data['url'].' У товара изменена цена. Код товара: '. $data['model']);
			return true;
		}
		return false;
	}

	private function _getImageUrlFromHtml($html){
		$result = '';
		$html = str_get_html($html);
		foreach($html->find('div.ql_main_images a.woocommerce-main-image') as $img){
			$result = $img->href;
			break;
		}
		$html->clear();
		unset($html);
		return $result;
	}

	private function _getDescriptionFromHtml($html){
		$result = '';
		$html = str_get_html($html);
		foreach($html->find('div.b-har') as $desc){
			$result = $desc->innertext;
			break;
		}
		$html->clear();
		unset($html);
		return $result;
	}

	private function _getCategoriesFromHtml($html){
		$result = array();
		$html = str_get_html($html);
		foreach($html->find('div#breadcrumbs a') as $cat){
			$result[] = trim($cat->plaintext);
		}
		$html->clear();
		unset($html);
		return $result;
	}

	public function parsingDataFromHtml($html, $data){
		$result = array();
		//$text_product = iconv('windows-1251', 'utf-8', $html);
		$text_product = $html;
		foreach ($data as $result_key=>$limits){
			if (isset($limits['simple_html_dom']) AND $limits['simple_html_dom']){
				//var_dump($limits['simple_html_dom']); exit;

				continue;
			}
			$result[$result_key] = 	trim($this->cutTextStartEnd($text_product, $limits[0], $limits[1]));
			$result[$result_key] = strip_tags($result[$result_key]);
		}
		return $result;
	}

	function  cutTextStartEnd($text, $start, $end) {
		$posStart = stripos($text, $start);
		//var_dump($posStart); exit;
		if ($posStart === false) return  false;
		$text =  substr($text,$posStart+strlen( $start ));
		$posEnd = stripos($text, $end );
		if ($posEnd === false) return  false;
		$result = substr($text,0,  0-(strlen($text)-$posEnd));
		return $result;
	}


	public function createSeoUrl($product_name){
		if (function_exists('transliterator_transliterate'))
		{
			$transliterator = "Any-Latin; NFD; Lower();";
			$string = transliterator_transliterate($transliterator, $product_name);
			$string = preg_replace("/[^\p{L}\p{N}\s\-]/u", '', strtolower($string));
			$string = preg_replace('/[-\s]+/', '-', $string);
			return trim($string, '-');
		}else{
			return '';
		}
	}
	public function delAll()
	{
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_to_category");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_description");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_to_store");
//		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "manufacturer");
//		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "manufacturer_to_store");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_attribute");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_description");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_to_store");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "attribute_description");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "attribute");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_image");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_path");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "option");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "option_description");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "option_value");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "option_value_description");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_option");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_option_value");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "url_alias");
		return true;
	}

	function _curl($url, $proxy = false, $post = '') {
		$user_agent = array();
		$user_agent[] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.205 Safari/534.16';
		$user_agent[] = 'Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.8.1.6) Gecko/2007072300 Iceweasel/2.0.0.6 (Debian-2.0.0.6-0etch1+lenny1)';
		$user_agent[] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';
		$user_agent[] = 'Mozilla/5.0 (X11; U; Linux i686; cs-CZ; rv:1.7.12) Gecko/20050929';
		$user_agent[] = 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.9.168 Version/11.51';
		$user_agent[] = 'Mozilla/5.0 (Windows; I; Windows NT 5.1; ru; rv:1.9.2.13) Gecko/20100101 Firefox/4.0';
		$user_agent[] = 'Opera/9.80 (Windows NT 6.1; U; ru) Presto/2.8.131 Version/11.10';
		$user_agent[] = 'Opera/9.80 (Macintosh; Intel Mac OS X 10.6.7; U; ru) Presto/2.8.131 Version/11.10';
		$user_agent[] = 'Mozilla/5.0 (Macintosh; I; Intel Mac OS X 10_6_7; ru-ru) AppleWebKit/534.31+ (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent[array_rand($user_agent)]);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if ($proxy){
			curl_setopt($ch, CURLOPT_PROXY, "$proxy");
		}

		$html = $this->curl_redirect_exec($ch);
		curl_close($ch);
		if(!$html || empty($html) || strlen($html) < 2 ){
			if(function_exists('file_get_contents')){
				$html = file_get_contents($url);
			}
		}
		//$html = file_get_contents($url);
		return $html;
	}

	function curl_redirect_exec($ch, $redirects = 0, $curlopt_returntransfer = true, $curlopt_maxredirs = 20, $curlopt_header = false) {
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$data = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$exceeded_max_redirects = $curlopt_maxredirs > $redirects;
		$exist_more_redirects = false;
		if ($http_code == 301 || $http_code == 302) {
			if ($exceeded_max_redirects) {
				list($header) = explode("\r\n\r\n", $data, 2);
				$matches = array();
				preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
				$url = trim(array_pop($matches));
				$url_parsed = parse_url($url);
				if (isset($url_parsed)) {
					curl_setopt($ch, CURLOPT_URL, $url);
					$redirects++;
					return $this->curl_redirect_exec($ch, $redirects, $curlopt_returntransfer, $curlopt_maxredirs, $curlopt_header);
				}
			}
			else {
				$exist_more_redirects = true;
			}
		}
		if ($data !== false) {
			if (!$curlopt_header)
				list(,$data) = explode("\r\n\r\n", $data, 2);
			if ($exist_more_redirects) return false;
			if ($curlopt_returntransfer) {
				return $data;
			}
			else {
				echo $data;
				if (curl_errno($ch) === 0) return true;
				else return false;
			}
		}
		else {
			return false;
		}
	}
}
?>