<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Class BApiGeo {

    public static $default = [
        "CITY_ID"=>497,
        "CITY_NAME"=>"Москва",
        "REGION_ID"=>43,
        "REGION_NAME"=>"Москва и МО",
        "B_AREA"=>"EU",
    ];

    public static $params = [
        "DEBUG"=>true,
        "COOKIE_NAME"=>"TricolorGeo",
        "COOKIE_EXP"=>32140800, // 1 year
        "COOKIE_PATH"=>"/",
        "COOKIE_DOMAIN"=>"",
        "REQUEST_CHANGE"=>"geo",
        "IP_VARIABLE_NAME"=>"REMOTE_ADDR",
    ];

    public static $debug = [];

    public static function Init($ip = false){

        $changeID = $_REQUEST[self::$params["REQUEST_CHANGE"]];
        if(!empty($changeID)){

            // change city
            self::$debug[] = "changeRequest";

            $data = self::GetGeoData($changeID, "id");
            return self::SetGeo($data,true);

        }

        $cookieData = unserialize(trim($_COOKIE[self::$params["COOKIE_NAME"]]));
        if(!empty($cookieData)) {

            // setup location from cookie
            self::$debug[] = "cookieQuery";
            return self::SetGeo($cookieData,false);

        } else {

            // setup location from geoip
            self::$debug[] = "geoipQuery";

            $query = self::DetectFromIP($ip);
            $data = self::GetGeoData($query["city"]["name_ru"], "name");

            return self::SetGeo($data,true);

        }

    }

    public static function DetectFromIP($ip = false){

        // setup ip
        if($ip == false){ $ip = $_SERVER[self::$params["IP_VARIABLE_NAME"]]; }

        // api query
        include_once($_SERVER['DOCUMENT_ROOT']. "/local/classes/SxGeo/SxGeo.php");
        $SxGeo = new SxGeo($_SERVER['DOCUMENT_ROOT']. "/local/classes/SxGeo/SxGeoCity.dat");
        $query = $SxGeo->getCityFull($ip);
        unset($SxGeo);

        return $query;

    }

    private static function GetGeoData($propValue = "", $propName = "id"){

        self::$debug[] = "bitrixQuery";

        if(empty($propValue)){ return []; }

        $result = [];

        $filter = [
            "IBLOCK_ID"=>IBLOCK_GEO,
            "ACTIVE"=>"Y",
            "ACTIVE_DATE"=>"Y",
        ];

        if($propName === "id"){
            $filter["ID"] = $propValue;
        } else {
            $filter["NAME"] = $propValue;
        }

        $query = BApi::BitrixQuery([
            "FILTER"=>$filter,
            "SELECT"=>[
                "ID","NAME","IBLOCK_SECTION_ID","PROPERTY_B_AREA",
            ],
            "FAST_MODE"=>true,
            "RESULT_ARRAY"=>false,
        ]);

        $result["CITY_ID"] = $query["ID"];
        $result["CITY_NAME"] = $query["NAME"];
        $result["B_AREA"] = $query["PROPERTY_B_AREA_VALUE"];

        if(!empty($query["IBLOCK_SECTION_ID"])){

            $query = BApi::BitrixQuerySection([
                "FILTER"=>[
                    "IBLOCK_ID"=>IBLOCK_GEO,
                    "ACTIVE"=>"Y",
                    "ACTIVE_DATE"=>"Y",
                    "ID"=>$query["IBLOCK_SECTION_ID"],
                ],
                "SELECT"=>[
                    "ID","NAME",
                ],
                "RESULT_ARRAY"=>false,
            ]);

            $result["REGION_ID"] = $query["ID"];
            $result["REGION_NAME"] = $query["NAME"];

        }

        unset($query);

        return $result;

    }

    private static function SetGeo($data, $store = false){

        if(
            empty($data["CITY_ID"]) ||
            empty($data["CITY_NAME"]) ||
            empty($data["REGION_ID"]) ||
            empty($data["REGION_NAME"]) ||
            empty($data["B_AREA"])
        ){

            self::$debug[] = "error";
            $data = self::$default;
            $data["DETECTION"] = "N";


        } else {

            self::$debug[] = "success";
            $data["DETECTION"] = "Y";

        }

        if($store){

            self::StoreGeo($data);

        }

        if(self::$params["DEBUG"]){
            $data["DEBUG"] = implode('->', self::$debug);
        }

        return $data;

    }

    private static function StoreGeo($data){

        self::$debug[] = "store";

        $data = serialize($data);
        if(empty($data)){ return false; }

        setcookie(self::$params["COOKIE_NAME"], $data, time() + self::$params["COOKIE_EXP"], self::$params["COOKIE_PATH"], self::$params["COOKIE_DOMAIN"]);

        return true;

    }

}

$geo = BApiGeo::Init();

define("GEO_CITY_ID", $geo["CITY_ID"]);
define("GEO_CITY_NAME", $geo["CITY_NAME"]);
define("GEO_REGION_ID", $geo["REGION_ID"]);
define("GEO_REGION_NAME", $geo["REGION_NAME"]);
define("GEO_B_AREA", $geo["B_AREA"]);
