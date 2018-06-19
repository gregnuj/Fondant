<?php
namespace Fondant\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * JqueryUi component
 */
class JqueryUiComponent extends Component
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = ['limit' => 250];
    public $components = ['Fondant.Fondant'];

    public function select2(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $displayField = $controller->{$model}->displayField();
        $query = $controller->{$model}->find();
        $query->select(['id' => 'id', 'text' => $displayField]);
        if (!empty($controller->request->query['term'])){
            $term = $controller->request->query['term'];
            $query->where(["$displayField like" => "%{$term}%"]);
        }
        $query->order($displayField);
        $query->limit($this->config('limit'));
        $controller->set($model, ['results' => $query->all()]);
        $controller->set('_serialize', $model);
    }

    public function autocomplete(){
        $controller = $this->_registry->getController();
        $model = $controller->name;
        $displayField = $controller->{$model}->displayField();
        $query = $controller->{$model}->find('list');
        if (!empty($controller->request->query['term'])){
            $term = $controller->request->query['term'];
            $query->where(["$displayField like '{$term}%'"]);
        }
        $query->order($displayField);
        $query->limit($this->config('limit'));
        $controller->set($model, $query);
        $controller->set('_serialize', [ $model ]);
    }
}

