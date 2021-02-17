<?php

namespace DBSessionStorage\SaveHandler;

use Zend\Session\SaveHandler\DbTableGateway;

/**
 * Class EncodedDbTableGateway
 *
 * @package DBSessionStorage\SaveHandler
 */
class EncodedDbTableGateway extends DbTableGateway
{
    public static $shallSaveSession = true;
    
    /**
     * @inheritdoc
     */
    public function read($id, $destroyExpired = true)
    {
        $rows = $this->tableGateway->select(array(
            $this->options->getIdColumn()   => $id,
            $this->options->getNameColumn() => $this->sessionName,
        ));
        if ($row = $rows->current()) {
            if ($row->{$this->options->getModifiedColumn()} +
                $row->{$this->options->getLifetimeColumn()} > time()) {
                return base64_decode($row->{$this->options->getDataColumn()});
            }
        }

        return '';
    }


    /**
     * @inheritdoc
     */
    public function write($id, $data)
    {
        if (false === static::$shallSaveSession) {
            return true;
        }
        $data = base64_encode($data);
        $data = array(
            $this->options->getModifiedColumn() => time(),
            $this->options->getDataColumn()     => (string) $data,
        );
        $rows = $this->tableGateway->select(array(
            $this->options->getIdColumn()   => $id,
            $this->options->getNameColumn() => $this->sessionName,
        ));
        if ($row = $rows->current()) {
            try {
                $this->tableGateway->update($data, array(
                    $this->options->getIdColumn()   => $id,
                    $this->options->getNameColumn() => $this->sessionName,
                ));
                $this->gc($this->lifetime);
                
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        }
        $data[$this->options->getLifetimeColumn()] = $this->lifetime;
        $data[$this->options->getIdColumn()]       = $id;
        $data[$this->options->getNameColumn()]     = $this->sessionName;
        try {
            $this->tableGateway->insert($data);
            $this->gc($this->lifetime);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
