<!-- 
Eesti pakiautomaatide nimekirjade laadimine oma andmebaasi Codeigniter 3 platvormil.
Automaatide nimekirjad võetakse ettevõttete kodulehtedelt XML, JSON ja CSV formaadis.
-->

<?php

public function omnivaXML()
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.omniva.ee/locations.csv");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $body = curl_exec($ch);
    curl_close($ch);

    $body = str_getcsv($body,"\n");

    //var_dump($body);
    foreach ($body as $bodyElement) {
        $bodyCsv = explode(';', $bodyElement);
        if ($bodyCsv[11] != NULL) {
            $flatNumber = '-' . trim(str_replace('"', '', $bodyCsv[11]));
        } else {
            $flatNumber = '';
        }
        if ($bodyCsv[5] != NULL) {
            $city = trim(str_replace('"', '', $bodyCsv[5])) . ', ';
        } else {
            $city = '';
        }
        $insert = array(
            'CARRIERID'=>($bodyCsv[2] == 0 ? 'OMNIVA_PAC' : 'OMNIVAPOST'),
            'PACKOMATID'=>trim(str_replace('"', '', $bodyCsv[0])),
            'COUNTRY'=>trim(str_replace('"', '', $bodyCsv[3])),
            'ZIPCODEID'=>trim(str_replace('"', '', $bodyCsv[0])),
            'STREET'=>trim(str_replace('"', '', $bodyCsv[8])) . ' ' . trim(str_replace('"', '', $bodyCsv[10])) . $flatNumber,
            'CITY'=>( strlen(trim(str_replace('"', '', $bodyCsv[5])))>0 ? trim(str_replace('"', '', $bodyCsv[5])) : trim(str_replace('"', '', $bodyCsv[4])) ),
            'ADDRESS'=>trim(str_replace('"', '', $bodyCsv[4])),
            'NAME'=>trim(str_replace('"', '', $bodyCsv[1])) . ', ' . trim(str_replace('"', '', $bodyCsv[8])) . ' ' . trim(str_replace('"', '', $bodyCsv[10])) . $flatNumber, 
            'LONGITUDE'=>str_replace('Longitude', '', trim(str_replace('"', '', $bodyCsv[12]))),
            'LATITUDE'=>trim(str_replace('"', '', $bodyCsv[13]))
        );
        $this->andmeladu_model->insertOrUpdate('WEB.dbo.web_MecCarrierPackomats',$insert,array('COUNTRY'=>$insert['COUNTRY'],'CARRIERID'=>$insert['CARRIERID'],'PACKOMATID'=>$insert['PACKOMATID']));
    }
}

public function smartpostLae($filename)
{
    $jsonData = file_get_contents($filename);
    $jsonOk = substr($jsonData, 13, -1);
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


public function matkahuoltoJSON()
{

    $url='https://www.api.matkahuolto.io/search/offices/area?left=11.62348034419147&right=42.003271016759065&top=70.93414296843171&bottom=59.07655569867418';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $result = curl_exec($ch);
    curl_close($ch);  
    $result=json_decode($result, true);

    if( count($result)>0 ){
        foreach ($result as $kk => $vv) {
            $insert =array(
                'CARRIERID'=>'MATKAHUOLT',  
                'PACKOMATID'=>$vv['officeCode'],
                'COUNTRY'=>'FI',
                'ZIPCODEID'=>$vv['officePostalCode'],
                'STREET'=>$vv['officeStreetAddress'],
                'CITY'=>$vv['officeCity'],
                'ADDRESS'=>$vv['officeStreetAddress'],
                'NAME'=>mb_convert_encoding($vv['officeCity'] . ', ' .$vv['officeName'] . ', ' . $vv['officeStreetAddress'], 'HTML-ENTITIES', "UTF-8"),
                'LONGITUDE'=>$vv['longitude'],
                'LATITUDE'=>$vv['latitude'],
            );

            $this->andmeladu_model->insertOrUpdate('WEB.dbo.web_MecCarrierPackomats',$insert,array('COUNTRY'=>$insert['COUNTRY'],'CARRIERID'=>$insert['CARRIERID'],'PACKOMATID'=>$insert['PACKOMATID']));
        }
    }

}

?>