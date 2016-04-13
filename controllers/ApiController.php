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
        $breakpoint = null;
        $error = null;
        $segments = $root->AirSegments->AirSegment;
            
        foreach ($segments as $key => $segment) {
            //find residence time
            $dateD = $segment->Departure['Date'] . ' ' . $segment->Departure['Time'];
            $dateA = $segment->Arrival['Date'] . ' ' . $segment->Arrival['Time'];
            $difference = strtotime($dateA) - strtotime($dateD);
            $residenceTime[$key] = $difference;
            
            //save all board/off cities 
            array_push($boards, strval($segment->Board['City']));
            array_push($offs, strval($segment->Off['City']));
        }

        //check for breakpoint city
        foreach ($offs as $key => $value) {
            if (count($offs) - 1 === $key) {
                //break if the current off city is the last one
                break;
            }
            if ($value !== $boards[$key + 1]) {
                $breakpoint = $value;
                break;
            }
        }

        $roundTrip = $boards[0] === $offs[count($offs) - 1];
        //destination - max residence time in a city
        $destination = $offs[max(array_keys($residenceTime))];
        
        return array(
            'error' => $error,            
            'roundTrip' => $roundTrip,
            'residenceSeconds' => $residenceTime,
            'destinationCity' => $destination,
            'breakpoint' => $breakpoint,
            'boardCities' => $boards, 
            'offCities' => $offs
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
