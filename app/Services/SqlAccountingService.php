<?php

namespace App\Services;

use Exception;
use App\Config;

class SqlAccountingService
{
    protected $comServer;

    public function __construct()
    {
        // Initialize the COM object
        $this->comServer = new \COM("SQLAcc.BizApp") or die("Could not initialize SQLAcc.BizApp object.");
    }
    // public function checkLogin()
    // {
    //     // Call the login method of SQLAcc
    //     // Example:
    //     $status = $this->comServer->IsLogin();
    //     if ($status == true) {
    //         $this->comServer->Logout();
    //     }
    //     $this->comServer->Login("ADMIN", "ADMIN", "D:\\eStream\\SQLAccounting\\Share\\Default.DCF", "ACC-0001.FDB");
    // }
    public function CheckLogin()
    {
        global $ComServer;
        $ComServer = new \COM("SQLAcc.BizApp") or die("Could not initialise SQLAcc.BizApp object.");
        $status = $ComServer->IsLogin();

        if ($status == true)
        {
            $ComServer->Logout();
        }

        $config = Config::where('key', 'sqlAccDB')->value('value');
        $sqlAccDB = json_decode($config, true); // true to return associative array
        if ($sqlAccDB != null) {
            $db_username = $sqlAccDB['db_username'];
            $db_password = $sqlAccDB['db_password'];
            $db_path = $sqlAccDB['db_path'];
            $db_name = $sqlAccDB['db_name'];

            $ComServer->Login($db_username, $db_password, #UserName, Password
                            $db_path, #"C:\eStream\SQLAccounting\Share\Default.DCF",  #DCF file
                            $db_name); #Database Name
            return true;
        }
        //$this->comServer->Login("ADMIN", "ADMIN", "D:\\eStream\\SQLAccounting\\Share\\Default.DCF", "ACC-0001.FDB");
        // $ComServer->Login("ADMIN", "ADMIN", #UserName, Password
        //                "C:\\eStream\\SQLAccounting\\Share\\Default.DCF", #"C:\eStream\SQLAccounting\Share\Default.DCF",  #DCF file
        //                "ACC-0001.FDB"); #Database Name

        return false;                
    }

    public function createSalesOrder($data)
    {
        try {
            $isLogin = $this->checkLogin();
            if (!$isLogin) {
                return;
            }
            $so = $this->comServer->SL_SO;
            if ($data['do_no'] == null) {
                $docNo = '<<New>>';
            }
            $so->AddHeader("DocNo", $docNo);
            $so->AddHeader("DocDate", $data['doc_date']);
            $so->AddHeader("CustomerCode", $data['customer_code']);
            $so->AddHeader("Description", $data['description'] ?? "Laravel Sync");
            $so->AddHeader("Agent", $data['agent'] ?? "");
            $so->AddHeader("Terms", $data['terms'] ?? "");
            $so->AddHeader("Location", $data['location'] ?? "");

            foreach ($data['items'] as $item) {
                $so->AddLine(
                    "ItemCode", $item['code'],
                    "Qty", $item['qty'],
                    "UOM", $item['uom'] ?? "UNIT",
                    "Description", $item['description'] ?? ""
                );
            }

            $so->Post();
            return true;
        } catch (Exception $e) {
            throw new Exception("SO creation failed: " . $e->getMessage());
        }
    }

    public function transferSOtoDO($docNo)
    {
        try {
            $isLogin = $this->checkLogin();
            if (!$isLogin) {
                return;
            }
            if ($docNo == null) {
                $docNo = '<<New>>';
            }
            $transfer = $this->comServer->TransferDocument;
            $transfer->SourceDocType = "SL_SO";
            $transfer->TargetDocType = "SL_DO";
            $transfer->SourceDocNo = $docNo;
            $transfer->Transfer();
            return $docNo;
        } catch (Exception $e) {
            throw new Exception("DO transfer failed: " . $e->getMessage());
        }
    }
    
    // [
    //     "id" => 71
    //     "do_no" => "DO-2023-001"
    //     "do_date" => null
    //     "user_id" => 1
    //     "cart_id" => null
    //     "total_price" => "0.00"
    //     "billing_address" => "15,Jalan Mahagoni 1A,Tamu Hill,44300 Batang Kali,Selangor."
    //     "billing_city" => null
    //     "billing_postcode" => null
    //     "billing_state" => null
    //     "attn_name" => "handy04"
    //     "attn_contact" => "0173881398"
    //     "area" => "ALAM"
    //     "shipping_address" => null
    //     "shipping_city" => null
    //     "shipping_postcode" => null
    //     "shipping_state" => null
    //     "payment_method" => null
    //     "transfer_slip" => null
    //     "status" => "processing"
    //     "sql_sync_status" => "ERROR"
    //     "driver_id" => 1
    //     "order_weight" => null
    //     "created_at" => "2025-06-28 20:25:43"
    //     "updated_at" => "2025-07-28 23:44:55"
    //     "sql_sync_respond" => "Parameter 0: Type mismatch"
    // ]

   public function PostDataDOWithOrderData($order) {
        global $ComServer;
        $this->CheckLogin();
        $order = (array) $order;

        $lDocNo = isset($order['do_no']) && !empty($order['do_no']) ? $order['do_no'] : '<<New>>';

        $BizObject = $ComServer->BizObjects->Find("SL_DO");
        $lMain = $BizObject->DataSets->Find("MainDataSet");
        $lDetail = $BizObject->DataSets->Find("cdsDocDetail");

        $lDocKey = $BizObject->FindKeyByRef("DocNo", $lDocNo);
        if ($lDocKey != null) {
            echo "Update Dockey = ".$lDocKey.".<br>";
            $BizObject->Params->Find("DocKey")->AsString = $lDocKey;
            $BizObject->Open();
            $BizObject->Edit();
            $lMain->Edit();
            //$lMain->FindField("DocNoEX")->AsString = $lDocNo;
            $lMain->FindField("Code")->AsString = $order['sql_customer_code'] ?? null;
            $lMain->FindField("DocDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("PostDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("CompanyName")->AsString = $order['attn_name'];
            $lMain->FindField("Address1")->AsString = $order['billing_address'];
            $lMain->FindField("Address2")->AsString = '';
            $lMain->FindField("Postcode")->AsString = $order['billing_postcode'] ?? '';
            $lMain->FindField("City")->AsString = $order['billing_city'] ?? '';
            $lMain->FindField("State")->AsString = $order['billing_state'] ?? '';
            //$lMain->FindField("Country")->AsString = 'MY';
            $lMain->FindField("Phone1")->AsString = $order['attn_contact'];

            $r = $lDetail->RecordCount();
            $x = 1;
            while ($x <= $r) {
                $lDetail->First();
                $lDetail->Delete();
                $x++;
            }

            foreach ($order['items'] as $index => $item) {      
                $lDetail->Append();
                $lDetail->FindField("ItemCode")->AsString = trim($item->product_sku) ?? "";
                $lDetail->FindField("Description")->AsString = $item->product_name ?? "";
                $lDetail->FindField("UOM")->AsString = trim($item->uom_name) ?? "";
                $lDetail->FindField("Qty")->AsFloat = $item->quantity ?? 0.0;
                // $lDetail->FindField("Tax")->AsString = $item->tax_code;
                // $lDetail->FindField("TaxRate")->AsString = $item->tax_rate;
                // $lDetail->FindField("TaxInclusive")->value = $item->tax_inclusive;
                $lDetail->FindField("UnitPrice")->AsFloat = $item->unit_price ?? 0.0;
                $lDetail->FindField("Amount")->AsFloat = $item->price ?? 0.0;
                // $lDetail->FindField("TaxAmt")->AsFloat = $item->tax;
                $lDetail->FindField("REMARK1")->AsString = $item->remark ?? "";
                // if ($index === array_key_last($order['items'])) {
                //     $lDetail->FindField("DESCRIPTION3")->AsString = "\n\n\n\n" . $item->more_description;
                // }
                $lDetail->Post(); // ✅ Finish inserting this row
            }
        } else {
            echo "New Delivery Order (Create)<br>";
            $BizObject->New();
            $lMain->FindField("DocKey")->value = -1;
            $lMain->FindField("DocNo")->AsString = $lDocNo;
           // $lMain->FindField("DOCNOSETKEY")->Value = config('config.do_docnosetkey'); // or use your own value
            //$lMain->FindField("DocNoEX")->AsString = $lDocNo;
            $lMain->FindField("Code")->AsString = $order['sql_customer_code'] ?? null;
            $lMain->FindField("DocDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("PostDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("CompanyName")->AsString = $order['attn_name'];
            $lMain->FindField("Address1")->AsString = $order['billing_address'];
            $lMain->FindField("Address2")->AsString = '';
            $lMain->FindField("Postcode")->AsString = $order['billing_postcode'] ?? '';
            $lMain->FindField("City")->AsString = $order['billing_city'] ?? '';
            $lMain->FindField("State")->AsString = $order['billing_state'] ?? '';
            //$lMain->FindField("Country")->AsString = 'MY';
            $lMain->FindField("Phone1")->AsString = $order['attn_contact'];
            //$lMain->FindField("Description")->AsString = $order['payment_method'];
            // Add detail rows
            //  +"cart_id": 28
            // +"product_id": 4
            // +"quantity": null
            // +"weight": "4.00"
            // +"unit_price": "40.00"
            // +"price": "160.00"
            // +"remark": null
            // +"product_name": "Brown Rice"
            // +"product_sku": "A004"
            // +"uom_name": "KG"
            foreach ($order['items'] as $index => $item) {
                $lDetail->Append();
                $lDetail->FindField("ItemCode")->AsString = trim($item->product_sku) ?? "";
                $lDetail->FindField("Description")->AsString = $item->product_name ?? "";
                $lDetail->FindField("UOM")->AsString = trim($item->uom_name) ?? "";
                $lDetail->FindField("Qty")->AsFloat = $item->quantity ?? 0.0;
                // $lDetail->FindField("Tax")->AsString = $item->tax_code;
                // $lDetail->FindField("TaxRate")->AsString = $item->tax_rate;
                // $lDetail->FindField("TaxInclusive")->value = $item->tax_inclusive;
                $lDetail->FindField("UnitPrice")->AsFloat = $item->unit_price ?? 0.0;
                $lDetail->FindField("Amount")->AsFloat = $item->price ?? 0.0;
                // $lDetail->FindField("TaxAmt")->AsFloat = $item->tax;
                $lDetail->FindField("REMARK1")->AsString = $item->remark ?? "";
                // if ($index === array_key_last($order['items'])) {
                //     $lDetail->FindField("DESCRIPTION3")->AsString = "\n\n\n\n" . $item->more_description;
                // }
                $lDetail->Post();
            }
        }

        $BizObject->Save();

        $doNo = $lMain->FindField("DocNo")->AsString;
        echo date("d M Y h:i:s A") . " - DO Posting Done<br>";
        echo $doNo . " created";
        $BizObject->Close();

        return $doNo;
    }

    public function cancelDO($docNo)
    {
        try {
            $isLogin = $this->checkLogin();
            if (!$isLogin) {
                return;
            }
            $do = $this->comServer->SL_DO;
            $do->Delete($docNo);
            return true;
        } catch (Exception $e) {
            throw new Exception("Cancel Sale Invoice failed: " . $e->getMessage());
        }
    }

    public function PostDataInvoiceWithOrderData($order) {
        global $ComServer;
        $this->CheckLogin();
        $order = (array) $order;

        $lDocNo = isset($order['do_no']) && !empty($order['do_no']) ? $order['do_no'] : '<<New>>';

        $BizObject = $ComServer->BizObjects->Find("SL_IV");
        $lMain = $BizObject->DataSets->Find("MainDataSet"); #lMain contains master data
        $lDetail = $BizObject->DataSets->Find("cdsDocDetail"); #lDetail contains detail data
        $lSN = $BizObject->DataSets->Find("cdsSerialNumber"); #lDetail contains detail data
	
        $lDocKey = $BizObject->FindKeyByRef("DocNo", $lDocNo);
        if ($lDocKey != null) {
            echo "Update Dockey = ".$lDocKey.".<br>";
            $BizObject->Params->Find("DocKey")->AsString = $lDocKey;
            $BizObject->Open();
            $BizObject->Edit();
            $lMain->Edit();
            //$lMain->FindField("DocNoEX")->AsString = $lDocNo;
            $lMain->FindField("Code")->AsString = $order['sql_customer_code'] ?? null;
            $lMain->FindField("DocDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("PostDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("CompanyName")->AsString = $order['attn_name'];
            $lMain->FindField("Address1")->AsString = $order['billing_address'];
            $lMain->FindField("Address2")->AsString = '';
            //$lMain->FindField("Postcode")->AsString = $order['billing_postcode'] ?? '';
            //$lMain->FindField("City")->AsString = $order['billing_city'] ?? '';
           // $lMain->FindField("State")->AsString = $order['billing_state'] ?? '';
            //$lMain->FindField("Country")->AsString = 'MY';
            $lMain->FindField("Phone1")->AsString = $order['attn_contact'];
            $lMain->FindField("Description")->AsString = "Sales - Edited";

            $V = array("ANT", "UNIT");  #ItemCode, UOM

            foreach ($order['items'] as $index => $item) {      
                if ($lDetail->Locate("ItemCode;UOM", $V, False, False)){
                    $lDetail->Edit();
                    $lDetail->FindField("ItemCode")->AsString = trim($item->product_sku) ?? "";
                    $lDetail->FindField("Description")->AsString = $item->product_name ?? "";
                    $lDetail->FindField("UOM")->AsString = trim($item->uom_name) ?? "";
                    $lDetail->FindField("Qty")->AsFloat = $item->quantity ?? 0.0;
                    $lDetail->FindField("Tax")->AsString = "";
                    $lDetail->FindField("TaxRate")->AsString = "";
                    $lDetail->FindField("TaxInclusive")->value = 0;
                    $lDetail->FindField("UnitPrice")->AsFloat = $item->unit_price ?? 0.0;
                    $lDetail->FindField("Amount")->AsFloat = $item->price ?? 0.0;
                    $lDetail->FindField("TaxAmt")->AsFloat = 0;
                    $lDetail->Post(); // ✅ Finish inserting this row
                }
            }
        } else {
            echo "New Sale Invoice (Create)<br>";
            $BizObject->New();
            $lMain->FindField("DocKey")->value = -1;
            $lMain->FindField("DocNo")->AsString = $lDocNo;
           // $lMain->FindField("DOCNOSETKEY")->Value = config('config.do_docnosetkey'); // or use your own value
            //$lMain->FindField("DocNoEX")->AsString = $lDocNo;
            $lMain->FindField("Code")->AsString = $order['sql_customer_code'] ?? null;
            $lMain->FindField("DocDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("PostDate")->value = date("d-m-Y", strtotime($order['do_date']));
            $lMain->FindField("CompanyName")->AsString = $order['attn_name'];
            $lMain->FindField("Address1")->AsString = $order['billing_address'];
            $lMain->FindField("Address2")->AsString = '';
            $lMain->FindField("Postcode")->AsString = $order['billing_postcode'] ?? '';
            $lMain->FindField("City")->AsString = $order['billing_city'] ?? '';
            $lMain->FindField("State")->AsString = $order['billing_state'] ?? '';
            //$lMain->FindField("Country")->AsString = 'MY';
            $lMain->FindField("Phone1")->AsString = $order['attn_contact'];
            $lMain->FindField("Description")->AsString = "Sales invoice";
            // Add detail rows
            //  +"cart_id": 28
            // +"product_id": 4
            // +"quantity": null
            // +"weight": "4.00"
            // +"unit_price": "40.00"
            // +"price": "160.00"
            // +"remark": null
            // +"product_name": "Brown Rice"
            // +"product_sku": "A004"
            // +"uom_name": "KG"
            foreach ($order['items'] as $index => $item) {
                $lDetail->Append();
                $lDetail->FindField("ItemCode")->AsString = trim($item->product_sku) ?? "";
                $lDetail->FindField("Description")->AsString = $item->product_name ?? "";
                $lDetail->FindField("UOM")->AsString = trim($item->uom_name) ?? "";
                $lDetail->FindField("Qty")->AsFloat = $item->quantity ?? 0.0;
                $lDetail->FindField("Tax")->AsString = "";//$item->tax_code;
                $lDetail->FindField("TaxRate")->AsString = "";//$item->tax_rate;
                $lDetail->FindField("TaxInclusive")->value = 0;//$item->tax_inclusive;
                $lDetail->FindField("UnitPrice")->AsFloat = $item->unit_price ?? 0.0;
                $lDetail->FindField("Amount")->AsFloat = $item->price ?? 0.0;
                $lDetail->FindField("TaxAmt")->AsFloat = 0; //$item->tax;
                //$lDetail->FindField("REMARK1")->AsString = $item->remark ?? "";
                // if ($index === array_key_last($order['items'])) {
                //     $lDetail->FindField("DESCRIPTION3")->AsString = "\n\n\n\n" . $item->more_description;
                // }
                $lDetail->Post();
            }

            // $lSN->Append;
            // $lSN->FindField("SERIALNUMBER")->AsString = 'SN-136476';
            // $lSN->Post;
        }

        $BizObject->Save();

        $doNo = $lMain->FindField("DocNo")->AsString;
        echo date("d M Y h:i:s A") . " - Sale Invoice Posting Done<br>";
        echo $doNo . " created";
        $BizObject->Close();

        return $doNo;
    }

    public function cancelInvoice($docNo)
    {
        try {
          global $ComServer;
	
            $BizObject = $ComServer->BizObjects->Find("SL_IV");
            $lMain = $BizObject->DataSets->Find("MainDataSet"); #lMain contains master data
            
            #Find IV Number
            $lDocKey = $BizObject->FindKeyByRef("DocNo", $docNo);
            
            if ($lDocKey != null){
                echo "Dockey = ".$lDocKey."<br>";
                $BizObject->Params->Find("DocKey")->AsString = $lDocKey;
                $BizObject->Open();
                $BizObject->Delete();
                echo date("d M Y h:i:s A")." - Record deleted<br>";
            } else {
                echo date("d M Y h:i:s A")." - Document Not Found<br>";
            }		
            return true;
        } catch (Exception $e) {
            throw new Exception("Cancel Sale Invoice failed: " . $e->getMessage());
        }
    }
}
