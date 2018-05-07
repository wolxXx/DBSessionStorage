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
            $this->destroy($id);
        }

        return '';
    }


    /**
     * @inheritdoc
     */
    public function write($id, $data)
    {
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

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
