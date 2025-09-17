<?php
class AutoNumber {
    private $mydb;

    public function __construct() {
        global $mydb;
        $this->mydb = $mydb;
    }

    public function getAutoNumber($autocode) {
        $sql = "SELECT * FROM tblautonumbers WHERE AutoCode = ?";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$autocode]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    public function updateAutoNumber($autocode) {
        $sql = "UPDATE tblautonumbers SET AutoNumber = AutoNumber + 1 WHERE AutoCode = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$autocode]);
    }

    public function generateCode($autocode) {
        $auto = $this->getAutoNumber($autocode);
        if ($auto) {
            $this->updateAutoNumber($autocode);
            return $auto->AutoStart . str_pad($auto->AutoNumber, $auto->AutoEnd, '0', STR_PAD_LEFT);
        }
        return null;
    }
}
?>

