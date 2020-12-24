<?php
namespace root\base;

use libs\db;
use libs\pdo;

class model
{
    protected $db, $pdo, $cfg, $error;
    public function __construct(array $cfg = null)
    {
        $this->cfg = $cfg;
    }
    public function pdo()
    {
        $this->pdo || $this->pdo = pdo::init($this->cfg);
        return $this->pdo;
    }
    public function db(string $table = '')
    {
        $this->db ? ($table && $this->db->table($table)) : $this->db = db::init($table, $this->cfg);
        return $this->db;
    }
    public function error($err)
    {
        $this->error = $err;
        return false;
    }
    public function getError()
    {
        return $this->error;
    }
}
