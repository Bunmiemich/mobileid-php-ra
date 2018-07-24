<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\MRegModel;
use PhpParser\Error;
use Redirect;

class MRegController extends Controller
{

    public function GetUserDataByMsisdn($msisdn){
        $model = new MRegModel();
        $body = $model->GetMobileUserData($msisdn);
        return $body;
    }

    public function CheckIfUserExists($msisdn){
        $data = $this->GetUserDataByMsisdn($msisdn);
        $obj = json_decode($data);

        if(isset($obj->Fault->Reason) && $obj->Fault->Reason == "NO_SUCH_MOBILEUSER"){
            return 0;
        }else{
            return 1;
        }
    }

    public function CreateMobileUser(Request $request){
        $this->validate($request,[
            "msisdn" => "required",
            "fname" => "required",
            "lname" => "required",
            "language" =>  "required",
            "ssn" => "required",
            "address" => "required",
            "address2" => "",
            "city" => "required",
            "stateorprovince" => "required",
            "postalcode" => "required",
            "country" => "required"
        ]);

        $msisdn = $request->input("msisdn");
        $fname = $request->input("fname");
        $lname = $request->input("lname"); //rename to surname?
        $lang = $request->input("language");
        $ssn = $request->input("ssn");
        $address = $request->input("address");
        $address2 = $request->input("address2");
        $city = $request->input("city");
        $stateOrProvince = $request->input("stateorprovince");
        $postalcode = $request->input("postalcode");
        $country = $request->input("country");

        //TODO: this cant be correct
        if($this->CheckIfSimExists($msisdn) !== true){
            return view("pages.registration")->with("msg","ERROR: SIM card doesnt exist");
        }

        if($this->CheckIfUserExists($msisdn) == 1){
            return view("pages.registration")->with("msg","ERROR: User already exists");
        }else{

            //create user
            $model = new MRegModel();
            $data = $model->CreateMobileUser($msisdn,$fname,$lname,$lang,$ssn,$address,$address2,$city,$stateOrProvince,$postalcode,$country);
            $obj = json_decode($data);

            if(isset($obj->Fault->Reason)){
                print_r($obj);
            }else{
                $obj = $this->ActivateMobileUser($msisdn);

                if($this->ErrorOrNot($obj) !== false){
                    return view("pages.index")->with("msg","ERROR: Activation failed");
                }
            }

            return view("pages.index")->with("msg","Created user for MSISDN: {$msisdn} and started activation");
        }
    }

    public function LookupUser(Request $request){
        $this->validate($request,[
            "msisdn" => "required",
        ]);

        $msisdn = $request->input("msisdn");
        $data = $this->GetUserDataByMsisdn($msisdn);

        if($this->ErrorOrNot($data) == true){
            return Redirect::back()->withErrors(["this MSISDN doesnt exist", "MSISDN doesnt exist"]);
        }

        $array = json_decode($data,true);

        //TODO: check if string contains givenname rather than using absolute values
        foreach($array["MSS_RegistrationResp"]["UseCase"]["Outputs"] as $key=>$val){
            foreach($val as $k=>$v){
                if($v == "http://mss.ficom.fi/TS102204/v1.0.0/PersonID#givenName" || $v == "GivenName"){
                    $givenName = next($val);
                }
                if($v == "http://mss.ficom.fi/TS102204/v1.0.0/PersonID#surName" || $v == "Surname"){
                    $surName = next($val);
                }
                if($v == "State"){
                    $state = last($val);
                }
            }
        }

        $data = array("fname"=>"$givenName","surname"=>"$surName","msisdn"=>"$msisdn","state"=>"$state");
        return view("pages.userinfo",["data" => $data]);

    }

    /*
     * feed this an JSON array
     */
    public function ErrorOrNot($body){
        $obj = json_decode($body);

        if(isset($obj->Fault->Reason)){
            return true;
        }else{
            return false;
        }
    }

    public function ActivateMobileUser($msisdn){
        $model = new MRegModel();
        $data = $model->ActivateMobileUser($msisdn);


        return $data;
    }

    public function CheckIfSimExists($msisdn){
        $model = new MRegModel();
        $data = $model->GetSimCardData($msisdn);

        if($this->ErrorOrNot($data) == false){
            return true;
        }else{
            return false;
        }
    }


    public function DeactivateMobileUser(Request $request, $msisdn){
        $msisdn = $request->route("msisdn");
        $model = new MRegModel();
        $data = $model->DeactivateUser($msisdn);

        if($this->ErrorOrNot($data) == false){
            return view("pages.lookup");
        }else{
            return Redirect::back()->withErrors(["Error deactivating user", "Error deactivating user"]);
        }
    }

    public function TestSignature(Request $request){
        $msisdn = $request->route("msisdn");
        $model = new MRegModel();
        $data = $model->TestSignature($msisdn);

        if($this->ErrorOrNot($data) == false){
            return view("pages.lookup");
        }else{
            return Redirect::back()->withErrors(["Error sending test signature", "Error sending test signature"]);
        }


        //TODO: Finish this please
    }


}
