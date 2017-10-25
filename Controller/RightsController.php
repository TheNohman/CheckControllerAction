<?php

namespace App\Controller\System;

use App\Controller\AppController;
use ReflectionClass;
use ReflectionMethod;

/**
 * Rights Controller
 *
 * @property \App\Model\Table\RightsTable $Rights
 */
class RightsController extends AppController {

    private $archi = [
        'Admin', 'Manager', 'Profile', 'Systeme', ''
    ];

    /**
     * Index method
     *
     * @return void
     */
    public function index() {
        $this->set('rights', $this->paginate($this->Rights));
        $this->set('_serialize', ['rights']);
    }

    /**
     * synchronize method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function synchronize() {
        $this->request->allowMethod('post');
        $controllersByDir = $this->getControllers();
        $controllersByDirWithActions = [];
        foreach ($controllersByDir as $dir => $controllers):
            foreach ($controllers as $controller):
                if ($dir != 'root'):
                    $controllersByDirWithActions[$dir][$controller] = $this->getActions($controller, $dir . '\\');
                else:
                    $controllersByDirWithActions[$dir][$controller] = $this->getActions($controller);
                endif;
            endforeach;
        endforeach;
        $datas = $this->formatData($controllersByDirWithActions);

        $rights = $this->formatArrayToRights($datas);
        $this->clearOldRights($rights);
        foreach ($rights as $right):
            $this->Rights->save($right);
        endforeach;
        return $this->redirect(['action' => 'index']);
    }

    private function getControllers() {
        $files = scandir('../src/Controller/');
        $ignoreList = ['.', '..', 'Component', 'AppController.php'];
        $results = ['root' => []];
        foreach ($files as $file) :
            if (!in_array($file, $ignoreList)) :
                $controller = explode('.', $file)[0];
                if (preg_match("#Controller#", $controller)):
                    array_push($results['root'], $controller);
                else:
                    $subFiles = scandir('../src/Controller/' . $controller . '/');
                    $results[$controller] = [];
                    foreach ($subFiles as $subFile):
                        if (!in_array($subFile, $ignoreList)) :
                            $subController = explode('.', $subFile)[0];
                            array_push($results[$controller], $subController);
                        endif;
                    endforeach;
                endif;
            endif;
        endforeach;
        return $results;
    }

    private function getActions($controllerName, $directory = "") {
        $className = 'App\\Controller\\' . $directory . $controllerName;
        $class = new ReflectionClass($className);
        $actions = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $results = [];
        $ignoreList = ['beforeFilter', 'afterFilter', 'initialize'];
        foreach ($actions as $action) {
            if ($action->class == $className && !in_array($action->name, $ignoreList)) {
                array_push($results, $action->name);
            }
        }
        return $results;
    }

    private function formatData($datas) {
        $results = [];
        foreach ($datas as $prefix => $controllers):
            foreach ($controllers as $controller => $actions):
                foreach ($actions as $action):
                    array_push($results, [
                        'controller' => $controller,
                        'prefix' => strtolower($prefix),
                        'action' => $action
                    ]);
                endforeach;
            endforeach;
        endforeach;
        return $results;
    }

    private function formatArrayToRights($datas) {
        $results = [];
        foreach ($datas as $key => $data):
            $data['prefix'] = ($data['prefix'] == 'root') ? NULL : $data['prefix'];
            if (empty($data['prefix'])):
                $conditions = ['controller' => $data['controller'], 'action' => $data['action']];
            else:
                $conditions = ['controller' => $data['controller'], 'action' => $data['action'], 'prefix' => $data['prefix']];
            endif;
            if ($this->Rights->exists($conditions)):
                $right = $this->Rights->find()->where($conditions)->first();
                array_push($results, $right);
            else:
                $right = $this->Rights->newEntity();
                array_push($results, $this->Rights->patchEntity($right, $data));
            endif;
        endforeach;
        return $results;
    }

    private function clearOldRights($datas) {
        $rights = $this->Rights->find();
        foreach ($rights as $right):
            $deleted = TRUE;
            foreach ($datas as $data):
                if ($data->action == $right->action && $data->controller == $right->controller && $data->prefix == $right->prefix):
                    $deleted = FALSE;
                endif;
            endforeach;
            if ($deleted):
                $this->Rights->delete($right);
            endif;
        endforeach;
    }

    /**
     * Edit method
     *
     * @param string|null $id Right id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null) {
        $right = $this->Rights->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $right = $this->Rights->patchEntity($right, $this->request->data);
            if ($this->Rights->save($right)) {
                $this->Flash->success(__('The right has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The right could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('right'));
        $this->set('_serialize', ['right']);
    }

}
