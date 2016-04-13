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
        For the sake of simplicity, I just used static xml file `@webroot/trips.xml`
    */
    private function getData() {
        $filename = \Yii::getAlias('@webroot') . '/trips.xml';
        
        try {
            //read example file
            $root = new \SimpleXMLElement(file_get_contents($filename));
            
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
            return $this->redirect('api/trip');
        }

        $boards = array();
        $offs = array();
        $residenceTime = array();
        $breakpoint = null;
        $segments = $root->AirSegments->AirSegment;
            
        foreach ($segments as $segment) {
            $key = count($residenceTime);
            //find residence time
            $dateD = $segment->Departure['Date'] . ' ' . $segment->Departure['Time'];
            $dateA = $segment->Arrival['Date'] . ' ' . $segment->Arrival['Time'];
            $difference = strtotime($dateA) - strtotime($dateD);
            $residenceTime[$key] = $difference;
             
            array_push($boards, strval($segment->Board['City']));
            array_push($offs, strval($segment->Off['City']));
        }

        //check for breakpoint
        foreach ($offs as $key => $value) {
            if ($value !== $boards[$key + 1]) {
                $breakpoint = $value;
                break;
            }
        }

        $roundTrip = $boards[0] == $offs[count($offs) - 1];
        //destination - max residence time in a city
        $destination = $offs[max(array_keys($residenceTime))];
        
        return array(
            'error' => false,
            'boardsCities' => $boards, 
            'offsCities' => $offs,
            'roundTrip' => $roundTrip,
            'residenceSeconds' => $residenceTime,
            'destinationCity' => $destination,
            'breakpoint' => $breakpoint
        );
        
    }

    public function actionError()
    {               
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;    
        return array(
            'error' => 'Opps. Something goes wrong!'
        );  
    }

   
}
