<?php

namespace Extension;

class MedooCollector extends \DebugBar\DataCollector\PDO\PDOCollector
{
    public function __construct($database)
    {
        parent::__construct();
        $this->addConnection(new \DebugBar\DataCollector\PDO\TraceablePDO($database->getPDO()), 'Medoo PDO');
    }

    // Override
    public function getName()
    {
        return 'medoo_pdo';
    }

    // Override
    public function getWidgets()
    {
        return [
            'medoo' => [
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => 'medoo_pdo',
                'default' => '[]',
            ],
            'medoo:badge' => [
                'map' => 'medoo_pdo.nb_statements',
                'default' => 0,
            ],
        ];
    }
}
