<!-- 
Eesti pakiautomaatide nimekirjade laadimine oma andmebaasi Codeigniter 3 platvormil.
Automaatide nimekirjad võetakse ettevõttete kodulehtedelt XML, JSON ja CSV formaadis.
-->

<?php

public function omnivaXML()
{
    $xml=simplexml_load_file("https://www.omniva.ee/locations.xml") or die("Error: Cannot create object");

    foreach ($xml as $LOCATION)
    {
        $data = array(
            'CARRIERID' => "OMNIVA",
            'PACKOMATID' => substr(str_replace(' ', '', $LOCATION->NAME), 0, 10),
            'COUNTRY' => substr($LOCATION->A0_NAME, 0, 10),
            'ZIPCODEID' => substr($LOCATION->ZIP, 0, 10),
            'STREET' => substr($LOCATION->A5_NAME, 0, 138) . " " . substr($LOCATION->A7_NAME, 0, 10) . " " .substr($LOCATION->A8_NAME, 0, 10),
            'CITY' => substr($LOCATION->A2_NAME, 0, 60),
            'ADDRESS' => substr($LOCATION->A1_NAME, 0, 80) . " " . substr($LOCATION->A2_NAME, 0, 80) . " " . substr($LOCATION->A3_NAME, 0, 88),
            'NAME' => substr($LOCATION->NAME, 0, 160),
            'LONGITUDE' => $LOCATION->X_COORDINATE,
            'LATITUDE' => $LOCATION->Y_COORDINATE
        );
        $this->db->insert('asukohad', $data);
    }

    $xml = "";
    $data = "";
}

public function smartpostLae($filename)
{
    $jsonData = file_get_contents($filename);
    $jsonOk = substr($jsonData, 13, -1);
    //echo $jsonOk;
    $json = json_decode($jsonOk);
    
    foreach ($json as $item) 
    {
        $data = array(
            'CARRIERID' => "ITELLA",
            'PACKOMATID' => $item->id,
            'COUNTRY' => $item->countryCode,
            'ZIPCODEID' => $item->postalCode,
            'STREET' => substr($item->address->en->address, 0, 160),
            'CITY' => substr($item->address->en->postalCodeName, 0, 60),
            'ADDRESS' => substr($item->address->en->municipality, 0, 250),
            'NAME' => substr($item->publicName->en, 0, 160),
            'LONGITUDE' => $item->location->lon,
            'LATITUDE' => $item->location->lat
        );
        //echo $item->id;
        //echo $item->publicName->fi;
        //echo $item->longitude;
        $this->db->insert('asukohad', $data);
    }

    $json = "";
    $data = "";
    $jsonData = "";
    $jsonOk = "";
}

public function smartpostJSON()
{
    //Eesti
    $filename = 'http://locationservice.posti.com/location?countryCode=lv';
    $this->smartpostLae($filename);

    //Läti
    $filename = 'http://locationservice.posti.com/location?countryCode=lv';
    $this->smartpostLae($filename);

    //Leedu
    $filename = 'http://locationservice.posti.com/location?countryCode=lt';
    $this->smartpostLae($filename);

    //Soome
    $filename = 'http://locationservice.posti.com/location?countryCode=fi';
    $this->smartpostLae($filename);
}

public function dpdCSV()
{
    $lines =file('ftp://ftp.dpd.ee/parcelshop/psexport_latest.csv');
    array_shift($lines);
    $x = 0;

    foreach($lines as $data)
    {
        list(
            $paketshop[],
            $depot[],
            $firma[],
            $aadress[],
            $indeks[],
            $linn[],
            $notused7[],
            $notused8[],
            $notused9[],
            $notused10[],
            $notused11[],
            $notused12[],
            $notused13[],
            $notused14[],
            $notused15[],
            $notused16[],
            $notused17[],
            $notused18[],
            $notused19[],
            $notused20[],
            $notused21[],
            $notused22[],
            $country[]
        )
        = explode('|',$data);
        $data = array(
            'CARRIERID' => "DPD",
            'PACKOMATID' => substr($paketshop[$x], 0, 10),
            'COUNTRY' => substr($country[$x], 0, 2),
            'ZIPCODEID' => substr($indeks[$x], 0, 10),
            'STREET' => substr($aadress[$x], 0, 160),
            'CITY' => substr($linn[$x], 0, 60),
            //'ADDRESS' => "",
            'NAME' => substr($firma[$x], 0, 160)
            //'LONGITUDE' => "",
            //'LATITUDE' => ""
        );

        $this->db->insert('asukohad', $data);
        $x++;
    }
}

public function matkahuoltoJSON()
{
    //$jsonobj = file("https://www.api.matkahuolto.io/search/offices/area?left=11.62348034419147&right=42.003271016759065&top=70.93414296843171&bottom=59.07655569867418");
    //$filename = 'http://traktorist.org/stuff/jsontest.json';
    $filename = 'https://www.api.matkahuolto.io/search/offices/area?left=11.62348034419147&right=42.003271016759065&top=70.93414296843171&bottom=59.07655569867418';

    $jsonData = file_get_contents($filename);
    $json = json_decode($jsonData);

    $x = 0;

    foreach ($json as $item) 
    {
        $data = array(
            'CARRIERID' => "MATKAHUOLT",
            'PACKOMATID' => $item->officeCode,
            'COUNTRY' => "FI",
            'ZIPCODEID' => $item->officePostalCode,
            'STREET' => substr($item->officeStreetAddress, 0, 160),
            'CITY' => substr($item->officeCity, 0, 60),
            'ADDRESS' => "",
            'NAME' => substr($item->officeName, 0, 160),
            'LONGITUDE' => $item->longitude,
            'LATITUDE' => $item->latitude
        ); 
        $this->db->insert('asukohad', $data);
        $x++;
    }
}

?>