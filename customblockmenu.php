<?php

if (!defined('_PS_VERSION_'))
	exit;

class CustomBlockMenu extends Module
{
	public function __construct()
	{
		$this->name = 'customblockmenu';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->author = 'Ismail Albakov (wowsite.ru)';
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('CustomBlockMenu');
		$this->description = $this->l('Show custom menu');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		$this->_clearCache('*');

		Configuration::updateValue('CUSTOM_BLOCK_MENU_TITLE', $this->l('Useful Links'));
		Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', null);

		return parent::install() &&
			$this->registerHook('leftColumn') &&
			$this->registerHook('rightColumn') &&
			$this->registerHook('backOfficeHeader') &&
			$this->registerHook('DisplayCustomBlockMenu') &&
			$this->registerHook('displayFooter');
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	public function hookDisplayCustomBlockMenu($params)
	{
		return $this->displayCustomBlockMenu($params);
	}

	public function hookLeftColumn($params)
	{
		return $this->displayCustomBlockMenu($params);
	}

	public function hookRightColumn($params)
	{
		return $this->displayCustomBlockMenu($params);
	}

	/**
	 * render hidden modal block in footer
	 * @param  obj $params
	 * @return mix
	 */
	public function hookDisplayFooter($params)
	{
		$this->context->controller->addCSS($this->_path.'assets/front/css/customblockmenu.css');
		$this->context->controller->addJS($this->_path.'assets/front/js/customblockmenu.js');
	}

	/**
	 * render hidden modal block in footer
	 * @param  obj $params
	 * @return mix
	 */
	public function hookDisplayBackOfficeHeader($params)
	{
		$this->context->controller->addCSS($this->_path.'assets/admin/css/customblockmenu.css');
		$this->context->controller->addJS($this->_path.'assets/admin/js/jquery.min.js');
		$this->context->controller->addJS($this->_path.'assets/admin/js/customblockmenu.js');
	}

	/**
	 * render template
	 * @param  obj $params
	 * @return mix
	 */
	public function displayCustomBlockMenu($params)
	{
		$data['title'] = Configuration::get('CUSTOM_BLOCK_MENU_TITLE');
		$config = Configuration::get('CUSTOM_BLOCK_MENU_DATA');
		$data['allow_view'] = false;

		if(!is_null($config) || !empty($config)) {
			$data['allow_view'] = true;
			$data['menu'] = json_decode($config, true);
		}

		$this->smarty->assign($data);

		return $this->display(__FILE__, 'views/templates/hooks/customblockmenu.tpl');
	}

	/**
	 * render module settings
	 * @return mix
	 */
	public function getContent()
	{
		$title = Configuration::get('CUSTOM_BLOCK_MENU_TITLE');
		$data = Configuration::get('CUSTOM_BLOCK_MENU_DATA');
		$data = json_decode($data, true);

		$html = '<form id="custom_block_menu_form" class="defaultForm form-horizontal" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post" enctype="multipart/form-data" novalidate="">
			<div class="panel" id="fieldset_0">
				<div class="panel-heading">
					<i class="icon-cogs"></i> '.$this->l('Settings').'
				</div>
				<div class="alert alert-info">'.$this->l('Add needed block and press Save!').'</div>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label>'.$this->l('Block title').'</label>
							<input type="text" name="block_title" class="form-control" value="'.$title.'" placeholder="'.$this->l('Block title').'">
						</div>
					</div>
				</div>
				<div class="form-wrapper">'.(count($data) ? $this->parent_template($data) : '<button type="button" class="btn btn-success add-first-block">'.$this->l('Add block').'</button>').'
				</div><!-- /.form-wrapper -->

				<div class="panel-footer">
					<button type="button" id="module_form_submit_btn" class="btn btn-success pull-right">
					<i class="process-icon-save"></i> '.$this->l('Save').'
					</button>
					<a href="javascript: window.history.back();" class="btn btn-default pull-right">
					<i class="process-icon-close"></i> '.$this->l('Close').'
					</a>
				</div>
			</div>
		</form>

		<script>
			var CBM = {
				saved: "'.$this->l('Saved').'",
				delete_all_children: "'.$this->l('Delete all children data from this block?').'",
				delete_block: "'.$this->l('Delete block?').'",
				add_block: "'.$this->l('Add block').'",
			};
		</script>
		';

		return $html;
	}

	/**
	 * action via ajax
	 * @param  array $array $_POST data
	 * @return mix
	 */
	public function update($array)
	{
		$cookies = new Cookie('psAdmin');
		if(empty($cookies->id_employee))
			return $this->response(400, false);

		if(!count($array))
			return $this->response();

		switch ($array['action']) {
			case 'add':
				return $this->insert($array);
				break;

			case 'delete':
				return $this->delete($array);
				break;

			case 'delete-all-childs':
				$data = $this->delete_all_childs($array);
				return $this->response(200, $this->child_template($data, (int) $array['id']));
				break;
			
			default:
				$data = $this->update_values($array);
				return $this->response(200, $this->parent_template($data));
				break;
		}
	}

	/**
	 * insert block
	 * @param  array $array $_POST data
	 * @return mix
	 */
	public function insert($array)
	{
		switch ($array['type']) {
			case 'first':
				$data = $this->add_first_block();
				return $this->response(200, $this->parent_template($data));
				break;

			case 'parent':
				$data = $this->add_parent_block();
				return $this->response(200, $this->parent_template($data));
				break;

			case 'child':
				$data = $this->add_child_block($array);
				return $this->response(200, $this->child_template($data, (int) $array['id']));
				break;
			
			default:
				return $this->response();
				break;
		}
	}

	/**
	 * delete block
	 * @param  array $array $_POST data
	 * @return mix
	 */
	public function delete($array)
	{
		switch ($array['type']) {
			case 'parent':
				$data = $this->delete_parent($array);
				return $this->response(200, $this->parent_template($data));
				break;

			case 'child':
				$data = $this->delete_child($array);
				return $this->response(200, $this->child_template($data, (int) $array['parent']));
				break;
			
			default:
				return $this->response();
				break;
		}
	}

	/**
	 * update input values
	 * @param  array $array $_POST
	 * @return mix
	 */
	public function update_values($array)
	{

		if(!count($array['parent']))
			return false;

		foreach ($array['parent'] as $key => $value) {

			if(isset($value['child']) && count($value['child'])) {
				foreach($value['child'] as $k => $v) {
					$child[] = array(
						'id' => $v['id'],
						'title' => $v['title'],
						'url' => $v['url'],
					);
				}
			}

			$sql[$key] = array(
				'id' => $value['id'],
				'title' => $value['title'],
				'url' => $value['url'],
				'child' => (isset($child) ? $child : array()),
			);

			if(isset($child))
				unset($child);
		}

		Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($sql));
		Configuration::updateValue('CUSTOM_BLOCK_MENU_TITLE', $array['block_title']);

		return $sql;
	}

	/**
	 * add parent block
	 * @param array
	 */
	public function add_parent_block()
	{
		$data = Configuration::get('CUSTOM_BLOCK_MENU_DATA');

		if(!empty($data)) {
			$data = json_decode($data, true);

			foreach ($data as $key => $value) {
				$lastId = (int) $value['id'];
			}

			$data[] = array(
				'id' => (isset($lastId) ? ($lastId+1) : 1),
				'title' => '',
				'url' => '',
				'child' => array(),
			);			

			Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($data));

			return $data;
		}

		return false;
	}

	/**
	 * add first block
	 * @param array
	 */
	public function add_first_block()
	{
		$data = array(
			array(
				'id' => 1,
				'title' => '',
				'url' => '',
				'child' => array(),
			),
		);

		Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($data));

		return $data;
	}

	/**
	 * add child block
	 * @param array $array
	 * @return bool|array
	 */
	public function add_child_block($array)
	{
		$data = Configuration::get('CUSTOM_BLOCK_MENU_DATA');

		if(!empty($data)) {
			$data = json_decode($data, true);

			foreach ($data as $key => $value) {
				if((int) $value['id'] === (int) $array['id']) {

					if(count($value['child'])) {
						foreach ($value['child'] as $k => $v) {
							$childLastId = $v['id'];
						}
					}

					$data[$key]['child'][] = array(
						'id' => (isset($childLastId) ? ($childLastId+1) : 1),
						'title' => '',
						'url' => '',
					);

					$child = $data[$key]['child'];
				}
			}

			Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($data));

			return (isset($child) ? $child : false);
		}

		return false;
	}

	/**
	 * delete parent block
	 * @param  array $array
	 * @return array
	 */
	public function delete_parent($array)
	{
		if(!isset($array['id']) || empty($array['id']))
			return false;

		$data = Configuration::get('CUSTOM_BLOCK_MENU_DATA');

		if(!empty($data)) {
			$data = json_decode($data, true);

			foreach ($data as $key => $value) {
				if((int) $value['id'] === (int) $array['id'])
					unset($data[$key]);
			}

			Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($data));

			return $data;
		}

		return false;
	}

	/**
	 * delete child block
	 * @param  array $array
	 * @return array
	 */
	public function delete_child($array)
	{
		if(!isset($array['id']) || empty($array['id']))
			return false;

		$data = Configuration::get('CUSTOM_BLOCK_MENU_DATA');

		if(!empty($data)) {
			$data = json_decode($data, true);

			// delete child
			foreach ($data as $key => $value) {
				if((int) $value['id'] === (int) $array['parent']) {
					if(count($value['child'])) {
						foreach ($value['child'] as $k => $v) {
							if((int) $v['id'] === (int) $array['id']) {
								unset($data[$key]['child'][$k]);
							}
						}
					}
				}
			}

			// get updated child
			foreach ($data as $key => $value) {
				if((int) $value['id'] === (int) $array['parent']) {
					$child = $value['child'];
				}
			}

			Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($data));

			return $child;
		}

		return false;
	}

	/**
	 * delete all childs appended to parent
	 * @param  array $array
	 * @return array
	 */
	public function delete_all_childs($array)
	{
		if(!isset($array['id']) || empty($array['id']))
			return false;

		$data = Configuration::get('CUSTOM_BLOCK_MENU_DATA');

		if(!empty($data)) {
			$data = json_decode($data, true);

			foreach ($data as $key => $value) {
				if((int) $value['id'] === (int) $array['id'] && count($value['child']))
					unset($data[$key]['child']);
			}

			Configuration::updateValue('CUSTOM_BLOCK_MENU_DATA', json_encode($data));

			return array();
		}

		return false;
	}	

	/**
	 * render parent block
	 * @return string
	 */
	public function parent_template($array)
	{
		if(!is_array($array))
			return $this->response();

		$html = '';

		foreach ($array as $key => $value) {
			$html .= '<div class="block" data-id="'.$value['id'].'">
			<div class="row">			
				<input type="hidden" name="parent['.$value['id'].'][id]" value="'.$value['id'].'">
				<div class="col-md-12">
					<div class="form-group">
						<p></p>
						<p><b>'.$this->l('Parent block').'</b></p>
					</div>
					<div class="form-group">
						<label>'.$this->l('Link name').'</label>
						<input type="text" name="parent['.$value['id'].'][title]" required class="form-control" value="'.$value['title'].'" placeholder="'.$this->l('Link name').'">
					</div>
					<div class="form-group">
						<label>'.$this->l('Link').'</label>
						<input type="text" name="parent['.$value['id'].'][url]" required class="form-control" value="'.$value['url'].'" placeholder="'.$this->l('Link').'">
					</div>
					<div class="checkbox">
						<label>
							<input type="checkbox" name="parent['.$value['id'].'][is_parent]" class="is_parent" '.(count($value['child']) ? 'checked' : '').'> '.$this->l('Is it parent block?').'
						</label>
					</div>
				</div>
				<div class="child-block-area form-group">'.(count($value['child']) ? $this->child_template($value['child'], (int) $value['id']) : '').'</div>
			</div>
			<hr>
			<div class="row">
				<div class="col-md-10">
					<button type="button" class="btn btn-small btn-danger delete-block">'.$this->l('Delete block?').'</button>
					<button type="button" class="btn btn-small btn-success add-block">'.$this->l('Add block').'</button>
				</div>
			</div>
		</div>';
		}

		return $html;
	}

	/**
	 * render child block
	 * @param  array $array
	 * @param  int $parent
	 * @return array
	 */
	public function child_template($array, $parent)
	{
		if(!is_array($array) || empty($parent))
			return $this->response();

		$html = '';

		foreach ($array as $key => $value) {
			$html .= '<div class="child-block" data-child-id="'.$value['id'].'">
				<input type="hidden" name="parent['.$parent.'][child]['.$value['id'].'][id]" value="'.$value['id'].'">
				<div class="col-md-1"></div>
				<div class="col-md-11">
					<div class="form-group">
						<p></p>
						<p><b>'.$this->l('Child block').'</b></p>
					</div>
					<div class="form-group">
						<label>'.$this->l('Link name').'</label>
						<input type="text" name="parent['.$parent.'][child]['.$value['id'].'][title]" required value="'.$value['title'].'" class="form-control" placeholder="'.$this->l('Link name').'">
					</div>
					<div class="form-group">
						<label>'.$this->l('Link').'</label>
						<input type="text" name="parent['.$parent.'][child]['.$value['id'].'][url]" required value="'.$value['url'].'" class="form-control" placeholder="'.$this->l('Link').'">
					</div>
					<div class="text-right">
						<button type="button" class="btn btn-small btn-warning delete-child-block">'.$this->l('Delete child block').'</button>
						<button type="button" class="btn btn-small btn-info add-child-block">'.$this->l('Add child block').'</button>
					</div>
				</div>
			</div>';
		}

		return $html;
	}

	/**
	 * render response
	 * @param  array $array
	 * @return json
	 */
	public function response($status = 400, $data = '')
	{
		header('Content-Type: application/json');
		echo json_encode(array(
			'status' => $status,
			'data' => $data
		));
		die();		
	}
}