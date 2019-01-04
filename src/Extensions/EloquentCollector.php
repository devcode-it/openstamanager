<?php

namespace Extensions;

class EloquentCollector extends \DebugBar\DataCollector\PDO\PDOCollector
{
    protected $capsule;

    public function __construct($capsule)
    {
        parent::__construct();
        $this->capsule = $capsule;
        $this->addConnection($this->getTraceablePdo(), 'Eloquent PDO');
    }

    // Override
    public function getName()
    {
        return 'eloquent_pdo';
    }

    // Override
    public function getWidgets()
    {
        return [
            'eloquent' => [
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => 'eloquent_pdo',
                'default' => '[]',
            ],
            'eloquent:badge' => [
                'map' => 'eloquent_pdo.nb_statements',
                'default' => 0,
            ],
        ];
    }

    /**
     * @return Illuminate\Database\Capsule\Manager;
     */
    protected function getEloquentCapsule()
    {
        return $this->capsule;
    }

    /**
     * @return PDO
     */
    protected function getEloquentPdo()
    {
        return $this->getEloquentCapsule()->getConnection()->getPdo();
    }

    /**
     * @return \DebugBar\DataCollector\PDO\TraceablePDO
     */
    protected function getTraceablePdo()
    {
        return new \DebugBar\DataCollector\PDO\TraceablePDO($this->getEloquentPdo());
    }
}
