<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\base\Exception;

class ApiController extends Controller
{

    public function actionIndex() {
        return $this->redirect('api/trip');
    }

    /*
        Probably XML file should be attached via AJAX.
        $xml = file_get_contents('php://input');
        For the sake of simplicity, I just used static xml file `@webroot/trips.xml`
    */
    private function getData() {
        $filename = \Yii::getAlias('@webroot') . '/trips.xml';
        
        try {
            //read example file
            $root = @new \SimpleXMLElement(file_get_contents($filename));
            
        } catch (Exception $e) {
            Yii::error("Parse XML file error. " . $e->getMessage());
            return null;
        }

        return $root;
    }


    public function actionTrip()
    {       

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $root = $this->getData();
        if (!isset($root)) {
            return array(
                'error' => 'String could not be parsed as XML'
            ); 
        }

        $boards = array();
        $offs = array();
        $residenceTime = array();
        $destinationCity = null;
        $breakpointCity = null;
        $error = null;

        $segments = $root->AirSegments->AirSegment;
        $segmentsCount = count($segments);    

        for ($i = 0; $i < $segmentsCount - 1; $i++) {
            $segmentFrom = $segments[$i];
            $segmentTo = $segments[$i + 1];

            //find residence time during each residence
            $dateFrom = $segmentFrom->Arrival['Date'] . ' ' . $segmentFrom->Arrival['Time'];
            $dateTo = $segmentTo->Departure['Date'] . ' ' . $segmentTo->Departure['Time'];
            $difference = strtotime($dateTo) - strtotime($dateFrom);
            $residenceTime[$i] = $difference;

            //check for breakpoint cities
            if (strval($segmentTo->Board['City']) !== strval($segmentFrom->Off['City'])) {
                $breakpointCity = strval($segmentFrom->Off['City']);
            }
            
            //save all board/off cities 
            array_push($boards, strval($segmentFrom->Board['City']));
            array_push($offs, strval($segmentFrom->Off['City']));
        }

        //destination city - a city with max residence time
        $maxTimeKey = array_keys($residenceTime, max($residenceTime));
        $destinationCity = $offs[$maxTimeKey[0]];
        
        return array(
            'error' => $error,     
            'destinationCity' => $destinationCity,
            'breakpointCity' => $breakpointCity
        );
        
    }

    public function actionError()
    {               
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;    
        return array(
            'error' => 'Opps. Something goes wrong! Wrong API url'
        );  
    }

   
}
