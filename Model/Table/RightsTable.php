<?php

namespace App\Model\Table;

use App\Model\Entity\Right;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Rights Model
 *
 * @property \Cake\ORM\Association\BelongsToMany $Roles
 */
class RightsTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->table('rights');
        $this->displayField('title');
        $this->primaryKey('id');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        $validator
                ->integer('id')
                ->allowEmpty('id', 'create');

        $validator
                ->requirePresence('controller', 'create')
                ->notEmpty('controller');

        $validator
                ->requirePresence('action', 'create')
                ->notEmpty('action');

        $validator
                ->allowEmpty('prefix');

        $validator
                ->allowEmpty('title');

        $validator
                ->allowEmpty('description');

        return $validator;
    }
    }

}
