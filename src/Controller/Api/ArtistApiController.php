<?php
namespace App\Controller\Api;

use App\Controller\Api\AppController;
use Cake\Http\Client;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Event\Event;
use Cake\Core\Configure; 
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

class ArtistApiController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->RequestHandler->renderAs($this, 'json');
    }

    /*
    *
    * Endpoint   Artist/{@artistName}/album/{@albumname}
    * 
    * Parameters 
    * Name        | Type        | Description
    * ------------------------------------------------------------------------------
    * artistName  | string      | name of an Artist (e.g ABBA, LANY, JOURNEY)
    * albumname   | string      | name of an Artist Album (e.g  Arrival, Frontiers )
    *
    * Status Code 
    * Name        | Type        | Description
    * ------------------------------------------------------------------------------
    * code        | int         | HTTP Status Code (e.g 200, 300, 400)
    * message     | string      | Code description (e.g 200 = OK, 300 = Something went wrong. Please contact administrator, 400 = invalid Parameter)
    *
    */
    public function Artist($artistName = NULL ,$album = NULL, $albumame = NULL)
    {

      $http = new Client();
      $curl = curl_init();

      $header = explode('/',$this->request->getRequestTarget());

      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt_array($curl, [
        CURLOPT_URL => Configure::read('API_ACCESS_TOKEN'), // Spotify Access Token
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => ["Authorization: Basic " . base64_encode(Configure::read('CLIENT_ID').':'.Configure::read('CLIENT_SECRET'))],
      ]);

      $responseToken = curl_exec($curl);
      curl_close($curl);

      $http = new Client(['headers' => ['Authorization' => 'Bearer ' . json_decode($responseToken)->access_token]]);

      /*
      * initialization vars
      */
      $code = '';
      $message = '';
      $albums = [];
      $tracks = [];
      $sortedReleaseDate = [];

      if(count($header) == 3 ) {
         /*
          * Spotify Search Artist endpoint
          * Return : Artist's Albums 
          */
          $getSearchedArtistId = Configure::read('API_SEARCH') . $artistName . '&type=album';
          $responseSearchedArtistId = $http->get($getSearchedArtistId);
          $dataSearchedArtist = $responseSearchedArtistId->getJSON();
        if(isset($dataSearchedArtist['albums']['items'])) {
          foreach($dataSearchedArtist['albums']['items'] as $item) {
            $code = 200;
            $message = "Ok";
            
            array_push($albums, $item['release_date'] .":". $item['name']);
          } 
        } else {
            $code = 400;
            $message = 'Invalid Parameter or Empty Tracks';
        }

        rsort($albums);
        foreach($albums as $dataToSort) {
          $albumName = explode(":",$dataToSort);
          array_push($sortedReleaseDate, $albumName[1]);
        }
      } elseif(count($header) == 5 && in_array('album',$header)) {
          /*
          * search the artist and album name
          */   
          $getSearchedArtistIdAlbum = Configure::read('API_SEARCH').'album'.'%3A'.$albumname.'%20'.'artist'.'%3A'.$artistName.'&type=album';
          $responseSearchedArtistIdAlbum = $http->get($getSearchedArtistIdAlbum);
          $dataSearchedArtistAlbum = $responseSearchedArtistIdAlbum->getJSON();

          if(isset($dataSearchedArtistAlbum['albums']['items'][0])) {
            $code = 200;
            $message = 'OK';
            /*
            * Album id from search endpoint pass by Album name
            */
            $AlbumId = $dataSearchedArtistAlbum['albums']['items'][0]['id'];
            /*
            * Spotify Search Album track(s) endpoint
            * Return : Album tracks
            * For this endpoint Tracks are already been sorted by the number of tracks ASC order
            */
            $getSearchedArtistId = Configure::read('API_SEARCH_TRACKS') . $AlbumId . '/tracks';
            $responseSearchedArtistId = $http->get($getSearchedArtistId);
            $dataSearchedArtist = $responseSearchedArtistId->getJSON();
            foreach($dataSearchedArtist['items'] as $track) {
              array_push($tracks, $track['name']);
            }
          } else {
            $code = 400;
            $message = 'invalid Parameter or Empty Tracks';
          }
      } else {
          $code = 300;
          $message = 'Something went wrong. Please contact administrator';
      }

     /*
      * API Response
      */
      $this->set([
        'code'=> $code,
        'message' => $message,
        'data' => (!empty($tracks) ? $tracks : $sortedReleaseDate),
        '_serialize' => ['code','message','data'] 
      ]);  

    }
    
}