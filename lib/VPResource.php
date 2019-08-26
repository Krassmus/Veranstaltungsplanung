<?php

class VPResource extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'resources_objects';
        $config['has_many']['children'] = [
            'class_name' => 'VPResource',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func' => 'findByParent_id',
            'on_delete' => 'delete',
            'on_store' => 'store',
        ];
        parent::configure($config);
    }
}