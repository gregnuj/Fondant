<?php
namespace Fondant\Controller\Component;

use Cake\Core\ConventionsTrait;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Utility\Hash;
use Bake\View\Helper\BakeHelper;

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
    }

    protected function _setSearchVars(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $displayField = $controller->{$model}->displayField();
        $fields = $controller->{$model}->schema()->columns();
        $searchFields = array_combine($fields, $fields);
        $controller->set(compact('displayField', 'searchFields'));
    }

    protected function _getAssociationTypes(){
        $controller = $this->_registry->getController();
        if ($controller->request->getData('types')){
            $types = (array)$controller->request->getData('types');
        }elseif ($controller->request->getQuery('types')){
            $types = $controller->request->getQuery('types');
        }else{
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
        $model = $controller->name;
        $modelObj = $controller->{$model};
        $types = $this->_getAssociationTypes();
        $controller->set('association_list', $this->_getAssociations($modelObj, $types, $depth));
    }
    
    public function getAssociations(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $modelObj = $controller->{$model};
        $types = $this->_getAssociationTypes();
        $depth = $this->_getAssociationDepth();
        $controller->set('associations', $this->_getAssociations($modelObj, $types, $depth));
        $controller->set('_serialize', 'associations');
        $controller->render('App/empty');
    }

    protected function _getContain(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $modelObj = $controller->{$model};
        $getMethod = $controller->request->is('post') ? 'getData' : 'getQuery';
        if ($controller->request->{$getMethod}('contain')){
            $contain = (array)$controller->request->{$getMethod}('contain');
        }else if ($controller->request->{$getMethod}('fields')){
            $gotFields = (array)$controller->request->{$getMethod}('fields');
            $contain = [];
            foreach ($gotFields as $field){
                $oparts = explode('.', $field);
                $cparts = array_slice($oparts, 0, -1);
                $contain[] = implode('.', $cparts);
            }
        }else{
            $types = $this->_getAssociationTypes();
            $depth = $this->_getAssociationDepth();
            $contain = $this->_getAssociations($modelObj, $types, $depth);
        }
        return $contain;
    }

    protected function _getFields(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
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
        $model = $controller->name;
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
        $model = $controller->name;
        return $controller->{$model}->find()
            ->select($this->_getFields())
            ->contain($this->_getContain())
            ->where($this->_getConditions())
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
        $model = $controller->name;
        $primaryKey = (array)$controller->{$model}->primaryKey();
        return $controller->{$model}->find()
            ->select($this->_getFields())
            ->contain($this->_getContain())
            ->where(["{$model}.{$primaryKey[0]}" => $id])
            ->first();
    }

    protected function _findByName($name)
    {
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $displayField = $controller->{$model}->displayField();
        return $controller->{$model}->find()
            ->select($this->_getFields())
            ->contain($this->_getContain())
            ->where(["{$model}.{$displayField}" => "$name"])
            ->first();
    }

    public function index($param = null)
    {
        $query = $this->_find();
        $controller = $this->_registry->getController();
        $variableName = $this->_variableName($controller->name);
        $controller->set($variableName, $controller->paginate($query));
        $controller->set('_serialize', [ $variableName ]);
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
        $controller = $this->_registry->getController();
        if ($controller->request->getQuery('flatten') > 0){
           $entity = Hash::flatten($entity->toArray());
        }
        $singularName = $this->_singularName($controller->name);
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
        $model = $controller->name;
        $entity = $this->_findEntity($param);
        $singularName = $this->_singularName($controller->name);
        if ($controller->request->is(['patch', 'post', 'put'])) {
            $entity = $controller->{$model}->patchEntity($entity, $controller->request->data);
            if ($controller->{$model}->save($entity)) {
                $controller->Flash->success(__("The {$singularName} has been saved."));
                return $controller->redirect(["action" => "view", $entity->{$controller->{$model}->displayField()}]);
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
        $model = $controller->name;
        $entity = $controller->{$model}->newEntity();
        $singularName = $this->_singularName($controller->name);
        if ($controller->request->is('post')) {
            $entity = $controller->{$model}->patchEntity($entity, $controller->request->data);
            if ($controller->{$model}->save($entity)) {
                $controller->Flash->success(__("The {$singularName} has been saved."));
                return $controller->redirect(["action" => "view", $entity->{$controller->{$model}->displayField()}]);
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
        $model = $controller->name;
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

    protected function _getconditions(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $conditions = [];
        if ($controller->request->getQuery('conditions')){
            $conditions = $controller->request->query['conditions'];
        }
        if ($match = $controller->request->query('match')){
            $conditions[] = "$controller->{$model} regexp '{$match}'";
        }
        $filter = $controller->request->getQuery('filter');
        if (!empty($filter['form_action'])){
             $controller->redirect(['action' => 'index', '?' => []]);
        }else if (isset($filter['type']) && isset($filter['field']) && isset($filter['value'])){
            $controller->set(compact('filter'));
            $table = $controller->name;
            if (substr($filter['field'], -3) == '_id'){
                $table = $this->_modelNameFromKey($filter['field']);
                $filter['field'] = $controller->{$model}->{$table}->displayField();
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
}

