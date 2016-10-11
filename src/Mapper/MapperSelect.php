<?php
/**
 *
 * This file is part of Atlas for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Atlas\Orm\Mapper;

use Atlas\Orm\Table\TableSelect;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\SubselectInterface;

/**
 *
 * A SELECT object for Mapper queries.
 *
 * @package atlas/orm
 *
 */
class MapperSelect implements SubselectInterface
{
    /**
     *
     * The TableSelect being decorated.
     *
     * @var TableSelect
     *
     */
    protected $tableSelect;

    /**
     *
     * A callable back to the Mapper-specific getSelectedRecord() method.
     *
     * @var callable
     *
     */
    protected $getSelectedRecord;

    /**
     *
     * A callable back to the Mapper-specific getSelectedRecords() method.
     *
     * @var callable
     *
     */
    protected $getSelectedRecords;

    /**
     *
     * A callable back to the Mapper-specific getSelectedRecordSet() method.
     *
     * @var callable
     *
     */
    protected $getSelectedRecordSet;

    /**
     *
     * Select with these relateds.
     *
     * @var array
     *
     */
    protected $with = [];

    /**
     *
     * Constructor.
     *
     * @param TableSelect $tableSelect The TableSelect instance being decorated.
     *
     * @param callable $getSelectedRecord A callable back to the Mapper-specific
     * getSelectedRecord() method.
     *
     * @param callable $getSelectedRecords A callable back to the Mapper-specific
     * getSelectedRecords() method.
     *
     * @param callable $getSelectedRecordSet A callable back to the Mapper-specific
     * getSelectedRecordSet() method.
     *
     */
    public function __construct(
        TableSelect $tableSelect,
        callable $getSelectedRecord,
        callable $getSelectedRecords,
        callable $getSelectedRecordSet
    ) {
        $this->tableSelect = $tableSelect;
        $this->getSelectedRecord = $getSelectedRecord;
        $this->getSelectedRecords = $getSelectedRecords;
        $this->getSelectedRecordSet = $getSelectedRecordSet;
    }

    /**
     *
     * Decorates the underlying TableSelect object's __toString() method so that
     * (string) casting works properly.
     *
     * @return string
     *
     */
    public function __toString()
    {
        $this->tableColumns();
        return $this->tableSelect->__toString();
    }

    /**
     *
     * Forwards method calls to the underlying TableSelect object.
     *
     * @param string $method The call to the underlying TableSelect object.
     *
     * @param array $params Params for the method call.
     *
     * @return mixed If the call returned the underlying TableSelect object (a
     * fluent method call) return *this* object instead to emulate the fluency;
     * otherwise return the result as-is.
     *
     */
    public function __call($method, $params)
    {
        $result = call_user_func_array([$this->tableSelect, $method], $params);
        return ($result === $this->tableSelect) ? $this : $result;
    }

    /**
     *
     * Implements the SubSelect::getStatement() interface.
     *
     * @return string
     *
     */
    public function getStatement()
    {
        $this->tableColumns();
        return $this->tableSelect->getStatement();
    }

    /**
     *
     * Implements the SubSelect::getBindValues() interface.
     *
     * @return array
     *
     */
    public function getBindValues()
    {
        return $this->tableSelect->getBindValues();
    }

    /**
     *
     * Sets relateds on the select.
     *
     * @param array
     *
     * @return $this
     *
     */
    public function with(array $with)
    {
        $this->with = $with;
        return $this;
    }

    /**
     *
     * Returns a Record object from the Mapper.
     *
     * @return RecordInterface|false A Record on success, or false on failure.
     *
     */
    public function fetchRecord()
    {
        $this->tableColumns();

        $cols = $this->fetchOne();
        if (! $cols) {
            return false;
        }

        return call_user_func($this->getSelectedRecord, $cols, $this->with);
    }

    /**
     *
     * Returns an array of Record objects from the Mapper (*not* a RecordSet!).
     *
     * @return array
     *
     */
    public function fetchRecords()
    {
        $this->tableColumns();

        $data = $this->fetchAll();
        if (! $data) {
            return [];
        }

        return call_user_func($this->getSelectedRecords, $data, $this->with);
    }

    /**
     *
     * Returns a RecordSet object from the Mapper.
     *
     * @return RecordSetInterface|array A RecordSet on success, or an empty
     * array on failure.
     *
     */
    public function fetchRecordSet()
    {
        $this->tableColumns();

        $data = $this->fetchAll();
        if (! $data) {
            return [];
        }

        return call_user_func($this->getSelectedRecordSet, $data, $this->with);
    }
}
