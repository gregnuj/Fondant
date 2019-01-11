<?php
namespace Fondant\Controller\Component;

use Cake\Core\ConventionsTrait;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Bake\View\Helper\BakeHelper;
use Cake\Event\Event;

/**
 * FondantComponent component
 */
class FondantComponent extends Component
{

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [];

	use ConventionsTrait;

	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading components.
	 *
	 * @return void
	 */

	public function initialize(array $config)
	{
		parent::initialize($config);
		$controller = $this->_registry->getController();
		$controller->loadComponent('RequestHandler');
	}
	
	protected function getModelName(){
		$controller = $this->_registry->getController();
		if (!empty($controller->primaryModel)){
			$table = $controller->primaryModel;
			list($plugin, $model) = pluginSplit($table);
		}else{
			$model = $controller->name;
		}
		return $model;
	}

	public function beforeFilter(Event $event){
		$this->_setSearchVars();
		$this->_setAssociationList();
	}

	public function getAssociations(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$modelObj = $controller->{$model};
		$types = $this->_getAssociationTypes();
		$depth = $this->_getAssociationDepth();
		$controller->set('associations', $this->_getAssociations($modelObj, $types, $depth));
		$controller->set('_serialize', 'associations');
		$controller->render('App/empty');
	}

	public function getAssociatedDetails(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$modelObj = $controller->{$model};
		$associations = $this->_getAssociations($modelObj, ['BelongsTo'], 1);
		foreach($associations as &$association){
			$alias = $association;
			$association = [];
			$assoc = $modelObj->association($alias);
			$target = $assoc->target();
			// From AssociationFilter
			$association[$alias] = [
				'property' => $assoc->property(),
				'variable' => Inflector::variable($assoc->getName()),
				'primaryKey' => (array)$target->getPrimaryKey(),
				'displayField' => $target->getDisplayField(),
				'foreignKey' => $assoc->foreignKey(),
				'alias' => $assoc->getAlias(),
				'controller' => $assoc->getName(), //good enough?
				'fields' => $target->schema()->columns(),
			];
		}
		$controller->set(compact('associations'));
		$controller->set('_serialize', 'associations');
		$controller->render('App/empty');
	}

	/**
	 * Index method
	 */
	public function index($param = null)
	{
		if ($this->request->is('ajax') || $this->request->is('json')  ){

			$controller = $this->_registry->getController();
			$model = $this->getModelName();
			$variableName = $this->_variableName($controller->name);
			$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';

			# Filter conditions are processed first

			# build base query
			$query = $controller->{$model}->find()
		       ->select($this->_getFields())
		       ->contain($this->_getContain())
		       ->where($this->_getFilterConditions())
	       ;

			# get count of total records
			$recordsTotal = $query->count();

			# add Conditions and get count
			$query = $query->where($this->_getSearchConditions());
			$recordsFiltered = $query->count();

			# get length and page
			$draw = $controller->request->{$getMethod}('draw');
			$page = $this->_getPage();
			$length = $this->_getLimit();
			$query = $query    
				->order($this->_getOrder())
				->limit($length)
				->page($page)
			;
			//$this->log($query->sql());
			$controller->set(compact('draw', 'length', 'page', 'recordsTotal', 'recordsFiltered'));
			$controller->set($variableName, $query);
			$controller->set('_serialize', [ 'draw', 'length', 'page', 'recordsTotal', 'recordsFiltered', $variableName ]);
		}
	}

	/**
	 * View method
	 *
	 * @param string|null 
	 * @return void
	 * @throws \Cake\Network\Exception\NotFoundException When record not found.
	 */
	public function view($param = null)
	{
		$entity = $this->_findEntity($param);
		//$this->log(json_encode($entity, JSON_PRETTY_PRINT));
		$controller = $this->_registry->getController();
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		if ($controller->request->{$getMethod}('flatten') > 0){
			$entity = Hash::flatten($entity->toArray());
		}
		$singularName = $this->_singularName($controller->name);
		$this->log($singularName);
		$controller->set($singularName, $entity);
		$controller->set('_serialize', [ $singularName ]);
		$controller->render("{$controller->name}/view");
	}


	/**
	 * Edit method
	 *
	 * @param string|null 
	 * @return void Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Network\Exception\NotFoundException When record not found.
	 */
	public function edit($param = null)
	{
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$entity = $this->_findEntity($param);
		$singularName = $this->_singularName($controller->name);
		if ($controller->request->is(['patch', 'post', 'put'])) {
			$entity = $controller->{$model}->patchEntity($entity, $controller->request->data);
			if ($controller->{$model}->save($entity)) {
				$controller->Flash->success(__("The {$singularName} has been saved."));
				return $controller->redirect(["action" => "view", $entity->{$controller->{$model}->getPrimaryKey()}]);
			} else {
				$controller->log($entity->errors());
				$controller->Flash->error(__("The modified {$singularName} could not be saved. Please, try again."));
			}
		}
		$controller->set($singularName, $entity);
		$controller->set('_serialize', [ $singularName ]);
	}

	/**
	 * Add method
	 *
	 * @return void Redirects on successful add, renders view otherwise.
	 */
	public function add()
	{
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$entity = $controller->{$model}->newEntity();
		$singularName = $this->_singularName($controller->name);
		if ($controller->request->is('post')) {
			$entity = $controller->{$model}->patchEntity($entity, $controller->request->data);
			if ($controller->{$model}->save($entity)) {
				$controller->Flash->success(__("The {$singularName} has been saved."));
				return $controller->redirect(["action" => "view", $entity->{$controller->{$model}->getPrimaryKey()}]);
			} else {
				$controller->log($entity->errors());
				$controller->Flash->error(__("The new {$singularName} could not be saved. Please, try again."));
			}
		}
	}

	/**
	 * Delete method
	 *
	 * @param string|null 
	 * @return \Cake\Network\Response|null Redirects to index.
	 * @throws \Cake\Network\Exception\NotFoundException When record not found.
	 */
	public function delete($param = null)
	{
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$singularName = $this->_singularName($controller->name);
		$controller->request->allowMethod(['post', 'delete']);
		$entity = $this->_findEntity($param);
		if ($controller->{$model}->delete($entity)) {
			$controller->Flash->success(__("The {$singularName} has been deleted."));
		} else {
			$controller->Flash->error(__("The {$singularName} could not be deleted. Please, try again."));
		}
		return $controller->redirect(['action' => 'index']);
	}

	protected function _setSearchVars(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$displayField = $controller->{$model}->getDisplayField();
		$fields = $controller->{$model}->schema()->columns();
		$fieldNames = [];
		foreach($fields as $i => $field){
			$fieldNames[$i] = Inflector::humanize($field);
			if (stripos($fieldNames[$i], ' Id')){
				$fieldNames[$i] = substr($fieldNames[$i], 0, -3);
			}
		}
		$searchFields = array_combine($fields, $fieldNames);

		$controller->set(compact('displayField', 'searchFields'));
	}

	protected function _getAssociationTypes(){
		$controller = $this->_registry->getController();
		if ($controller->request->getData('types')){
			$types = (array)$controller->request->getData('types');
		}elseif ($controller->request->getQuery('types')){
			$types = $controller->request->getQuery('types');
		}else{
			#$types = ['BelongsTo'];
			$types = ['BelongsTo', 'BelongsToMany', 'HasMany', 'HasOne'];
		}
		return $types;
	}

	protected function _getAssociationDepth(){
		$controller = $this->_registry->getController();
		if ($controller->request->getData('depth') !== null){
			$depth = $controller->request->getData('depth');
		}elseif ($controller->request->getQuery('depth') !== null){
			$depth = $controller->request->getQuery('depth');
		}else{
			$depth = 1;
		}
		return $depth < 4 ? $depth : 4;
	}

	protected function _getAssociationList($modelObj, $types){
		$associations = [];
		$bake = new BakeHelper(new \Cake\View\View);
		foreach ((array)$types as $type){
			$these = $bake->aliasExtractor($modelObj, $type);
			foreach ($these as $association){
				$associations[] = $association;
			}
		}
		sort($associations);
		return $associations;
	}

	protected function _getAssociations($modelObj, $types, $depth){
		$contains = [];
		if ($depth-- > 0){
			$list = $this->_getAssociationList($modelObj, $types);
			if (!empty($list)){
				foreach ($list as $association){
					if ($depth <= 0){
						$contains[] = $association;
					}else{
						$contains[$association] = $this->_getAssociations($modelObj->{$association}->getTarget(), $types, $depth);
					}
				}
			}
		}
		return $contains;
	}

	public function getAssociationTypes(){
		$controller = $this->_registry->getController();
		$controller->set('association_types', $this->_getAssociationTypes());
		$controller->set('_serialize', 'association_types');
		$controller->render('App/empty');
	} 

	protected function _setAssociationList($depth = 1){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$modelObj = $controller->{$model};
		$types = $this->_getAssociationTypes();
		$controller->set('association_list', $this->_getAssociations($modelObj, $types, $depth));
	}

	protected function _getContain(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$modelObj = $controller->{$model};
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		$types = $this->_getAssociationTypes();
		$depth = $this->_getAssociationDepth();
		$assocs = $this->_getAssociations($modelObj, $types, $depth);
		if ($controller->request->{$getMethod}('contain')){
			$requested = (array)$controller->request->{$getMethod}('contain');
		}else if ($controller->request->{$getMethod}('fields')){
			$gotFields = (array)$controller->request->{$getMethod}('fields');
			$requested = [];
			foreach ($gotFields as $field){
				$oparts = explode('.', $field);
				$cparts = array_slice($oparts, 0, -1);
				$requested[] = implode('.', $cparts);
			}
		}else if ($this->request->getParam('action') == 'index'){
			$requested = $this->_getAssociations($modelObj, ['BelongsTo'], $depth);
		}else{
			$requested = $assocs;
		}
		// Only include valid contains
		$contain = [];
		foreach ($requested as $r){
			$s = explode('.', $r);
			if (in_array($s[0], $assocs)){
				$contain[] = $r;
			}
		}
		return $contain;
	}

	protected function _getFields(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$modelObj = $controller->{$model};
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		if ($controller->request->{$getMethod}('fields')){
			$gotFields = (array)$controller->request->{$getMethod}('fields');
			$fields = [];
			foreach ($gotFields as $field){
				$oparts = explode('.', $field);
				$fparts = array_slice($oparts, -2);
				$fields[] = implode('.', $fparts);
			}
		}else{
			$fields = [];
		}
		return $fields;
	}


	protected function _setHiddenColumns(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$action = $controller->request->action;
		$hiddenColumns = [];
		if (isset($controller->{$model}->hiddenColumns)){
			if (isset($controller->{$model}->hiddenColumns[$action])){
				$hiddenColumns = $controller->{$model}->hiddenColumns[$action];
			}
		}
		$controller->set(compact('hiddenColumns'));
	}

	protected function _find(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		return $controller->{$model}->find()
		    ->select($this->_getFields())
		    ->contain($this->_getContain())
		    ->where($this->_getFilterConditions())
		    ->where($this->_getSearchConditions())
		    ->order($this->_getOrder())
		    ->limit($this->_getLimit())
		    ->page($this->_getPage())
	    ;
	}

	protected function _findNone(){
		return [];
	}

	protected function _findEntity($param){
		if (is_numeric($param)){
			return $this->_findById($param);
		}else{
			return $this->_findByName($param);
		}
	}

	protected function _findById($id){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$primaryKey = (array)$controller->{$model}->getPrimaryKey();
		$query = $controller->{$model}->find()
		    ->select($this->_getFields())
		    ->contain($this->_getContain())
		    ->where(["{$model}.{$primaryKey[0]}" => $id]);
		return $query->first();
	}

	protected function _findByName($name)
	{
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$displayField = $controller->{$model}->getDisplayField();
		$query = $controller->{$model}->find()
		    ->select($this->_getFields())
		    ->contain($this->_getContain())
		    ->where(["{$model}.{$displayField}" => "$name"]);
		return $query->first();
	}

	protected function _getSearchConditions(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$associations = $controller->{$model}->associations();
		$conditions = [];
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		if ($columns = $controller->request->{$getMethod}('columns')){
			foreach ($columns as $column){
				$table = false;
				if ($column['search']['value'] != false){
					foreach ($associations as $name => $association){
						$fk = $association->getForeignKey();
						if ($column['name'] == $fk){
							$table = $association->getName();
							$searchField = $association->getDisplayField();
							break;
						}
					}
					if (!$table){
						$table = $controller->name;
						$searchField = $column['name'];
					}
					$operator = $column['search']['regex'] == false ? 'like' : 'regexp';
					$conditions[] = "{$table}.{$searchField} {$operator} '{$column['search']['value']}'";
				}
			}
		}
		//$this->log(serialize($conditions));
		return $conditions;
	}

	protected function _getFilterConditions(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$conditions = [];
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		if ($controller->request->{$getMethod}('conditions')){
			$conditions = (array)$controller->request->{$getMethod}('conditions');
		}
		if ($match = $controller->request->{$getMethod}('match')){
			$conditions[] = "$controller->{$model} regexp '{$match}'";
		}
		$filter = $controller->request->{$getMethod}('filter');
		if (!empty($filter['form_action'])){
			$controller->redirect(['action' => 'index', '?' => []]);
		}else if (isset($filter['type']) && isset($filter['field']) && isset($filter['value'])){
			$controller->set(compact('filter'));
			$table = $controller->name;
			if (substr($filter['field'], -3) == '_id'){
				$table = $this->_modelNameFromKey($filter['field']);
				$filter['field'] = $controller->{$model}->{$table}->getDisplayField();
			}
			switch ($filter['type']) {
			case 'starts with':
				$conditions[] = "{$table}.{$filter['field']} like '{$filter['value']}%'";
				break;
			case 'ends with':
				$conditions[] = "{$table}.{$filter['field']} like '%{$filter['value']}'";
				break;
			case 'contains':
				$conditions[] = "{$table}.{$filter['field']} like '%{$filter['value']}%'";
				break;
			default:
				$conditions[] = "{$table}.{$filter['field']} like '{$filter['value']}'";
				break;
			}
			$controller->set(compact('filter'));
		}
		return $conditions;
	}

	protected function _getOrder(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$results = [];
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		if ($order = $controller->request->{$getMethod}('order')){
			if ($columns = $controller->request->{$getMethod}('columns')){
				foreach ($order as $ord){
					if ($columns[$ord['column']]['orderable'] == 'true'){
						if ($columns[$ord['column']]['data'] != 'false'){
							if (!empty($columns[$ord['column']]['data'])){
								$results[] = "{$model}.{$columns[$ord['column']]['data']} {$ord['dir']}";
							}
						}
					}
				}
			}
		}
		if (empty($results)){
			$displayField = $controller->{$model}->getDisplayField();
			$results[] = "{$model}.{$controller->{$model}->getDisplayField()} ASC";
		}
		return $results;
	}

	protected function _getLimit(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$results = 500;
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		if ($length = $controller->request->{$getMethod}('length')){
			$results = $length;
		}elseif ($limit = $controller->request->{$getMethod}('limit')){
			$results = $limit;  
		}
		return $results;
	}

	protected function _getPage(){
		$controller = $this->_registry->getController();
		$model = $this->getModelName();
		$getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
		$results = 1;
		$start = $controller->request->{$getMethod}('start') ? $controller->request->{$getMethod}('start') : 0;
		$limit = $this->_getLimit();
		$results = ($start + $limit)/$limit;  
		return $results;
	}

}

