<?php

namespace App\Models;

use COM;

class SQLAccModel
{
    protected $ComServer;

    public function __construct()
    {
        $this->ComServer = new COM("SQLAcc.BizApp") or die("Could not initialise SQLAcc.BizApp object.");
    }

    public function checkLogin()
    {
        $status = $this->ComServer->IsLogin();

        if ($status == true) {
            $this->ComServer->Logout();
        }

        $this->ComServer->Login("ADMIN", "ADMIN", "D:\\eStream\\SQLAccounting\\Share\\Default.DCF", "ACC-0001.FDB");
    }

}