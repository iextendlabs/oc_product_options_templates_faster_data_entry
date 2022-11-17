<?php

class ControllerExtensionModuleOptionTemplates extends Controller {

	private $error = array();



	private function install(){



		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."template_option_value` (

		  `product_option_value_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,

		  `product_option_id` int(11) NOT NULL,

		  `template_id` int(11) NOT NULL,

		  `option_id` int(11) NOT NULL,

		  `option_value_id` int(11) NOT NULL,

		  `quantity` int(3) NOT NULL,

		  `subtract` tinyint(1) NOT NULL,

		  `price` decimal(15,4) NOT NULL,

		  `price_prefix` varchar(1) NOT NULL,

		  `points` int(8) NOT NULL,

		  `points_prefix` varchar(1) NOT NULL,

		  `weight` decimal(15,8) NOT NULL,

		  `weight_prefix` varchar(1) NOT NULL

		)");



		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."template_option` (

		  `product_option_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,

		  `template_id` int(11) NOT NULL,

		  `option_id` int(11) NOT NULL,

		  `value` text NOT NULL,

		  `required` tinyint(1) NOT NULL

		)");



		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."option_templates` (

		  `template_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,

		  `name` varchar(255) NOT NULL

		) ");

	}



	private function getProductOptions($template_id){



		$product_option_data = array();



		$product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "template_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.template_id = '" . (int)$template_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");







		foreach ($product_option_query->rows as $product_option) {

			$product_option_value_data = array();



			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "template_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON(pov.option_value_id = ov.option_value_id) WHERE pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' ORDER BY ov.sort_order ASC");



			foreach ($product_option_value_query->rows as $product_option_value) {

				$product_option_value_data[] = array(

					'product_option_value_id' => $product_option_value['product_option_value_id'],

					'option_value_id'         => $product_option_value['option_value_id'],

					'quantity'                => $product_option_value['quantity'],

					'subtract'                => $product_option_value['subtract'],

					'price'                   => $product_option_value['price'],

					'price_prefix'            => $product_option_value['price_prefix'],

					'points'                  => $product_option_value['points'],

					'points_prefix'           => $product_option_value['points_prefix'],

					'weight'                  => $product_option_value['weight'],

					'weight_prefix'           => $product_option_value['weight_prefix']

				);

			}



			$product_option_data[] = array(

				'product_option_id'    => $product_option['product_option_id'],

				'product_option_value' => $product_option_value_data,

				'option_id'            => $product_option['option_id'],

				'name'                 => $product_option['name'],

				'type'                 => $product_option['type'],

				'value'                => $product_option['value'],

				'required'             => $product_option['required']

			);

		}



		return $product_option_data;

	}

	private function loadView($data,$view){



		$this->load->language('extension/module/optiontemplates');



		$this->document->setTitle($this->language->get('heading_title'));



		$data['heading_title'] = $this->language->get('heading_title');





		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['button_add'] = $this->language->get('button_add');



		$data['breadcrumbs'] = array();



		$data['breadcrumbs'][] = array(

			'text' => $this->language->get('text_home'),

			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)

		);



		$data['breadcrumbs'][] = array(

			'text' => $this->language->get('text_extension'),

			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)

		);



		$data['breadcrumbs'][] = array(

			'text' => $this->language->get('heading_title'),

			'href' => $this->url->link('extension/module/optiontemplates', 'user_token=' . $this->session->data['user_token'], 'SSL')

		);



		$data['header'] = $this->load->controller('common/header');



		$data['column_left'] = $this->load->controller('common/column_left');

		$data['footer'] = $this->load->controller('common/footer');

		$data['template']=$view;

		$this->response->setOutput($this->load->view('extension/module/optiontemplates', $data));

	}





	public function getList(){

		$list=$this->db->query("select * from `".DB_PREFIX."option_templates`");

		$data=array();

		$data['templates']=array();

		foreach ($list->rows as $key => $value) {

			$data['templates'][]=array(

				'template_id'=>$value['template_id'],

				'name'=>$value['name'],

				'edit'=>$this->url->link('extension/module/optiontemplates/update','template_id='.$value['template_id'].'&user_token=' . $this->session->data['user_token'], 'SSL'),

				'delete'=>$this->url->link('extension/module/optiontemplates/delete','template_id='.$value['template_id'].'&user_token=' . $this->session->data['user_token'], 'SSL')

			);

		}

		return $data['templates'];

	}

	public function getForm(){

		//Show Add Template Form



		$data['product_options'] = $this->getProductOptions($this->request->get['template_id']);



		$this->load->model('catalog/option');





		$data['option_values'] = array();



		foreach ($data['product_options'] as $product_option) {

			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {

				if (!isset($data['option_values'][$product_option['option_id']])) {

					$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);

				}

			}

		}





		$data['old']=true;

		$data['template_id']=(int)$this->request->get['template_id'];

		$name_query=$this->db->query("select name from " . DB_PREFIX . "option_templates where template_id=".$data['template_id']);

		$data['template_name']=$name_query->row['name'];

		$data['user_token'] = $this->session->data['user_token'];





		$data['action']=$this->url->link('extension/module/optiontemplates/store','user_token=' . $this->session->data['user_token'], 'SSL');



		$this->loadView($data,'RawForm');



	}

	public function getSelect(){

		$html='';

		$list=$this->getList();

		foreach($list as $item){

			$html.='<option value="'.$item['template_id'].'">'.$item['name'].'</option>';

		}

		echo $html;

	}

	public function add(){

		//Show Add Template Form

		$data['product_options'] = array();

		$data['option_values'] = array();

		$data['old']=false;



		$data['user_token'] = $this->session->data['user_token'];

		$data['cancel']=$this->url->link('extension/module/optiontemplates','user_token=' . $this->session->data['user_token'], 'SSL');

		$data['action']=$this->url->link('extension/module/optiontemplates/store','user_token=' . $this->session->data['user_token'], 'SSL');



		$this->loadView($data,'Form');

	}

	// private function dd($var){
	//
	// 	echo "<pre>";
	//
	// 	print_r($var);
	//
	// 	echo "</pre>";
	//
	// 	exit;
	// 
	// }

	public function update(){

		//Show Add Template Form



		$data['product_options'] = $this->getProductOptions($this->request->get['template_id']);



		$this->load->model('catalog/option');





		$data['option_values'] = array();



		foreach ($data['product_options'] as $product_option) {

			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {

				if (!isset($data['option_values'][$product_option['option_id']])) {

					$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);

				}

			}

		}





		$data['old']=true;

		$data['template_id']=(int)$this->request->get['template_id'];

		$name_query=$this->db->query("select name from " . DB_PREFIX . "option_templates where template_id=".$data['template_id']);

		$data['template_name']=$name_query->row['name'];

		$data['user_token'] = $this->session->data['user_token'];

		$data['cancel']=$this->url->link('extension/module/optiontemplates','user_token=' . $this->session->data['user_token'], 'SSL');

		$data['action']=$this->url->link('extension/module/optiontemplates/store','user_token=' . $this->session->data['user_token'], 'SSL');



		$this->loadView($data,'Form');



	}



	public function store(){

		$data=$this->request->post;

		if(isset($data['template_id'])){

			$template_id=(int)$data['template_id'];

			$this->db->query("update " . DB_PREFIX . "option_templates set name='".$this->db->escape($data['name'])."' where template_id=".$template_id);



			$this->db->query("delete from " . DB_PREFIX . "template_option where template_id=".$template_id);

			$this->db->query("delete from " . DB_PREFIX . "template_option_value where template_id=".$template_id);



		}else{

			$this->db->query("insert into " . DB_PREFIX . "option_templates set name='".$this->db->escape($data['name'])."'");

			$template_id = $this->db->getLastId();

		}

		if (isset($data['product_option'])) {

			foreach ($data['product_option'] as $product_option) {

				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {

					if (isset($product_option['product_option_value'])) {

						$this->db->query("INSERT INTO " . DB_PREFIX . "template_option SET template_id = '" . (int)$template_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");



						$product_option_id = $this->db->getLastId();



						foreach ($product_option['product_option_value'] as $product_option_value) {

							$this->db->query("INSERT INTO " . DB_PREFIX . "template_option_value SET product_option_id = '" . (int)$product_option_id . "', template_id = '" . (int)$template_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");

						}

					}

				} else {

					$this->db->query("INSERT INTO " . DB_PREFIX . "template_option SET template_id = '" . (int)$template_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");

				}

			}

		}



		$this->response->redirect($this->url->link('extension/module/optiontemplates', 'user_token=' . $this->session->data['user_token'], 'SSL'));





	}

	public function index() {

		//check and install the tables if not exist

		$this->install();



		//custom code starts here

		//get list of templates

		$data['templates']=$this->getList();

		$data['add']=$this->url->link('extension/module/optiontemplates/add', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel']=$this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');



		//custom code ends



		$this->loadView($data,'main');

	}



	public function delete(){

		$this->db->query("delete from ".DB_PREFIX."option_templates where template_id=".$this->request->get['template_id']);

		$this->db->query("delete from ".DB_PREFIX."template_option_value where template_id=".$this->request->get['template_id']);

		$this->db->query("delete from ".DB_PREFIX."template_option where template_id=".$this->request->get['template_id']);

		$this->response->redirect($this->url->link('extension/module/optiontemplates', 'user_token=' . $this->session->data['user_token'], 'SSL'));

	}

	protected function validate() {

		if (!$this->user->hasPermission('modify', 'extension/module/optiontemplates')) {

			$this->error['warning'] = $this->language->get('error_permission');

		}



		return !$this->error;

	}

}
